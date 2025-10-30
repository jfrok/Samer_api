<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'product_variant_id' => $this->product_variant_id,
            'product' => [
                'id' => $this->productVariant->product->id,
                'title' => $this->productVariant->product->name,
                'slug' => $this->productVariant->product->slug,
                'price' => $this->productVariant->price,
                'image_src' => $this->productVariant->product->image_url ?? '/api/placeholder/300/300',
                'description' => $this->productVariant->product->description,
            ],
            'variant' => [
                'id' => $this->productVariant->id,
                'size' => $this->productVariant->size,
                'color' => $this->productVariant->color,
                'price' => $this->productVariant->price,
                'stock' => $this->productVariant->stock,
                'sku' => $this->productVariant->sku,
            ],
            'subtotal' => $this->productVariant->price * $this->quantity,
        ];
    }
}
