<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Check if variants relationship is loaded and filter for in-stock only
        $inStockVariants = $this->whenLoaded('variants', function () {
            return $this->variants->filter(function ($variant) {
                return $variant->stock > 0;
            });
        }, collect());

        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'brand' => $this->brand,
            // Ensure numeric JSON types
            'base_price' => (float) $this->base_price,
            'images' => $this->images,
            'slug' => $this->slug,
            'is_active' => $this->is_active,
            'category' => $this->whenLoaded('category', function () {
                return new CategoryResource($this->category);
            }),
            'variants' => ProductVariantResource::collection($inStockVariants),
        ];
    }
}
