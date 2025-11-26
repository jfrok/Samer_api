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
                    return [
                        'id' => $item->id,
                        'order_id' => $item->order_id,
                        'product_variant_id' => $item->product_variant_id,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'subtotal' => $item->subtotal,
                        'product_variant' => [
                            'id' => $item->productVariant->id,
                            'product_id' => $item->productVariant->product_id,
                            'size' => $item->productVariant->size,
                            'color' => $item->productVariant->color,
                            'price' => $item->productVariant->price,
                            'product' => [
                                'id' => $item->productVariant->product->id,
                                'name' => $item->productVariant->product->name,
                                'slug' => $item->productVariant->product->slug,
                                'image_url' => $item->productVariant->product->image_url,
                                'description' => $item->productVariant->product->description,
                            ],
                        ],
                    ];
                });
            }, []),
            'shipping_address' => $this->whenLoaded('shippingAddress', function () {
                return [
                    'id' => $this->shippingAddress->id,
                    'first_name' => $this->shippingAddress->first_name,
                    'last_name' => $this->shippingAddress->last_name,
                    'street' => $this->shippingAddress->street,
                    'city' => $this->shippingAddress->city,
                    'state' => $this->shippingAddress->state,
                    'zip_code' => $this->shippingAddress->zip_code,
                    'country' => $this->shippingAddress->country,
                    'phone' => $this->shippingAddress->phone,
                ];
            }, null),
        ];
    }
}
