<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ReviewController extends Controller
{
    /**
     * Get all reviews for a product
     */
    public function index(Request $request, $productId)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $sortBy = $request->input('sort_by', 'recent'); // recent, rating_high, rating_low, verified

            $query = Review::where('product_id', $productId)
                ->approved()
                ->with(['user:id,name']);

            // Apply sorting
            switch ($sortBy) {
                case 'rating_high':
                    $query->byRating('desc');
                    break;
                case 'rating_low':
                    $query->byRating('asc');
                    break;
                case 'verified':
                    $query->verifiedPurchase()->recent();
                    break;
                case 'recent':
                default:
                    $query->recent();
                    break;
            }

            $reviews = $query->paginate($perPage);

            // Calculate average rating and counts
            $stats = Review::where('product_id', $productId)
                ->approved()
                ->selectRaw('
                    AVG(rating) as average_rating,
                    COUNT(*) as total_reviews,
                    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
                ')
                ->first();

            return response()->json([
                'data' => $reviews->items(),
                'meta' => [
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                    'per_page' => $reviews->perPage(),
                    'total' => $reviews->total(),
                ],
                'stats' => [
                    'average_rating' => round($stats->average_rating ?? 0, 1),
                    'total_reviews' => $stats->total_reviews ?? 0,
                    'rating_distribution' => [
                        5 => $stats->five_star ?? 0,
                        4 => $stats->four_star ?? 0,
                        3 => $stats->three_star ?? 0,
                        2 => $stats->two_star ?? 0,
                        1 => $stats->one_star ?? 0,
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch reviews',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new review
     * Protected by authentication and rate limiting
     */
    public function store(Request $request)
    {
        try {
            // Validate input
            $validator = Validator::make($request->all(), [
                'product_id' => 'required|exists:products,id',
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'required|string|min:10|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();
            $productId = $request->product_id;

            // Check if user already reviewed this product
            $existingReview = Review::where('user_id', $user->id)
                ->where('product_id', $productId)
                ->first();

            if ($existingReview) {
                return response()->json([
                    'error' => 'You have already reviewed this product',
                    'message' => 'You can only submit one review per product. Please edit your existing review instead.'
                ], 409);
            }

            // Check if user purchased the product (optional but recommended)
            // Note: order_items uses product_variant_id, so we need to join through product_variants
            $hasPurchased = Order::where('user_id', $user->id)
                ->where('status', 'completed')
                ->whereHas('items', function ($query) use ($productId) {
                    $query->whereHas('productVariant', function ($variantQuery) use ($productId) {
                        $variantQuery->where('product_id', $productId);
                    });
                })
                ->exists();

            // Create review
            $review = Review::create([
                'user_id' => $user->id,
                'product_id' => $productId,
                'rating' => $request->rating,
                'comment' => strip_tags($request->comment), // Remove HTML tags for security
                'is_verified_purchase' => $hasPurchased,
                'is_approved' => true, // Auto-approve, or set to false for manual moderation
            ]);

            // Clear product cache
            Cache::forget("product_{$productId}");

            // Load user relationship
            $review->load('user:id,name');

            return response()->json([
                'message' => 'Review submitted successfully',
                'data' => $review
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create review',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a review (user can only update their own review)
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'rating' => 'sometimes|integer|min:1|max:5',
                'comment' => 'sometimes|string|min:10|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();
            $review = Review::findOrFail($id);

            // Check if user owns this review
            if ($review->user_id !== $user->id) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'You can only update your own reviews'
                ], 403);
            }

            // Update review
            if ($request->has('rating')) {
                $review->rating = $request->rating;
            }
            if ($request->has('comment')) {
                $review->comment = strip_tags($request->comment);
            }

            $review->save();

            // Clear cache
            Cache::forget("product_{$review->product_id}");

            $review->load('user:id,name');

            return response()->json([
                'message' => 'Review updated successfully',
                'data' => $review
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update review',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a review (user can only delete their own review)
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            $review = Review::findOrFail($id);

            // Check if user owns this review
            if ($review->user_id !== $user->id) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'You can only delete your own reviews'
                ], 403);
            }

            $productId = $review->product_id;
            $review->delete();

            // Clear cache
            Cache::forget("product_{$productId}");

            return response()->json([
                'message' => 'Review deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete review',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if user can review a product
     */
    public function canReview(Request $request, $productId)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'can_review' => false,
                    'reason' => 'Please log in to write a review'
                ]);
            }

            // Check if product exists
            $product = Product::find($productId);
            if (!$product) {
                return response()->json([
                    'can_review' => false,
                    'reason' => 'Product not found'
                ], 404);
            }

            // Check if user already reviewed
            $existingReview = Review::where('user_id', $user->id)
                ->where('product_id', $productId)
                ->first();

            if ($existingReview) {
                return response()->json([
                    'can_review' => false,
                    'reason' => 'You have already reviewed this product',
                    'existing_review' => $existingReview
                ]);
            }

            // Check if user purchased the product
            $hasPurchased = Order::where('user_id', $user->id)
                ->where('status', 'completed')
                ->whereHas('items', function ($query) use ($productId) {
                    $query->whereHas('productVariant', function ($variantQuery) use ($productId) {
                        $variantQuery->where('product_id', $productId);
                    });
                })
                ->exists();

            return response()->json([
                'can_review' => true,
                'has_purchased' => $hasPurchased,
                'message' => $hasPurchased ? 'You can review this product' : 'You can review this product (not a verified purchase)'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to check review status',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
