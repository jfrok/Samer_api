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
                    $featuredImage = $product ? $product->getFirstMedia('gallery') : null;
                    $productName = $item->product_name ?: ($product?->name);
                    $productSlug = $item->product_slug ?: ($product?->slug);
                    $productImageSrc = $item->product_image_src ?: ($featuredImage ? $featuredImage->getUrl('medium') : null);
                    $productImageThumb = $featuredImage ? $featuredImage->getUrl('thumb') : $item->product_image_src;
                    $variantSize = $item->variant_size ?: ($variant?->size);
                    $variantColor = $item->variant_color ?: ($variant?->color);
                    $variantSku = $item->variant_sku ?: ($variant?->sku);

                    return [
                        'id' => $item->id,
                        'order_id' => $item->order_id,
                        'product_variant_id' => $item->product_variant_id,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'subtotal' => $item->subtotal,
                        'product_variant' => [
                            'id' => $variant?->id ?? $item->product_variant_id,
                            'product_id' => $variant?->product_id,
                            'size' => $variantSize,
                            'color' => $variantColor,
                            'sku' => $variantSku,
                            'price' => $item->price,
                            'product' => [
                                'id' => $product?->id,
                                'name' => $productName,
                                'slug' => $productSlug,
                                'image_src' => $productImageSrc,
                                'image_thumb' => $productImageThumb,
                                'description' => $product?->description,
                            ],
                        ],
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
                    'first_name' => $this->customer_first_name,
                    'last_name' => $this->customer_last_name,
                    'email' => $this->customer_email,
                    'street' => $addr->street,
                    'closest_point' => $addr->closest_point,
                    'city' => $addr->city,
                    'state' => $addr->state,
                    'zip_code' => $addr->zip_code,
                    'country' => $addr->country,
                    'phone' => $this->phone,
                ];
            }, null),
        ];
    }
}
