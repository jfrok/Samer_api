<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Category;  // Add this import for fallback

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $cacheKey = 'products_index';
        $search = $request->get('search');
        $categoryId = $request->get('category_id');

        // If search or filter, bypass product cache and query DB
        if ($search || $categoryId) {
            $query = Product::active();
            if ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            }
            if ($categoryId) {
                // Enhanced: Get all category IDs including sub-categories from cache
                $categoryIds = $this->getCategoryIds($categoryId);
                $query->whereIn('category_id', $categoryIds);
            }
            // Eager load with inStock scope
            $products = $query->with([
                'category',
                'variants' => function ($q) {
                    $q->inStock();
                }
            ])->paginate(20);
        } else {
            // Use product cache for general listings
            $cachedProducts = Cache::remember($cacheKey, 3600, function () {
                return Product::active()->with([
                    'category',
                    'variants' => function ($query) {
                        $query->inStock();
                    }
                ])->get();
            });
            $products = $cachedProducts->forPage(1, 20);  // Manual pagination from cache
        }

        return ProductResource::collection($products);
    }

    public function show($slug)
    {
        try {
            $product = Product::where('slug', $slug)->firstOrFail();  // Explicitly use slug for binding
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
