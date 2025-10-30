<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    /**
     * Get all categories with hierarchical structure
     */
    public function index()
    {
        try {
            // Cache categories for 1 hour
            $categories = Cache::remember('categories_tree', 3600, function () {
                return $this->buildCategoryTree();
            });

            return response()->json($categories);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch categories',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Build hierarchical category tree
     */
    private function buildCategoryTree()
    {
        // Get all categories
        $allCategories = Category::orderBy('name')->get();

        // Get parent categories (no parent_id)
        $parentCategories = $allCategories->whereNull('parent_id');

        $tree = [];

        foreach ($parentCategories as $parent) {
            $categoryData = [
                'id' => $parent->id,
                'name' => $parent->name,
                'slug' => $parent->slug,
                'description' => $parent->description,
                'children' => []
            ];

            // Get children for this parent
            $children = $allCategories->where('parent_id', $parent->id);

            foreach ($children as $child) {
                $categoryData['children'][] = [
                    'id' => $child->id,
                    'name' => $child->name,
                    'slug' => $child->slug,
                    'description' => $child->description,
                    'parent_id' => $child->parent_id
                ];
            }

            $tree[] = $categoryData;
        }

        return $tree;
    }

    /**
     * Clear categories cache
     */
    public function clearCache()
    {
        Cache::forget('categories_tree');

        return response()->json([
            'message' => 'Categories cache cleared successfully'
        ]);
    }

    /**
     * Get a single category with its children
     */
    public function show($id)
    {
        try {
            $category = Category::with('children')->findOrFail($id);

            return response()->json([
                'data' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'parent_id' => $category->parent_id,
                    'children' => $category->children->map(function ($child) {
                        return [
                            'id' => $child->id,
                            'name' => $child->name,
                            'slug' => $child->slug,
                            'description' => $child->description,
                            'parent_id' => $child->parent_id
                        ];
                    })
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Category not found',
                'message' => $e->getMessage()
            ], 404);
        }
    }
}
