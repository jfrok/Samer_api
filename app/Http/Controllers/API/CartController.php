<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

class CartController extends Controller
{
    /**
     * Manually authenticate user using Sanctum token
     */
    private function authenticateUser(Request $request)
    {
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        $token = substr($authHeader, 7); // Remove "Bearer " prefix

        // Find the token in the personal_access_tokens table
        $accessToken = PersonalAccessToken::findToken($token);

        if (!$accessToken) {
            Log::info('Token not found in database', ['token_preview' => substr($token, 0, 10) . '...']);
            return null;
        }

        // Set the authenticated user
        Auth::setUser($accessToken->tokenable);
        Log::info('User authenticated manually', ['user_id' => $accessToken->tokenable->id]);

        return $accessToken->tokenable;
    }

    public function add(Request $request)
    {
        // Manually authenticate user
        $user = $this->authenticateUser($request);

        $authHeader = $request->header('Authorization');
        Log::info('CartController::add called', [
            'request_data' => $request->all(),
            'user_id' => $user ? $user->id : null,
            'is_authenticated' => $user !== null,
            'auth_header' => $authHeader ? 'Present' : 'Missing',
            'auth_header_preview' => $authHeader ? substr($authHeader, 0, 20) . '...' : null
        ]);

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_variant_id' => 'nullable|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $userId = $user ? $user->id : null;
        Log::info('User authentication status', ['user_id' => $userId, 'auth_check' => $user !== null]);

        // Resolve product and chosen variant (with fallback to first in-stock)
        $product = Product::findOrFail($request->product_id);
        if ($request->product_variant_id) {
            $variant = ProductVariant::withTrashed()->findOrFail($request->product_variant_id);
            if ($variant->product_id !== $product->id) {
                return response()->json(['error' => 'Variant does not belong to product'], 422);
            }
        } else {
            $variant = ProductVariant::where('product_id', $product->id)
                ->whereNull('deleted_at')
                ->where('stock', '>', 0)
                ->orderBy('id')
                ->first();
            if (!$variant) {
                return response()->json(['error' => 'No available variants for this product'], 422);
            }
        }

        // For guests, return a response without saving to database
        if (!$userId) {
            Log::info('Processing guest cart item');
            return response()->json([
                'message' => 'Item added to guest cart successfully',
                'guest_cart_item' => [
                    'id' => 'guest-' . $request->product_id . '-' . time(),
                    'quantity' => $request->quantity,
                    'product' => [
                        'id' => $product->id,
                        'title' => $product->name,
                        'slug' => $product->slug,
                        'price' => $variant->price,
                        'image_src' => $product->image_url ?? '/api/placeholder/300/300',
                        'description' => $product->description,
                    ],
                    'variant' => [
                        'id' => $variant->id,
                        'size' => $variant->size,
                        'color' => $variant->color,
                        'price' => $variant->price,
                        'stock' => $variant->stock,
                        'sku' => $variant->sku,
                    ],
                    'subtotal' => $variant->price * $request->quantity,
                ]
            ]);
        }

        // Use resolved variant for authenticated user flows
        $variantId = $variant->id;

        Log::info('Using variant', ['variant_id' => $variantId]);

        // Check stock
        if ($variant->stock < $request->quantity) {
            Log::warning('Insufficient stock', ['available' => $variant->stock, 'requested' => $request->quantity]);
            return response()->json(['error' => 'Insufficient stock'], 400);
        }

        // Check if item already exists in cart
        $existingCartItem = Cart::where('user_id', $userId)
            ->where('product_variant_id', $variantId)
            ->first();

        Log::info('Checking existing cart item', ['exists' => $existingCartItem ? 'yes' : 'no']);

        if ($existingCartItem) {
            // Update quantity
            $newQuantity = $existingCartItem->quantity + $request->quantity;

            if ($variant->stock < $newQuantity) {
                Log::warning('Insufficient stock for update', ['available' => $variant->stock, 'requested' => $newQuantity]);
                return response()->json(['error' => 'Insufficient stock'], 400);
            }

            $existingCartItem->update(['quantity' => $newQuantity]);
            $cartItem = $existingCartItem;
            Log::info('Updated existing cart item', ['cart_item_id' => $cartItem->id, 'new_quantity' => $newQuantity]);
        } else {
            // Create new cart item
            $cartItem = Cart::create([
                'user_id' => $userId,
                'product_variant_id' => $variantId,
                'quantity' => $request->quantity,
            ]);
            Log::info('Created new cart item', ['cart_item_id' => $cartItem->id]);
        }

        Log::info('Cart operation successful', ['cart_item_id' => $cartItem->id]);

        return response()->json([
            'message' => 'Item added to cart successfully',
            'cart_item' => new CartResource($cartItem->load('productVariant.product'))
        ]);
    }

    public function index(Request $request)
    {
        // Manually authenticate user
        $user = $this->authenticateUser($request);
        $userId = $user ? $user->id : null;

        Log::info('CartController::index called', ['user_id' => $userId, 'is_authenticated' => $user !== null]);

        // For guests, return empty cart
        if (!$userId) {
            Log::info('Returning empty cart for guest user');
            return response()->json([
                'items' => [],
                'total_items' => 0,
                'total_price' => 0
            ]);
        }

        $carts = Cart::where('user_id', $userId)
            ->with(['productVariant.product'])
            ->get();

        Log::info('Found cart items for user', ['user_id' => $userId, 'cart_count' => $carts->count()]);

        $cartData = [
            'items' => CartResource::collection($carts),
            'total_items' => $carts->sum('quantity'),
            'total_price' => $carts->sum(function($cart) {
                $variant = $cart->productVariant; // may be soft-deleted
                return $cart->quantity * ($variant->price ?? 0);
            })
        ];

        Log::info('Returning cart data', $cartData);

        return response()->json($cartData);
    }

    public function remove($id)
    {
        $userId = Auth::id();
        $cart = Cart::where('user_id', $userId)->where('id', $id)->firstOrFail();
        $cart->delete();

        return response()->json(['message' => 'Item removed from cart']);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $userId = Auth::id();
        $cart = Cart::where('user_id', $userId)->where('id', $id)->firstOrFail();

        $variant = $cart->productVariant;
        if ($variant->stock < $request->quantity) {
            return response()->json(['error' => 'Insufficient stock'], 400);
        }

        $cart->update(['quantity' => $request->quantity]);

        return response()->json([
            'message' => 'Cart item updated successfully',
            'cart_item' => new CartResource($cart->load('productVariant.product'))
        ]);
    }

    public function clear()
    {
        $userId = Auth::id();
        Cart::where('user_id', $userId)->delete();

        return response()->json(['message' => 'Cart cleared successfully']);
    }
}
