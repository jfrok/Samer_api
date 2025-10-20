<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use App\Models\ProductVariant;
use App\Models\Discount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index()
    {
        $orders = Auth::user()->orders()->with('items.productVariant.product', 'shippingAddress')->paginate(10);

        return OrderResource::collection($orders);
    }

    public function show(Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        $order->load('items.productVariant.product', 'shippingAddress', 'payment');

        return new OrderResource($order);
    }

    public function store(Request $request)
    {
        $request->validate([
            'shipping_address_id' => 'required|exists:addresses,id',
            'payment_method' => 'required|string',
            'discount_code' => 'nullable|string',
        ]);

        $user = Auth::user();
        $discount = null;
        if ($request->discount_code) {
            $discount = Discount::active()->where('code', $request->discount_code)->first();
            if (!$discount || !$discount->isApplicable(0)) {  // Validate later with total
                return response()->json(['error' => 'Invalid discount'], 400);
            }
        }

        // Get cart items
        $cartItems = $user->cartItems()->with('productVariant')->get();
        if ($cartItems->isEmpty()) {
            return response()->json(['error' => 'Cart is empty'], 400);
        }

        DB::transaction(function () use ($request, $user, $cartItems, $discount) {
            $total = 0;
            foreach ($cartItems as $item) {
                $total += $item->productVariant->price * $item->quantity;
            }

            if ($discount && !$discount->isApplicable($total)) {
                throw new \Exception('Discount not applicable');
            }

            $discountAmount = $discount ? $discount->calculateDiscount($total) : 0;
            $finalTotal = $total - $discountAmount;

            // Create order
            $order = Order::create([
                'user_id' => $user->id,
                'status' => 'pending',
                'total_amount' => $finalTotal,
                'discount_amount' => $discountAmount,
                'shipping_address_id' => $request->shipping_address_id,
                'payment_method' => $request->payment_method,
            ]);

            // Create order items and update stock
            foreach ($cartItems as $cartItem) {
                $variant = $cartItem->productVariant;
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $cartItem->quantity,
                    'price' => $variant->price,
                ]);

                $variant->decrement('stock', $cartItem->quantity);
            }

            // Clear cart
            $user->cartItems()->delete();

            // Increment discount uses
            if ($discount) {
                $discount->increment('uses_count');
            }

            // TODO: Create payment record and integrate gateway (e.g., Stripe)
        });

        return response()->json(['message' => 'Order created', 'order' => $order], 201);
    }
}
