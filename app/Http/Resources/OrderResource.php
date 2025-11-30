<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference_number' => $this->reference_number ?? 'REF-' . date('Ymd', strtotime($this->created_at)) . '-' . str_pad($this->id, 4, '0', STR_PAD_LEFT),
            'order_number' => $this->order_number,
            'status' => $this->status,
            'total_amount' => $this->total_amount,
            'discount_amount' => $this->discount_amount ?? 0,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'tracking_number' => $this->tracking_number,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }, null),
            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {
                    $variant = $item->productVariant; // may be null if soft-deleted or missing
                    $product = $variant?->product;
                    return [
                        'id' => $item->id,
                        'order_id' => $item->order_id,
                        'product_variant_id' => $item->product_variant_id,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'subtotal' => $item->subtotal,
                        'product_variant' => $variant ? [
                            'id' => $variant->id,
                            'product_id' => $variant->product_id,
                            'size' => $variant->size,
                            'color' => $variant->color,
                            'price' => $variant->price,
                            'product' => $product ? [
                                'id' => $product->id,
                                'name' => $product->name,
                                'slug' => $product->slug,
                                'image_url' => $product->image_url,
                                'description' => $product->description,
                            ] : null,
                        ] : null,
                    ];
                });
            }, []),
            'shipping_address' => $this->whenLoaded('shippingAddress', function () {
                $addr = $this->shippingAddress;
                if (!$addr) {
                    return null;
                }
                return [
                    'id' => $addr->id,
                    'first_name' => $addr->first_name,
                    'last_name' => $addr->last_name,
                    'street' => $addr->street,
                    'city' => $addr->city,
                    'state' => $addr->state,
                    'zip_code' => $addr->zip_code,
                    'country' => $addr->country,
                    'phone' => $addr->phone,
                ];
            }, null),
        ];
    }
}
