<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Filter variants collection for in-stock only (post-load)
        $inStockVariants = $this->variants->filter(function ($variant) {
            return $variant->stock > 0;  // Matches the inStock scope logic
        });

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'brand' => $this->brand,
            'base_price' => $this->base_price,
            'images' => $this->images,
            'slug' => $this->slug,
            'category' => new CategoryResource($this->category),
            'variants' => ProductVariantResource::collection($inStockVariants),  // Fixed: Filter collection here
        ];
    }
}
