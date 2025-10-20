<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'size' => $this->size,
            'color' => $this->color,
            'price' => $this->price,
            'stock' => $this->stock,
            'sku' => $this->sku,
            'product' => new ProductResource($this->whenLoaded('product')),  // Optional back-ref
        ];
    }
}
