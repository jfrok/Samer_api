<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $product = $this->productVariant->product;
        $featuredImage = $product->getFirstMedia('gallery');

        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'product_variant_id' => $this->product_variant_id,
            'product' => [
                'id' => $product->id,
                'title' => $product->name,
                'slug' => $product->slug,
                'price' => $this->productVariant->price,
                'image_src' => $featuredImage ? $featuredImage->getUrl('medium') : null,
                'image_thumb' => $featuredImage ? $featuredImage->getUrl('thumb') : null,
                'description' => $product->description,
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
