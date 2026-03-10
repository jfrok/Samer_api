<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductDetailResource;
use App\Models\Product;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductGalleryController extends Controller
{
    protected $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    /**
     * Display a listing of products.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'variants'])
            ->when($request->search, function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%");
            })
            ->when($request->category_id, fn($q) => $q->where('category_id', $request->category_id))
            ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->is_active));

        $products = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json(ProductResource::collection($products));
    }

    /**
     * Store a newly created product.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Ensure unique slug
            $slug = $this->generateUniqueSlug($request->slug);

            // Create product
            $product = Product::create([
                'name' => $request->name,
                'slug' => $slug,
                'description' => $request->description,
                'category_id' => $request->category_id,
                'brand' => $request->brand,
                'base_price' => $request->base_price,
                'is_active' => $request->input('is_active', true),
            ]);

            // Upload gallery images with duplicate detection
            if ($request->hasFile('gallery')) {
                foreach ($request->file('gallery') as $index => $image) {
                    // Check for duplicates
                    $duplicate = $this->mediaService->findDuplicateMedia($image);

                    if ($duplicate && $request->input('prevent_duplicates', true)) {
                        // Reuse existing media
                        $media = $this->mediaService->attachExistingMedia($product, $duplicate->id, 'gallery');
                    } else {
                        // Upload new media
                        $media = $product->addMedia($image)
                            ->withCustomProperties([
                                'alt_text' => $request->input("gallery_alt.{$index}", $product->name),
                                'caption' => $request->input("gallery_caption.{$index}", ''),
                            ])
                            ->preservingOriginal() // Keep original file
                            ->toMediaCollection('gallery');

                        // Add hash for future duplicate detection
                        $this->mediaService->addHashToMedia($media);
                    }
                }
            }

            // Create variants
            if ($request->has('variants')) {
                foreach ($request->variants as $variantData) {
                    $variantData['sku'] = $variantData['sku'] ?? $this->generateSKU($product, $variantData);
                    $product->variants()->create($variantData);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Product created successfully',
                'data' => new ProductDetailResource($product->load(['category', 'variants'])),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Product creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to create product',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): JsonResponse
    {
        $product->load(['category', 'variants']);

        return response()->json([
            'data' => new ProductDetailResource($product)
        ]);
    }

    /**
     * Update the specified product.
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Update basic fields
            $product->update($request->only([
                'name', 'slug', 'description', 'category_id', 'brand', 'base_price', 'is_active'
            ]));

            // Upload new gallery images with duplicate detection
            if ($request->hasFile('gallery')) {
                foreach ($request->file('gallery') as $index => $image) {
                    // Check for duplicates
                    $duplicate = $this->mediaService->findDuplicateMedia($image);

                    if ($duplicate && $request->input('prevent_duplicates', true)) {
                        // Reuse existing media
                        $media = $this->mediaService->attachExistingMedia($product, $duplicate->id, 'gallery');
                    } else {
                        // Upload new media
                        $media = $product->addMedia($image)
                            ->withCustomProperties([
                                'alt_text' => $request->input("gallery_alt.{$index}", $product->name),
                                'caption' => $request->input("gallery_caption.{$index}", ''),
                            ])
                            ->preservingOriginal()
                            ->toMediaCollection('gallery');

                        // Add hash for future duplicate detection
                        $this->mediaService->addHashToMedia($media);
                    }
                }
            }

            // Update variants
            if ($request->has('variants')) {
                $requestedVariantIds = collect($request->variants)->pluck('id')->filter();

                // Soft delete variants not in request
                $product->variants()->whereNotIn('id', $requestedVariantIds)->delete();

                // Update or create variants
                foreach ($request->variants as $variantData) {
                    if (isset($variantData['id'])) {
                        $product->variants()->find($variantData['id'])?->update($variantData);
                    } else {
                        $variantData['sku'] = $variantData['sku'] ?? $this->generateSKU($product, $variantData);
                        $product->variants()->create($variantData);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Product updated successfully',
                'data' => new ProductDetailResource($product->fresh()->load(['category', 'variants'])),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Product update failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to update product',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Product $product): JsonResponse
    {
        try {
            // Soft delete will preserve media
            $product->delete();

            return response()->json([
                'message' => 'Product deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Product deletion failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to delete product',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Delete a specific gallery image.
     */
    public function deleteGalleryImage(Product $product, string $mediaId): JsonResponse
    {
        try {
            $media = $product->getMedia('gallery')->where('id', $mediaId)->first();

            if (!$media) {
                return response()->json([
                    'message' => 'Image not found'
                ], 404);
            }

            $media->delete();

            return response()->json([
                'message' => 'Image deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Image deletion failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to delete image',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Reorder gallery images.
     */
    public function reorderGalleryImages(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'required|integer|exists:media,id',
        ]);

        try {
            $mediaItems = $product->getMedia('gallery');

            foreach ($request->order as $index => $mediaId) {
                $media = $mediaItems->where('id', $mediaId)->first();
                if ($media) {
                    $media->order_column = $index + 1;
                    $media->save();
                }
            }

            return response()->json([
                'message' => 'Gallery images reordered successfully',
                'data' => new ProductDetailResource($product)
            ]);
        } catch (\Exception $e) {
            Log::error('Image reorder failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to reorder images',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update image metadata (alt text, caption).
     */
    public function updateImageMetadata(Request $request, Product $product, string $mediaId): JsonResponse
    {
        $request->validate([
            'alt_text' => 'nullable|string|max:255',
            'caption' => 'nullable|string|max:500',
        ]);

        try {
            $media = $product->getMedia('gallery')->where('id', $mediaId)->first();

            if (!$media) {
                return response()->json(['message' => 'Image not found'], 404);
            }

            $media->setCustomProperty('alt_text', $request->alt_text);
            $media->setCustomProperty('caption', $request->caption);
            $media->save();

            return response()->json([
                'message' => 'Image metadata updated successfully',
                'data' => [
                    'id' => $media->id,
                    'alt_text' => $media->getCustomProperty('alt_text'),
                    'caption' => $media->getCustomProperty('caption'),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Image metadata update failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to update image metadata',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Attach existing media from library to product.
     */
    public function attachExistingMedia(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'media_id' => 'required|integer|exists:media,id',
            'collection' => 'nullable|string|in:gallery,main_image',
        ]);

        try {
            $collection = $request->input('collection', 'gallery');
            $media = $this->mediaService->attachExistingMedia($product, $request->media_id, $collection);

            return response()->json([
                'message' => 'Existing media attached successfully',
                'data' => new ProductDetailResource($product->load(['category', 'variants']))
            ]);
        } catch (\Exception $e) {
            Log::error('Attach existing media failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to attach media',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Generate unique slug.
     */
    private function generateUniqueSlug(string $slug, int $attempt = 0): string
    {
        $newSlug = $attempt > 0 ? "{$slug}-{$attempt}" : $slug;

        if (Product::where('slug', $newSlug)->exists()) {
            return $this->generateUniqueSlug($slug, $attempt + 1);
        }

        return $newSlug;
    }

    /**
     * Generate SKU for variant.
     */
    private function generateSKU(Product $product, array $variantData): string
    {
        $base = Str::upper(Str::limit($product->slug, 10, ''));
        $size = Str::upper(Str::limit($variantData['size'], 3, ''));
        $color = Str::upper(Str::limit($variantData['color'], 3, ''));
        $random = rand(100000, 999999);

        return "{$base}-{$size}-{$color}-{$random}";
    }
}
