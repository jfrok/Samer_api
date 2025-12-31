<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LikedProductController extends Controller
{
    /**
     * Get the authenticated user's liked products
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $likedProducts = $user->likedProducts()
            ->with(['category', 'variants'])
            ->active()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $likedProducts,
            'count' => $likedProducts->count()
        ]);
    }

    /**
     * Like a product
     */
    public function store(Request $request, $productId): JsonResponse
    {
        $user = $request->user();
        $product = Product::findOrFail($productId);

        // Check if already liked
        if ($user->likedProducts()->where('product_id', $productId)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product already liked'
            ], 400);
        }

        $user->likedProducts()->attach($productId);

        return response()->json([
            'status' => 'success',
            'message' => 'Product liked successfully',
            'data' => $product
        ]);
    }

    /**
     * Unlike a product
     */
    public function destroy(Request $request, $productId): JsonResponse
    {
        $user = $request->user();

        // Check if liked
        if (!$user->likedProducts()->where('product_id', $productId)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not liked'
            ], 400);
        }

        $user->likedProducts()->detach($productId);

        return response()->json([
            'status' => 'success',
            'message' => 'Product unliked successfully'
        ]);
    }

    /**
     * Check if a product is liked by the authenticated user
     */
    public function check(Request $request, $productId): JsonResponse
    {
        $user = $request->user();
        $isLiked = $user->likedProducts()->where('product_id', $productId)->exists();

        return response()->json([
            'status' => 'success',
            'is_liked' => $isLiked
        ]);
    }
}
