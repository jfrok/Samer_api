<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'size' => $this->size,
            'color' => $this->color,
            // Ensure numeric JSON types
            'price' => (float) $this->price,
            'stock' => (int) $this->stock,
            'sku' => $this->sku,
        ];
    }
}
