<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PackageDeal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PackageDealController extends Controller
{
    /**
     * Get all package deals with pagination
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'per_page' => 'nullable|integer|min:1|max:100',
            'available_only' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $perPage = $request->get('per_page', 12);
        $availableOnly = $request->get('available_only', false);

        $query = PackageDeal::with(['products' => function ($query) {
            $query->select('products.id', 'products.name', 'products.slug', 'products.images', 'products.base_price')
                ->with(['variants' => function ($q) {
                    $q->inStock()->select('id', 'product_id', 'price', 'stock');
                }]);
        }]);

        if ($availableOnly) {
            $query->available();
        } else {
            $query->active();
        }

        $packages = $query->latest()->paginate($perPage);

        return response()->json([
            'data' => $packages->items(),
            'meta' => [
                'current_page' => $packages->currentPage(),
                'last_page' => $packages->lastPage(),
                'per_page' => $packages->perPage(),
                'total' => $packages->total(),
            ]
        ]);
    }

    /**
     * Get a single package deal by slug
     */
    public function show($slug)
    {
        $package = PackageDeal::where('slug', $slug)
            ->with(['products' => function ($query) {
                $query->with(['variants' => function ($q) {
                    $q->inStock();
                }]);
            }])
            ->first();

        if (!$package) {
            return response()->json([
                'message' => 'Package deal not found'
            ], 404);
        }

        // Add availability status
        $package->available = $package->isAvailable();

        return response()->json([
            'data' => $package
        ]);
    }

    /**
     * Create a new package deal (admin only)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:package_deals,slug',
            'description' => 'nullable|string|max:2000',
            'package_price' => 'required|numeric|min:0|max:1000000',
            'images' => 'nullable|array',
            'images.*' => 'string',
            'is_active' => 'nullable|boolean',
            'stock' => 'nullable|integer|min:0|max:100000',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'products' => 'required|array|min:2',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Calculate original price from products
        $originalPrice = 0;
        foreach ($request->products as $productData) {
            $product = \App\Models\Product::find($productData['id']);
            $quantity = $productData['quantity'] ?? 1;
            $originalPrice += $product->base_price * $quantity;
        }

        $package = PackageDeal::create([
            'name' => $request->name,
            'slug' => $request->slug,
            'description' => $request->description,
            'original_price' => $originalPrice,
            'package_price' => $request->package_price,
            'images' => $request->images,
            'is_active' => $request->is_active ?? true,
            'stock' => $request->stock ?? 0,
            'starts_at' => $request->starts_at,
            'ends_at' => $request->ends_at,
        ]);

        // Calculate and save discount percentage
        $package->calculateDiscount();
        $package->save();

        // Attach products with quantities
        foreach ($request->products as $productData) {
            $package->products()->attach($productData['id'], [
                'quantity' => $productData['quantity'] ?? 1
            ]);
        }

        return response()->json([
            'message' => 'Package deal created successfully',
            'data' => $package->load('products')
        ], 201);
    }

    /**
     * Update a package deal (admin only)
     */
    public function update(Request $request, $id)
    {
        $package = PackageDeal::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'slug' => 'nullable|string|unique:package_deals,slug,' . $id,
            'description' => 'nullable|string|max:2000',
            'package_price' => 'nullable|numeric|min:0|max:1000000',
            'images' => 'nullable|array',
            'images.*' => 'string',
            'is_active' => 'nullable|boolean',
            'stock' => 'nullable|integer|min:0|max:100000',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'products' => 'nullable|array|min:2',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Update products if provided
        if ($request->has('products')) {
            // Recalculate original price
            $originalPrice = 0;
            foreach ($request->products as $productData) {
                $product = \App\Models\Product::find($productData['id']);
                $quantity = $productData['quantity'] ?? 1;
                $originalPrice += $product->base_price * $quantity;
            }
            $package->original_price = $originalPrice;

            // Sync products
            $productsToSync = [];
            foreach ($request->products as $productData) {
                $productsToSync[$productData['id']] = ['quantity' => $productData['quantity'] ?? 1];
            }
            $package->products()->sync($productsToSync);
        }

        // Update other fields
        $package->update($request->only([
            'name', 'slug', 'description', 'package_price', 'images',
            'is_active', 'stock', 'starts_at', 'ends_at'
        ]));

        // Recalculate discount
        $package->calculateDiscount();
        $package->save();

        return response()->json([
            'message' => 'Package deal updated successfully',
            'data' => $package->load('products')
        ]);
    }

    /**
     * Delete a package deal (admin only)
     */
    public function destroy($id)
    {
        $package = PackageDeal::findOrFail($id);
        $package->delete();

        return response()->json([
            'message' => 'Package deal deleted successfully'
        ]);
    }

    /**
     * Get featured package deals (limited to 6)
     */
    public function featured()
    {
        $packages = PackageDeal::available()
            ->with(['products' => function ($query) {
                $query->select('products.id', 'products.name', 'products.slug', 'products.images');
            }])
            ->orderBy('discount_percentage', 'desc')
            ->limit(6)
            ->get();

        return response()->json([
            'data' => $packages
        ]);
    }
}
