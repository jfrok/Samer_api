<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaLibraryController extends Controller
{
    protected $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    /**
     * Get all media in the library (only original uploads, not library attachments)
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 20);
        $search = $request->get('search', '');

        $query = Media::with('model:id,name')
            ->whereRaw("JSON_EXTRACT(custom_properties, '$.is_original_upload') = true OR JSON_EXTRACT(custom_properties, '$.is_original_upload') IS NULL")
            ->orderBy('created_at', 'desc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('file_name', 'like', "%{$search}%");
            });
        }

        $media = $query->paginate($perPage);

        return response()->json([
            'data' => $media->map(function ($item) {
                // Count how many times this image is used (by hash)
                $usageCount = $item->hash
                    ? Media::where('hash', $item->hash)->count()
                    : 1;

                return [
                    'id' => $item->id,
                    'uuid' => $item->uuid,
                    'name' => $item->name,
                    'file_name' => $item->file_name,
                    'mime_type' => $item->mime_type,
                    'size' => $item->size,
                    'human_readable_size' => $item->human_readable_size,
                    'hash' => $item->hash,
                    'created_at' => $item->created_at,
                    'usage_count' => $usageCount,
                    'original_url' => $item->getUrl(),
                    'conversions' => [
                        'thumb' => $item->getUrl('thumb'),
                        'medium' => $item->getUrl('medium'),
                        'large' => $item->getUrl('large'),
                    ],
                    'custom_properties' => $item->custom_properties,
                    'attached_to' => $item->model ? [
                        'type' => class_basename($item->model_type),
                        'id' => $item->model_id,
                        'name' => $item->model->name ?? null,
                    ] : null,
                ];
            }),
            'pagination' => [
                'total' => $media->total(),
                'per_page' => $media->perPage(),
                'current_page' => $media->currentPage(),
                'last_page' => $media->lastPage(),
            ],
        ]);
    }

    /**
     * Get media usage details (which products use this image)
     */
    public function usage(int $mediaId)
    {
        $usage = $this->mediaService->getMediaUsage($mediaId);

        return response()->json([
            'media_id' => $mediaId,
            'usage' => $usage,
            'total_usage' => $usage->count(),
        ]);
    }

    /**
     * Get orphaned media (not attached to any model)
     */
    public function orphaned()
    {
        $orphaned = $this->mediaService->getOrphanedMedia();

        return response()->json([
            'data' => $orphaned->map(function ($item) {
                return [
                    'id' => $item->id,
                    'uuid' => $item->uuid,
                    'name' => $item->name,
                    'file_name' => $item->file_name,
                    'size' => $item->size,
                    'human_readable_size' => $item->human_readable_size,
                    'created_at' => $item->created_at,
                    'original_url' => $item->getUrl(),
                    'thumb_url' => $item->getUrl('thumb'),
                ];
            }),
            'total' => $orphaned->count(),
        ]);
    }

    /**
     * Delete media from library
     */
    public function destroy(int $mediaId)
    {
        $result = $this->mediaService->safeDeleteMedia($mediaId);

        return response()->json($result);
    }

    /**
     * Bulk delete orphaned media
     */
    public function cleanupOrphaned()
    {
        $orphaned = $this->mediaService->getOrphanedMedia();
        $count = $orphaned->count();

        foreach ($orphaned as $media) {
            $media->delete();
        }

        return response()->json([
            'deleted_count' => $count,
            'message' => "{$count} orphaned media files deleted successfully.",
        ]);
    }

    /**
     * Get duplicate images (same hash)
     */
    public function duplicates()
    {
        $duplicates = Media::selectRaw('hash, COUNT(*) as count')
            ->whereNotNull('hash')
            ->groupBy('hash')
            ->having('count', '>', 1)
            ->get();

        $result = [];
        foreach ($duplicates as $duplicate) {
            $mediaItems = Media::where('hash', $duplicate->hash)
                ->with('model:id,name')
                ->get();

            $result[] = [
                'hash' => $duplicate->hash,
                'count' => $duplicate->count,
                'items' => $mediaItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'file_name' => $item->file_name,
                        'attached_to' => $item->model ? [
                            'type' => class_basename($item->model_type),
                            'id' => $item->model_id,
                            'name' => $item->model->name ?? null,
                        ] : null,
                        'url' => $item->getUrl('thumb'),
                    ];
                }),
            ];
        }

        return response()->json([
            'duplicates' => $result,
            'total_duplicate_groups' => count($result),
        ]);
    }
}
