<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'brand' => $this->brand,
            'base_price' => (float) $this->base_price,
            'is_active' => (bool) $this->is_active,

            // Category relationship
            'category' => $this->whenLoaded('category', function () {
                return new CategoryResource($this->category);
            }),

            // Product variants
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),

            // Gallery images with all conversions
            'gallery' => $this->getGalleryImages(),

            // Metadata
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Get formatted gallery images with conversions
     */
    private function getGalleryImages(): array
    {
        return $this->getMedia('gallery')->map(function ($media) {
            return [
                'id' => $media->id,
                'uuid' => $media->uuid,
                'name' => $media->name,
                'file_name' => $media->file_name,
                'mime_type' => $media->mime_type,
                'size' => $media->size,
                'order' => $media->order_column,

                // Custom properties (alt text, caption, etc.)
                'custom_properties' => $media->custom_properties,
                'alt_text' => $media->getCustomProperty('alt_text', ''),
                'caption' => $media->getCustomProperty('caption', ''),

                // Original image URL
                'original_url' => $media->getUrl(),

                // Image conversions/sizes
                'conversions' => [
                    'thumb' => [
                        'url' => $media->getUrl('thumb'),
                        'width' => 300,
                        'height' => 300,
                    ],
                    'medium' => [
                        'url' => $media->getUrl('medium'),
                        'width' => 600,
                        'height' => 600,
                    ],
                    'large' => [
                        'url' => $media->getUrl('large'),
                        'width' => 1200,
                        'height' => 1200,
                    ],
                ],

                // Responsive images srcset (if enabled)
                'responsive' => $media->hasGeneratedConversion('thumb') ? [
                    'srcset' => $media->getSrcset('large'),
                    'sizes' => '(max-width: 600px) 300px, (max-width: 1200px) 600px, 1200px',
                ] : null,
            ];
        })->toArray();
    }
}
