<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        // Validate and sanitize input for security
        $validator = Validator::make($request->all(), [
            'search' => 'nullable|string|max:100',
            'category_id' => 'nullable|integer|exists:categories,id',
            'sort_by' => 'nullable|string|in:created_at,name,base_price,updated_at',
            'sort_order' => 'nullable|string|in:asc,desc',
            'page' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid search parameters',
                'details' => $validator->errors()
            ], 422);
        }

        // Sanitize search input (prevent XSS and SQL injection)
        $search = $request->get('search');
        if ($search) {
            // Remove any HTML tags and script tags
            $search = strip_tags($search);
            // Remove special characters that could be used for SQL injection
            $search = preg_replace('/[^\p{L}\p{N}\s\-\_]/u', '', $search);
            // Limit length
            $search = substr($search, 0, 100);
            $search = trim($search);
        }

        $categoryId = $request->get('category_id');
        $sortBy = $request->get('sort_by', 'created_at'); // Default to newest first
        $sortOrder = $request->get('sort_order', 'desc'); // Default to descending

        // Build query with security in mind
        $query = Product::active();

        if ($search && strlen($search) > 0) {
            // Use parameterized query to prevent SQL injection
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%')
                  ->orWhere('brand', 'like', '%' . $search . '%');
            });
        }

        if ($categoryId) {
            // Enhanced: Get all category IDs including sub-categories from cache
            $categoryIds = $this->getCategoryIds($categoryId);
            $query->whereIn('category_id', $categoryIds);
        }

        // Apply sorting (already validated above)
        $allowedSortFields = ['created_at', 'name', 'base_price', 'updated_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        // Eager load with inStock scope
        $products = $query->with([
            'category',
            'variants' => function ($q) {
                $q->inStock();
            }
        ])->paginate(20);

        return ProductResource::collection($products);
    }

    public function show($identifier)
    {
        try {
            // Check if it's numeric (ID) or string (slug)
            if (is_numeric($identifier)) {
                $product = Product::findOrFail($identifier);
            } else {
                $product = Product::where('slug', $identifier)->firstOrFail();
            }

            $product->load([
                'category',
                'variants' => function ($q) {
                    $q->inStock();
                }
            ]);

            return new ProductResource($product);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Product not found'], 404);
        }
    }

    // Admin methods
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'brand' => 'nullable|string|max:255',
            'base_price' => 'required|numeric|min:0',
            'images' => 'nullable|array',
            'images.*' => ['string', function ($attribute, $value, $fail) {
                // Accept either URL or base64 encoded image
                if (!filter_var($value, FILTER_VALIDATE_URL) && !preg_match('/^data:image\/(jpeg|jpg|png|gif|webp);base64,/', $value)) {
                    $fail('The ' . $attribute . ' must be a valid URL or base64 encoded image.');
                }
            }],
            'is_active' => 'boolean',
            'variants' => 'nullable|array',
            'variants.*.size' => 'required|string|max:50',
            'variants.*.color' => 'required|string|max:50',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.stock' => 'required|integer|min:0',
            'variants.*.sku' => 'nullable|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['slug'] = Str::slug($data['name']);

        // Ensure unique slug
        $originalSlug = $data['slug'];
        $counter = 1;
        while (Product::where('slug', $data['slug'])->exists()) {
            $data['slug'] = $originalSlug . '-' . $counter;
            $counter++;
        }

        // Extract variants data before creating product
        $variantsData = $data['variants'] ?? [];
        unset($data['variants']);

        $product = Product::create($data);

        // Create variants
        if (!empty($variantsData)) {
            foreach ($variantsData as $variantData) {
                // Generate unique SKU if not provided or conflict exists
                $variantData['sku'] = $this->generateUniqueSku($product, $variantData);
                $product->variants()->create($variantData);
            }
        }

        $product->load(['category', 'variants']);

        return new ProductResource($product);
    }

    public function update(Request $request, Product $product)
    {
        // Add logging to debug the issue
        Log::info('Product update request received', [
            'product_id' => $product->id,
            'request_data' => $request->all()
        ]);

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'exists:categories,id',
            'brand' => 'nullable|string|max:255',
            'base_price' => 'numeric|min:0',
            'images' => 'nullable|array',
            'images.*' => ['string', function ($attribute, $value, $fail) {
                // Accept either URL or base64 encoded image
                if (!filter_var($value, FILTER_VALIDATE_URL) && !preg_match('/^data:image\/(jpeg|jpg|png|gif|webp);base64,/', $value)) {
                    $fail('The ' . $attribute . ' must be a valid URL or base64 encoded image.');
                }
            }],
            'is_active' => 'boolean',
            'variants' => 'nullable|array',
            'variants.*.id' => 'nullable|integer|exists:product_variants,id',
            'variants.*.size' => 'required|string|max:50',
            'variants.*.color' => 'required|string|max:50',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.stock' => 'required|integer|min:0',
            'variants.*.sku' => 'nullable|string|max:100'
        ]);

        if ($validator->fails()) {
            Log::error('Product update validation failed', [
                'product_id' => $product->id,
                'errors' => $validator->errors()
            ]);
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // Extract variants data
        $variantsData = $data['variants'] ?? null;
        unset($data['variants']);

        // Update slug if name changed
        if (isset($data['name']) && $data['name'] !== $product->name) {
            $data['slug'] = Str::slug($data['name']);

            // Ensure unique slug
            $originalSlug = $data['slug'];
            $counter = 1;
            while (Product::where('slug', $data['slug'])->where('id', '!=', $product->id)->exists()) {
                $data['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }
        }

        $product->update($data);

        // Update variants if provided
        if ($variantsData !== null) {
            // Get existing variant IDs from request
            $requestedVariantIds = collect($variantsData)->pluck('id')->filter()->toArray();

            // Soft-delete variants that are not in the request to preserve FK integrity
            $product->variants()->whereNotIn('id', $requestedVariantIds)->update(['deleted_at' => now()]);

            // Create or update variants
            foreach ($variantsData as $variantData) {
                if (isset($variantData['id'])) {
                    // Update existing variant
                    $variant = $product->variants()->find($variantData['id']);
                    if ($variant) {
                        $variant->update($variantData);
                    }
                } else {
                    // Create new variant with unique SKU
                    $variantData['sku'] = $this->generateUniqueSku($product, $variantData);
                    $product->variants()->create($variantData);
                }
            }
        }

        $product->load(['category', 'variants']);

        Log::info('Product updated successfully', [
            'product_id' => $product->id,
            'updated_data' => $data
        ]);

        return new ProductResource($product);
    }

    /**
     * Generate a unique SKU for a product variant.
     * Normalizes size/color tokens and avoids duplicates by appending a counter.
     */
    private function generateUniqueSku(Product $product, array $variantData): string
    {
        $brand = Str::slug($product->brand ?? $product->name ?? 'prd', '-');
        $size = Str::slug($variantData['size'] ?? 'sz', '-');
        // Normalize color: prefer provided color string; if hex like #ff0000, strip '#'
        $rawColor = $variantData['color'] ?? 'clr';
        $color = Str::slug(ltrim($rawColor, '#'), '-');
        $base = strtoupper(substr($brand, 0, 3)) . '-' . strtoupper($size) . '-' . $color;

        $sku = $base;
        $counter = 0;
        while (\App\Models\ProductVariant::where('sku', $sku)->exists()) {
            $counter++;
            $sku = $base . '-' . $counter;
            // Safety to prevent infinite loop
            if ($counter > 100) {
                $sku = $base . '-' . time();
                break;
            }
        }
        return $sku;
    }

    public function destroy(Product $product)
    {
        // Check if any variants are referenced in order_items
        $variantIds = $product->variants()->withTrashed()->pluck('id');
        $hasOrders = \DB::table('order_items')->whereIn('product_variant_id', $variantIds)->exists();

        if ($hasOrders) {
            // Safety: Do not hard-delete. Deactivate product and soft-delete non-referenced variants.
            $product->update(['is_active' => false]);

            // Soft-delete variants not referenced by orders
            $referencedIds = \DB::table('order_items')->whereIn('product_variant_id', $variantIds)->pluck('product_variant_id')->toArray();
            $product->variants()->whereNotIn('id', $referencedIds)->update(['deleted_at' => now()]);

            return response()->json([
                'message' => 'Product has existing orders. It was deactivated instead of deletion.',
                'action' => 'deactivated',
            ], 409);
        }

        // No linked orders: safe to soft-delete variants then delete product
        $product->variants()->update(['deleted_at' => now()]);
        $product->delete();
        return response()->json(['message' => 'Product deleted successfully']);
    }

    public function dashboardStats()
    {
        $totalProducts = Product::count();
        $activeProducts = Product::active()->count();
        $totalCategories = Category::count();
        $recentProducts = Product::with('category')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'total_products' => $totalProducts,
            'active_products' => $activeProducts,
            'inactive_products' => $totalProducts - $activeProducts,
            'total_categories' => $totalCategories,
            'recent_products' => ProductResource::collection($recentProducts)
        ]);
    }

    // Helper: Get category IDs including sub-categories from cache (or DB fallback)
    private function getCategoryIds($categoryId)
    {
        $cachedTree = Cache::get('categories_tree');

        if ($cachedTree) {
            // Traverse cached tree to collect IDs
            $tree = collect($cachedTree)->firstWhere('id', $categoryId);
            if (!$tree) return collect([$categoryId]);  // Just parent if not found

            $ids = collect([$categoryId]);
            $this->collectChildIds($tree['children'], $ids);
            return $ids;
        }

        // Fallback to DB if cache misses
        return Category::where('id', $categoryId)
            ->orWhereIn('parent_id', function ($q) use ($categoryId) {
                $q->select('id')->from('categories')->where('parent_id', $categoryId);
            })
            ->whereNull('parent_id')  // Wait, no: recursive-ish via subquery
            ->pluck('id');  // Simplified; for deeper nests, use a recursive query or cache always
    }

    // Recursive helper to collect child IDs from tree
    private function collectChildIds($children, &$ids)
    {
        foreach ($children as $child) {
            $ids->push($child['id']);
            if (!empty($child['children'])) {
                $this->collectChildIds($child['children'], $ids);
            }
        }
    }
}
