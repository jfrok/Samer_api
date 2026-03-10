<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaService
{
    /**
     * Check if a file with the same hash already exists
     */
    public function findDuplicateMedia(UploadedFile $file): ?Media
    {
        $hash = hash_file('sha256', $file->getRealPath());

        return Media::where('hash', $hash)->first();
    }

    /**
     * Calculate and store hash for uploaded media
     */
    public function addHashToMedia(Media $media, bool $isOriginalUpload = true): void
    {
        $path = $media->getPath();

        if (file_exists($path)) {
            $hash = hash_file('sha256', $path);
            $media->setCustomProperty('is_original_upload', $isOriginalUpload);
            $media->hash = $hash;
            $media->save();
        }
    }

    /**
     * Attach existing media to a product
     */
    public function attachExistingMedia(Product $product, int $mediaId, string $collection = 'gallery'): Media
    {
        $existingMedia = Media::findOrFail($mediaId);
        
        // Check if this product already has this image (by hash)
        if ($existingMedia->hash) {
            $alreadyAttached = $product->getMedia($collection)
                ->where('hash', $existingMedia->hash)
                ->first();
            
            if ($alreadyAttached) {
                // Image already attached to this product, return existing
                return $alreadyAttached;
            }
        }

        // Copy the media file to the product
        $newMedia = $product->addMedia($existingMedia->getPath())
            ->withCustomProperties([
                'alt_text' => $existingMedia->getCustomProperty('alt_text', ''),
                'caption' => $existingMedia->getCustomProperty('caption', ''),
                'is_original_upload' => false, // Mark as library attachment, not original
            ])
            ->preservingOriginal()
            ->toMediaCollection($collection);

        // Copy the hash
        if ($existingMedia->hash) {
            $newMedia->update(['hash' => $existingMedia->hash]);
        }

        return $newMedia;
    }

    /**
     * Get all media not attached to any model (orphaned)
     */
    public function getOrphanedMedia()
    {
        return Media::whereNull('model_type')->get();
    }

    /**
     * Get all unique media (by hash)
     */
    public function getAllUniqueMedia()
    {
        return Media::select('media.*')
            ->selectRaw('COUNT(DISTINCT model_id) as usage_count')
            ->groupBy('hash')
            ->havingRaw('hash IS NOT NULL')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get products using a specific media
     */
    public function getMediaUsage(int $mediaId)
    {
        $media = Media::findOrFail($mediaId);

        return Media::where('hash', $media->hash)
            ->with('model')
            ->get()
            ->map(function ($item) {
                return [
                    'model_type' => $item->model_type,
                    'model_id' => $item->model_id,
                    'model' => $item->model,
                ];
            });
    }

    /**
     * Delete media and check if it can be safely deleted
     */
    public function safeDeleteMedia(int $mediaId): array
    {
        $media = Media::findOrFail($mediaId);

        // Check how many products use this image
        $usageCount = Media::where('hash', $media->hash)->count();

        if ($usageCount > 1) {
            // Just delete this instance, others still exist
            $media->delete();
            return [
                'deleted' => true,
                'message' => 'Media removed from product. Image still used by other products.',
                'still_in_use' => true,
            ];
        }

        // Last instance, delete completely
        $media->delete();
        return [
            'deleted' => true,
            'message' => 'Media deleted completely.',
            'still_in_use' => false,
        ];
    }
}
