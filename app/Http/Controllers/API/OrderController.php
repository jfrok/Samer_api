<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\Discount;
use App\Notifications\OrderCreatedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\OwnerOrderCreatedNotification;

class OrderController extends Controller
{
    public function __construct()
    {
        // Only apply auth to user methods that require authentication
        // Admin methods are protected by route middleware in api.php
        // store method now supports both authenticated and guest users
        $this->middleware('auth:sanctum')->only(['index', 'show', 'showByReference']);
    }

    public function index()
    {
        $orders = Order::where('user_id', Auth::id())
            ->with([
                'items.productVariant.product',
                'shippingAddress'
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return OrderResource::collection($orders);
    }

    // Admin methods
    public function adminIndex(Request $request)
    {
        $query = Order::with([
            'user',
            'items.productVariant.product',
            'shippingAddress'
        ]);

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by payment status
        if ($request->has('payment_status') && $request->payment_status) {
            $query->where('payment_status', $request->payment_status);
        }

        // Search by order number or customer name
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', '%' . $search . '%')
                  ->orWhere('reference_number', 'like', '%' . $search . '%')
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', '%' . $search . '%')
                                ->orWhere('email', 'like', '%' . $search . '%');
                  });
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(20);

        return OrderResource::collection($orders);
    }

    public function adminShow(Order $order)
    {
        $order->load([
            'user',
            'items.productVariant.product',
            'shippingAddress'
        ]);

        return new OrderResource($order);
    }

    public function adminUpdate(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'nullable|in:pending,processing,shipped,delivered,cancelled',
            'payment_status' => 'nullable|in:pending,paid,failed'
        ]);

        $order->update($request->only(['status', 'payment_status']));

        $order->load([
            'user',
            'items.productVariant.product',
            'shippingAddress'
        ]);

        return new OrderResource($order);
    }

    /**
     * Soft delete an order (admin only).
     */
    public function adminSoftDelete(Order $order)
    {
        // Soft delete the order; items remain for audit and can reference the trashed order
        $order->delete();
        return response()->json(['message' => 'Order soft-deleted successfully']);
    }

    public function show(Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        $order->load([
            'items.productVariant.product',
            'shippingAddress'
        ]);

        return new OrderResource($order);
    }

    /**
     * Show an order by its reference number for authenticated user.
     */
    public function showByReference($reference)
    {
        $order = Order::with([
            'items.productVariant.product',
            'shippingAddress'
        ])->where('reference_number', $reference)->firstOrFail();

        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        return new OrderResource($order);
    }

    /**
     * Public: Show an order by its reference number without authentication.
     * Intended for tracking links sent by email. Be cautious exposing sensitive data.
     */
    public function publicShowByReference($reference)
    {
        $order = Order::with([
            'items.productVariant.product',
            'shippingAddress'
        ])->where('reference_number', $reference)->firstOrFail();

        return new OrderResource($order);
    }

    public function store(Request $request)
    {
        $request->validate([
            'shipping_address' => 'required|array',
            'shipping_address.firstName' => 'required|string|max:100',
            'shipping_address.lastName' => 'required|string|max:100',
            'shipping_address.email' => 'required|email|max:255',
            'shipping_address.phone' => ['nullable','string','max:20','regex:/^[0-9+\-\s()]+$/'],
            'shipping_address.address' => 'required|string|max:255',
            'shipping_address.city' => 'required|string|max:100',
            'shipping_address.postalCode' => 'nullable|string|max:20',
            'payment_method' => 'required|in:card,cash',
            'discount_code' => 'nullable|string|max:100',
            'cart_items' => 'required|array|min:1',
            'cart_items.*.product_id' => 'required|integer|exists:products,id',
            'cart_items.*.product_variant_id' => 'nullable|integer|exists:product_variants,id',
            'cart_items.*.quantity' => 'required|integer|min:1|max:100',
            'cart_items.*.price' => 'required|numeric|min:0|max:1000000',
        ]);

        $user = Auth::user(); // Will be null for guest users
        $isGuest = !$user;

        $discount = null;
        if ($request->discount_code) {
            $discount = Discount::active()->where('code', $request->discount_code)->first();
            // Only validate existence now; applicability checked after computing total
            if (!$discount) {
                return response()->json(['error' => 'Invalid discount'], 400);
            }
        }

        // Get cart items from request body instead of database
        // The frontend will send the cart items with the order
        $cartItems = $request->input('cart_items', []);

        if (empty($cartItems)) {
            return response()->json(['error' => 'Cart is empty'], 400);
        }

        DB::beginTransaction();
        try {
            // Calculate totals
            $total = 0;
            foreach ($cartItems as $item) {
                $price = $item['price'] ?? 0;
                $quantity = $item['quantity'] ?? 1;
                $total += $price * $quantity;
            }

            if ($discount && !$discount->isApplicable($total)) {
                DB::rollBack();
                return response()->json(['error' => 'Discount not applicable'], 400);
            }

            $discountAmount = $discount ? $discount->calculateDiscount($total) : 0;
            // Add city-based shipping fee if available
            $shippingFee = 0;
            try {
                $cityName = $request->input('shipping_address.city');
                if ($cityName) {
                    $city = \App\Models\City::where('country', 'IQ')
                        ->where('is_active', true)
                        ->where(function($q) use ($cityName) {
                            $q->where('name', $cityName)
                              ->orWhere('name', 'like', $cityName);
                        })
                        ->first();
                    if ($city) {
                        $shippingFee = (float) $city->shipping_price;
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('City lookup failed: ' . $e->getMessage());
            }

            $finalTotal = ($total - $discountAmount) + $shippingFee;

            // Create shipping address (user_id is null for guest orders)
            $shippingAddress = \App\Models\Address::create([
                'user_id' => $user?->id, // null for guest users
                'street' => $request->input('shipping_address.address'),
                'city' => $request->input('shipping_address.city'),
                'state' => $request->input('shipping_address.city'), // Use city as state for now
                'zip_code' => $request->input('shipping_address.postalCode', '00000'),
                'country' => 'IQ', // Default to Iraq
                'is_default' => false,
            ]);

            // Extract customer details for order record
            $firstName = $request->input('shipping_address.firstName');
            $lastName = $request->input('shipping_address.lastName');
            $email = $request->input('shipping_address.email');
            $phone = $request->input('shipping_address.phone');

            // Create order (user_id is null for guest orders)
            $order = Order::create([
                'user_id' => $user?->id, // null for guest users
                'order_number' => 'ORD-' . strtoupper(uniqid()),
                'reference_number' => 'REF-' . now()->format('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT),
                'status' => $request->payment_method === 'cash' ? 'pending' : 'processing',
                'total_amount' => $finalTotal,
                'discount_amount' => $discountAmount,
                'shipping_address_id' => $shippingAddress->id,
                'phone' => $phone,
                'payment_method' => $request->payment_method,
                'payment_status' => $request->payment_method === 'cash' ? 'pending' : 'paid',
                // Store customer details for guest orders
                'customer_first_name' => $firstName,
                'customer_last_name' => $lastName,
                'customer_email' => $email,
            ]);

            // Create order items
            foreach ($cartItems as $cartItem) {
                $productId = $cartItem['product_id'] ?? null;
                $variantId = $cartItem['product_variant_id'] ?? null;

                // If no variant specified, get the first variant for this product
                if (!$variantId && $productId) {
                    $variant = ProductVariant::where('product_id', $productId)->first();
                    if ($variant) {
                        $variantId = $variant->id;
                    }
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $variantId,
                    'quantity' => $cartItem['quantity'] ?? 1,
                    'price' => $cartItem['price'] ?? 0,
                    'subtotal' => ($cartItem['price'] ?? 0) * ($cartItem['quantity'] ?? 1),
                ]);
            }

            // Clear cart - remove this since we don't have cart_items table
            // Cart will be cleared on the frontend after successful order

            // Increment discount uses
            if ($discount) {
                $discount->increment('uses_count');
            }

            DB::commit();

            // Send order created notification to the customer
            try {
                if ($order->user) {
                    $order->user->notify(new OrderCreatedNotification($order));
                }
            } catch (\Exception $e) {
                Log::error('Order notification failed: ' . $e->getMessage());
            }

            // Send order created email to the store owner
            try {
                $ownerEmail = config('mail.owner_email') ?? env('OWNER_EMAIL');
                if ($ownerEmail) {
                    Notification::route('mail', $ownerEmail)
                        ->notify(new OwnerOrderCreatedNotification($order));
                } else {
                    Log::warning('Owner email not configured. Set MAIL_OWNER_EMAIL or OWNER_EMAIL env.');
                }
            } catch (\Exception $e) {
                Log::error('Owner order notification failed: ' . $e->getMessage());
            }

            return response()->json([
                'message' => 'Order created successfully',
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'total_amount' => $order->total_amount,
                    'payment_method' => $order->payment_method,
                    'created_at' => $order->created_at,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order creation failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create order: ' . $e->getMessage()], 500);
        }
    }
}
