<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'brand' => $this->brand,
            'base_price' => $this->base_price,
            'images' => $this->images,
            'slug' => $this->slug,
            'category' => new CategoryResource($this->category),
            'variants' => ProductVariantResource::collection($this->variants->inStock),
        ];
    }
}
