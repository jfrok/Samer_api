<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function add(Request $request)
    {
        $request->validate([
            'product_variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $variant = ProductVariant::findOrFail($request->product_variant_id);
        if ($variant->stock < $request->quantity) {
            return response()->json(['error' => 'Insufficient stock'], 400);
        }

        $userId = Auth::id() ?? $request->session()->get('cart_user_id', null);  // Handle guests via session

        $cartItem = Cart::updateOrCreate(
            ['user_id' => $userId, 'product_variant_id' => $request->product_variant_id],
            ['quantity' => $request->quantity]
        );

        return response()->json($cartItem->load('productVariant'));
    }

    public function index()
    {
        $userId = Auth::id();
        $carts = Cart::where('user_id', $userId)->with('productVariant.product')->get();

        return response()->json($carts);
    }

    public function remove($id)
    {
        $userId = Auth::id();
        $cart = Cart::where('user_id', $userId)->where('id', $id)->firstOrFail();
        $cart->delete();

        return response()->json(['message' => 'Item removed']);
    }
}
