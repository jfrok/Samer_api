# Media Library Album System - Complete Guide

## Overview

The Media Library Album System provides centralized media management with automatic duplicate detection, preventing the same image from being uploaded multiple times. Images are stored once and can be reused across multiple products.

---

## Key Features

✅ **Duplicate Detection** - Automatically detects duplicate images using SHA-256 hash  
✅ **Media Album Browser** - Browse all uploaded images in one central library  
✅ **Usage Tracking** - See which products use each image  
✅ **Reuse Existing Images** - Attach already-uploaded images to products  
✅ **Space Optimization** - Duplicate images share the same file storage  
✅ **Orphaned Media Cleanup** - Find and delete unused media files  

---

## How It Works

### 1. Duplicate Detection

When uploading an image, the system:
1. Calculates SHA-256 hash of the file
2. Checks if an image with the same hash exists
3. If duplicate found → Reuses existing image
4. If unique → Uploads new image and stores hash

### 2. Media Sharing

- Multiple products can reference the same image file
- Each product has its own media record with custom metadata (alt text, caption)
- Deleting from one product doesn't affect other products using it

---

## API Endpoints

### Media Library Management

#### 1. Browse Media Library
```http
GET /api/admin/media-library
```

**Query Parameters:**
- `per_page` (optional, default: 20) - Results per page
- `search` (optional) - Search by filename or name

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "uuid": "abc123-def456",
      "name": "Product Image",
      "file_name": "product-image.jpg",
      "mime_type": "image/jpeg",
      "size": 256000,
      "human_readable_size": "250 KB",
      "hash": "a1b2c3d4e5f6...",
      "created_at": "2026-02-21T10:00:00.000000Z",
      "usage_count": 3,
      "original_url": "http://localhost:8000/storage/1/product-image.jpg",
      "conversions": {
        "thumb": "http://localhost:8000/storage/1/conversions/product-image-thumb.jpg",
        "medium": "http://localhost:8000/storage/1/conversions/product-image-medium.jpg",
        "large": "http://localhost:8000/storage/1/conversions/product-image-large.jpg"
      },
      "custom_properties": {
        "alt_text": "Product image",
        "caption": "Main product photo"
      },
      "attached_to": {
        "type": "Product",
        "id": 5,
        "name": "Product Name"
      }
    }
  ],
  "pagination": {
    "total": 50,
    "per_page": 20,
    "current_page": 1,
    "last_page": 3
  }
}
```

---

#### 2. Check Media Usage
```http
GET /api/admin/media-library/{mediaId}/usage
```

**Response:**
```json
{
  "media_id": 1,
  "usage": [
    {
      "model_type": "App\\Models\\Product",
      "model_id": 5,
      "model": {
        "id": 5,
        "name": "Product Name",
        "slug": "product-name"
      }
    },
    {
      "model_type": "App\\Models\\Product",
      "model_id": 8,
      "model": {
        "id": 8,
        "name": "Another Product",
        "slug": "another-product"
      }
    }
  ],
  "total_usage": 2
}
```

---

#### 3. Find Duplicate Images
```http
GET /api/admin/media-library/duplicates
```

**Response:**
```json
{
  "duplicates": [
    {
      "hash": "a1b2c3d4e5f6...",
      "count": 3,
      "items": [
        {
          "id": 1,
          "file_name": "image1.jpg",
          "attached_to": {
            "type": "Product",
            "id": 5,
            "name": "Product A"
          },
          "url": "http://localhost:8000/storage/1/conversions/image1-thumb.jpg"
        },
        {
          "id": 2,
          "file_name": "image2.jpg",
          "attached_to": {
            "type": "Product",
            "id": 7,
            "name": "Product B"
          },
          "url": "http://localhost:8000/storage/2/conversions/image2-thumb.jpg"
        }
      ]
    }
  ],
  "total_duplicate_groups": 1
}
```

---

#### 4. Get Orphaned Media
```http
GET /api/admin/media-library/orphaned
```

Returns media files not attached to any product.

---

#### 5. Delete Media
```http
DELETE /api/admin/media-library/{mediaId}
```

**Response:**
```json
{
  "deleted": true,
  "message": "Media removed from product. Image still used by other products.",
  "still_in_use": true
}
```

---

#### 6. Cleanup Orphaned Media
```http
POST /api/admin/media-library/cleanup-orphaned
```

Bulk deletes all orphaned media files.

**Response:**
```json
{
  "deleted_count": 15,
  "message": "15 orphaned media files deleted successfully."
}
```

---

### Product Gallery - Attach Existing Media

#### Attach Existing Image from Library
```http
POST /api/admin/products/{productId}/gallery/attach
```

**Request Body:**
```json
{
  "media_id": 5,
  "collection": "gallery"
}
```

**Parameters:**
- `media_id` (required) - ID of existing media to attach
- `collection` (optional, default: "gallery") - Collection name ("gallery" or "main_image")

**Response:**
```json
{
  "message": "Existing media attached successfully",
  "data": {
    // ProductDetailResource with updated gallery
  }
}
```

---

## Upload Behavior

### Automatic Duplicate Prevention

When uploading images via product create/update:

**Default (Prevent Duplicates):**
```http
POST /api/admin/products
Content-Type: multipart/form-data

name: "Product Name"
gallery[]: image1.jpg
gallery[]: image2.jpg
prevent_duplicates: true (default)
```

If `image1.jpg` already exists → System reuses existing file  
If `image2.jpg` is new → System uploads new file

**Allow Duplicates:**
```http
POST /api/admin/products
Content-Type: multipart/form-data

name: "Product Name"
gallery[]: image1.jpg
prevent_duplicates: false
```

Force upload even if duplicate exists.

---

## Usage Workflow

### Scenario 1: Upload Product with Images

```javascript
// Frontend: Upload product with 3 images
const formData = new FormData();
formData.append('name', 'T-Shirt Blue');
formData.append('gallery[]', image1File);
formData.append('gallery[]', image2File);
formData.append('gallery[]', image3File);

await axios.post('/api/admin/products', formData);

// Backend automatically:
// - Checks each image hash
// - Reuses existing images if duplicates found
// - Only uploads truly new images
```

### Scenario 2: Browse Media Library

```javascript
// Fetch all media from library
const response = await axios.get('/api/admin/media-library', {
  params: { per_page: 20, search: 'product' }
});

// Display in gallery UI with:
// - Thumbnails
// - Usage count badges
// - Search/filter
```

### Scenario 3: Reuse Existing Image

```javascript
// User browses media library, selects image ID 5
// Attach to product ID 10
await axios.post('/api/admin/products/10/gallery/attach', {
  media_id: 5,
  collection: 'gallery'
});

// Product 10 now has this image
// Original product still has it too
```

### Scenario 4: Find Duplicates

```javascript
// Check for duplicate images
const response = await axios.get('/api/admin/media-library/duplicates');

// Shows all images that appear multiple times
// Can decide which to keep/remove
```

### Scenario 5: Cleanup Unused Images

```javascript
// Find orphaned media
const orphaned = await axios.get('/api/admin/media-library/orphaned');

// Delete all at once
await axios.post('/api/admin/media-library/cleanup-orphaned');
```

---

## Database Schema

### Media Table (Updated)
```sql
media
├── id (bigint)
├── model_type (string) - Product, etc.
├── model_id (bigint) - Product ID
├── uuid (string) - Unique identifier
├── collection_name (string) - 'gallery', 'main_image'
├── name (string) - Display name
├── file_name (string) - Actual filename
├── hash (string) - SHA-256 hash for duplicate detection ⭐ NEW
├── mime_type (string)
├── disk (string)
├── size (bigint)
├── custom_properties (json) - alt_text, caption
├── created_at
└── updated_at
```

**Key Points:**
- `hash` column stores SHA-256 hash of file content
- Same hash = Same file (duplicate)
- Index on `hash` for fast duplicate lookups

---

## Frontend Implementation Examples

### Vue 3 Media Library Browser

```vue
<template>
  <div class="media-library">
    <el-input 
      v-model="search" 
      placeholder="Search images..." 
      @input="fetchMedia"
    />
    
    <el-row :gutter="20">
      <el-col 
        v-for="media in mediaItems" 
        :key="media.id" 
        :span="6"
      >
        <el-card class="media-card">
          <img :src="media.conversions.thumb" :alt="media.name" />
          
          <div class="media-info">
            <h4>{{ media.file_name }}</h4>
            <el-tag size="small">{{ media.human_readable_size }}</el-tag>
            <el-tag v-if="media.usage_count > 1" type="warning">
              Used {{ media.usage_count }}x
            </el-tag>
          </div>
          
          <div class="media-actions">
            <el-button @click="attachToProduct(media.id)">
              Attach to Product
            </el-button>
            <el-button @click="viewUsage(media.id)" type="info">
              View Usage
            </el-button>
            <el-button @click="deleteMedia(media.id)" type="danger">
              Delete
            </el-button>
          </div>
        </el-card>
      </el-col>
    </el-row>
    
    <el-pagination
      v-model:current-page="currentPage"
      :page-size="perPage"
      :total="totalItems"
      @current-change="fetchMedia"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const search = ref('');
const mediaItems = ref([]);
const currentPage = ref(1);
const perPage = ref(20);
const totalItems = ref(0);

const fetchMedia = async () => {
  const response = await axios.get('/api/admin/media-library', {
    params: {
      per_page: perPage.value,
      page: currentPage.value,
      search: search.value
    }
  });
  
  mediaItems.value = response.data.data;
  totalItems.value = response.data.pagination.total;
};

const attachToProduct = async (mediaId) => {
  const productId = 10; // Current product being edited
  await axios.post(`/api/admin/products/${productId}/gallery/attach`, {
    media_id: mediaId
  });
};

const viewUsage = async (mediaId) => {
  const response = await axios.get(`/api/admin/media-library/${mediaId}/usage`);
  console.log('Usage:', response.data.usage);
};

const deleteMedia = async (mediaId) => {
  await axios.delete(`/api/admin/media-library/${mediaId}`);
  fetchMedia();
};

onMounted(() => {
  fetchMedia();
});
</script>
```

---

## Benefits

### Space Savings
- **Before:** 10 products with same logo = 10 file copies (10 MB)
- **After:** 10 products sharing 1 logo = 1 file (1 MB)
- **Savings:** 90% reduction

### Performance
- Faster uploads (skip duplicate files)
- Less bandwidth usage
- Faster page loads (browser caching)

### Management
- Central media library view
- Easy cleanup of unused images
- Track which products use which images

---

## Technical Details

### How Hashing Works

```php
// When uploading
$hash = hash_file('sha256', $uploadedFile->getRealPath());

// Check for existing
$duplicate = Media::where('hash', $hash)->first();

if ($duplicate) {
    // Reuse existing file
    $newMedia = copyMediaToProduct($duplicate, $product);
} else {
    // Upload new file
    $newMedia = $product->addMedia($file)->toMediaCollection('gallery');
    $newMedia->update(['hash' => $hash]);
}
```

### Safe Deletion

```php
// When deleting
$usageCount = Media::where('hash', $media->hash)->count();

if ($usageCount > 1) {
    // Delete only this reference
    $media->delete(); // Others still exist
} else {
    // Delete file completely
    $media->delete(); // Last reference
}
```

---

## Troubleshooting

### Hashes Not Being Created?

Run migration:
```bash
php artisan migrate
```

Add hash to existing media:
```php
Media::whereNull('hash')->get()->each(function ($media) {
    if (file_exists($media->getPath())) {
        $hash = hash_file('sha256', $media->getPath());
        $media->update(['hash' => $hash]);
    }
});
```

### Duplicates Still Being Uploaded?

Check `prevent_duplicates` parameter:
```javascript
formData.append('prevent_duplicates', true);
```

### Cannot Find Media in Library?

Ensure hash is not null:
```sql
SELECT * FROM media WHERE hash IS NOT NULL;
```

---

## Summary

✅ Automatic duplicate detection using file hashes  
✅ Central media library for browsing all images  
✅ Reuse existing images across products  
✅ Track usage and safely delete media  
✅ Space and performance optimization  

The system is now production-ready with comprehensive media management!
