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
                'parent_id' => null,
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

    /**
     * Store a newly created category
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:categories,name',
                'slug' => 'nullable|string|max:255|unique:categories,slug',
                'description' => 'nullable|string|max:1000',
                'parent_id' => 'nullable|integer|exists:categories,id'
            ]);

            // Auto-generate slug if not provided
            if (empty($validated['slug'])) {
                $validated['slug'] = \Illuminate\Support\Str::slug($validated['name']);
            }

            $category = Category::create($validated);

            // Clear categories cache
            Cache::forget('categories_tree');

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully',
                'data' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'parent_id' => $category->parent_id
                ]
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to create category',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified category
     */
    public function update(Request $request, $id)
    {
        try {
            $category = Category::findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:categories,name,' . $id,
                'slug' => 'nullable|string|max:255|unique:categories,slug,' . $id,
                'description' => 'nullable|string|max:1000',
                'parent_id' => 'nullable|integer|exists:categories,id'
            ]);

            // Prevent self-referencing parent
            if (isset($validated['parent_id']) && $validated['parent_id'] == $id) {
                return response()->json([
                    'success' => false,
                    'message' => 'A category cannot be its own parent'
                ], 422);
            }

            // Auto-generate slug if not provided
            if (empty($validated['slug'])) {
                $validated['slug'] = \Illuminate\Support\Str::slug($validated['name']);
            }

            $category->update($validated);

            // Clear categories cache
            Cache::forget('categories_tree');

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully',
                'data' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'parent_id' => $category->parent_id
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to update category',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified category
     */
    public function destroy($id)
    {
        try {
            $category = Category::findOrFail($id);

            // Check if category has products
            $productCount = $category->products()->count();
            if ($productCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete category. It has {$productCount} product(s) associated with it."
                ], 422);
            }

            // Check if category has children
            $childrenCount = $category->children()->count();
            if ($childrenCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete category. It has {$childrenCount} subcategory(ies)."
                ], 422);
            }

            $category->delete();

            // Clear categories cache
            Cache::forget('categories_tree');

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete category',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
