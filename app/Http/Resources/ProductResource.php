<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * Lightweight resource for listings.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Get one featured image (first gallery image)
        $featuredImage = $this->getFirstMedia('gallery');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->when(
                $request->query('include_description'),
                \Str::limit($this->description, 150)
            ),
            'brand' => $this->brand,
            'base_price' => (float) $this->base_price,
            'is_active' => $this->is_active,

            // Category
            'category' => $this->whenLoaded('category', fn() => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),

            // Featured image (optimized for listing)
            'featured_image' => $featuredImage ? [
                'id' => $featuredImage->id,
                'thumb' => $featuredImage->getUrl('thumb'),
                'medium' => $featuredImage->getUrl('medium'),
                'alt_text' => $featuredImage->getCustomProperty('alt_text', $this->name),
            ] : null,

            // Gallery count
            'gallery_count' => $this->getMedia('gallery')->count(),

            // Variant summary
            'variants_count' => $this->whenLoaded('variants', fn() => $this->variants->count()),
            'price_range' => $this->whenLoaded('variants', function () {
                if ($this->variants->isEmpty()) {
                    return null;
                }

                $prices = $this->variants->pluck('price');
                $min = $prices->min();
                $max = $prices->max();

                return [
                    'min' => (float) $min,
                    'max' => (float) $max,
                    'formatted' => $min == $max ? "{$min}" : "{$min} - {$max}",
                ];
            }),

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
