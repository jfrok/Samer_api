# Production-Ready Product Image Gallery System

## 📋 Table of Contents
1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Setup Guide](#setup-guide)
4. [Queue Worker Configuration](#queue-worker-configuration)
5. [Storage Configuration](#storage-configuration)
6. [API Endpoints](#api-endpoints)
7. [Frontend Integration](#frontend-integration)
8. [Testing](#testing)
9. [Troubleshooting](#troubleshooting)
10. [Performance Optimization](#performance-optimization)

---

## Overview

This is a production-ready Laravel API implementation for managing product image galleries using Spatie Media Library. The system includes:

✅ **Multiple image uploads** via `multipart/form-data` (no base64)  
✅ **Three responsive conversions**: thumb (300px), medium (600px), large (1200px)  
✅ **Queue-based image processing** for non-blocking uploads  
✅ **Image metadata management** (alt text, captions, custom properties)  
✅ **Image reordering** with drag-and-drop support  
✅ **Delete specific images** without affecting the product  
✅ **Form Request validation** for clean controllers  
✅ **API Resources** for consistent JSON responses  
✅ **S3/CDN support** for scalable storage  

---

## Architecture

### Components

```
┌─────────────────────┐
│ StoreProductRequest │ ← Validation Layer
│ UpdateProductRequest│
└──────────┬──────────┘
           │
           ▼
┌─────────────────────────┐
│ ProductGalleryController│ ← Business Logic
└──────────┬──────────────┘
           │
           ▼
┌─────────────────────────┐
│    Product Model        │ ← Data Layer
│  (HasMedia trait)       │
└──────────┬──────────────┘
           │
           ▼
┌─────────────────────────┐
│ Spatie Media Library    │ ← Storage Engine
│  + Queue System         │
└──────────┬──────────────┘
           │
           ▼
┌─────────────────────────┐
│  Local / S3 Storage     │ ← File System
└─────────────────────────┘
```

### Database Schema

```sql
-- Media table (created by Media Library)
media
  - id
  - model_type (App\Models\Product)
  - model_id (product id)
  - collection_name ('gallery')
  - name (original filename)
  - file_name (stored filename)
  - mime_type
  - disk (public/s3)
  - size (bytes)
  - custom_properties (JSON: alt_text, caption)
  - order_column (for sorting)
  - created_at
  - updated_at
```

---

## Setup Guide

### 1. Install Dependencies

Already installed in your project:
```bash
composer require spatie/laravel-medialibrary:^10.15.0
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider"
```

### 3. Run Migrations

```bash
php artisan migrate
```

This creates:
- `media` table
- `jobs` table (for queue system)
- `failed_jobs` table

### 4. Link Storage

```bash
php artisan storage:link
```

This creates a symbolic link: `public/storage` → `storage/app/public`

### 5. Configure Queue System

Edit `.env`:

```env
QUEUE_CONNECTION=database
```

Run queue table migration if not exists:

```bash
php artisan queue:table
php artisan migrate
```

---

## Queue Worker Configuration

### Development

Start the queue worker manually:

```bash
php artisan queue:work --queue=media --tries=3 --timeout=300
```

Options:
- `--queue=media` - Process only media queue jobs
- `--tries=3` - Retry failed jobs 3 times
- `--timeout=300` - Kill jobs running over 5 minutes

### Production (Supervisor)

Create file: `/etc/supervisor/conf.d/samer-queue-worker.conf`

```ini
[program:samer-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work database --queue=media --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/queue-worker.log
stopwaitsecs=3600
```

Load configuration:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start samer-queue-worker:*
```

Monitor queue:

```bash
# Check status
sudo supervisorctl status samer-queue-worker:*

# Restart workers
sudo supervisorctl restart samer-queue-worker:*

# View logs
tail -f storage/logs/queue-worker.log
```

### Monitoring Failed Jobs

```bash
# List failed jobs
php artisan queue:failed

# Retry specific job
php artisan queue:retry {job-id}

# Retry all failed jobs
php artisan queue:retry all

# Flush failed jobs
php artisan queue:flush
```

---

## Storage Configuration

### Local Storage (Development)

Already configured in `config/filesystems.php`:

```php
'public' => [
    'driver' => 'local',
    'root' => storage_path('app/public'),
    'url' => env('APP_URL').'/storage',
    'visibility' => 'public',
],
```

Images are stored in: `storage/app/public/products/{product-id}/`

### S3 Storage (Production)

#### 1. Install AWS SDK

```bash
composer require league/flysystem-aws-s3-v3 "^3.0"
```

#### 2. Configure `.env`

```env
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
AWS_URL=https://your-bucket.s3.amazonaws.com
AWS_USE_PATH_STYLE_ENDPOINT=false
```

#### 3. Update Product Model

Edit `app/Models/Product.php`:

```php
public function registerMediaCollections(): void
{
    $this->addMediaCollection('gallery')
        ->useDisk(config('app.env') === 'production' ? 's3' : 'public')
        ->registerMediaConversions(function (Media $media) {
            $this->addMediaConversion('thumb')
                ->width(300)
                ->height(300)
                ->sharpen(10)
                ->nonQueued(); // Immediate for preview

            $this->addMediaConversion('medium')
                ->width(600)
                ->height(600)
                ->optimize();

            $this->addMediaConversion('large')
                ->width(1200)
                ->height(1200)
                ->quality(85)
                ->optimize();
        });
}
```

#### 4. Configure CORS on S3 Bucket

```json
[
  {
    "AllowedHeaders": ["*"],
    "AllowedMethods": ["GET", "HEAD"],
    "AllowedOrigins": ["https://yourdomain.com", "https://admin.yourdomain.com"],
    "ExposeHeaders": []
  }
]
```

#### 5. CloudFront CDN (Optional)

For better performance, use CloudFront in front of S3:

```env
AWS_URL=https://dxxxxxxxxxxxxx.cloudfront.net
```

---

## API Endpoints

### Base URL
```
https://yourdomain.com/api/admin
```

### Authentication
All admin endpoints require authentication:
```
Authorization: Bearer {token}
```

---

### 1. List Products

**Endpoint:** `GET /api/admin/products`

**Query Parameters:**
- `page` (int) - Page number
- `per_page` (int) - Items per page (default: 15)
- `search` (string) - Search in name/description
- `category_id` (int) - Filter by category
- `is_active` (bool) - Filter by status

**Example Request:**
```bash
GET /api/admin/products?page=1&per_page=20&category_id=3&search=t-shirt
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Blue T-Shirt",
      "slug": "blue-t-shirt",
      "brand": "Nike",
      "base_price": 29.99,
      "is_active": true,
      "category": {
        "id": 3,
        "name": "T-Shirts",
        "slug": "t-shirts"
      },
      "featured_image": {
        "id": 45,
        "thumb": "http://localhost/storage/products/1/thumb.jpg",
        "medium": "http://localhost/storage/products/1/medium.jpg",
        "alt_text": "Blue T-Shirt"
      },
      "gallery_count": 5,
      "variants_count": 4,
      "price_range": {
        "min": 24.99,
        "max": 34.99,
        "formatted": "24.99 - 34.99"
      },
      "created_at": "2024-01-15T10:30:00.000000Z",
      "updated_at": "2024-01-20T14:22:00.000000Z"
    }
  ],
  "links": { ... },
  "meta": { ... }
}
```

---

### 2. Create Product

**Endpoint:** `POST /api/admin/products`

**Content-Type:** `multipart/form-data`

**Request Body:**
```
name: Blue T-Shirt (required)
slug: blue-t-shirt (optional, auto-generated)
description: Comfortable cotton t-shirt (required)
category_id: 3 (required)
brand: Nike (optional)
base_price: 29.99 (required)
is_active: 1 (optional, default: 1)
gallery[]: [File] (optional, max 10 images)
gallery[]: [File]
gallery_alt[0]: Blue T-Shirt Front View (optional)
gallery_alt[1]: Blue T-Shirt Back View (optional)
gallery_caption[0]: Official product image (optional)
variants[0][size]: S (optional)
variants[0][color]: Blue (optional)
variants[0][sku]: BTS-S-BL-123456 (optional, auto-generated)
variants[0][price]: 24.99 (optional)
variants[0][stock]: 10 (optional)
```

**Validation Rules:**
- `name`: required, string, max:255
- `description`: required, string
- `category_id`: required, exists:categories,id
- `brand`: nullable, string, max:100
- `base_price`: required, numeric, min:0
- `is_active`: nullable, boolean
- `gallery`: nullable, array, max:10
- `gallery.*`: required, file, mimes:jpeg,jpg,png,gif,webp, max:5120 (5MB), dimensions:min_width=100,min_height=100,max_width=4000,max_height=4000

**cURL Example:**
```bash
curl -X POST "http://localhost/api/admin/products" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json" \
  -F "name=Blue T-Shirt" \
  -F "description=Comfortable cotton t-shirt" \
  -F "category_id=3" \
  -F "brand=Nike" \
  -F "base_price=29.99" \
  -F "is_active=1" \
  -F "gallery[]=@/path/to/image1.jpg" \
  -F "gallery[]=@/path/to/image2.jpg" \
  -F "gallery_alt[0]=Front View" \
  -F "gallery_alt[1]=Back View" \
  -F "variants[0][size]=S" \
  -F "variants[0][color]=Blue" \
  -F "variants[0][price]=24.99" \
  -F "variants[0][stock]=10"
```

**Response (201 Created):**
```json
{
  "message": "Product created successfully",
  "data": {
    "id": 1,
    "name": "Blue T-Shirt",
    "slug": "blue-t-shirt",
    "description": "Comfortable cotton t-shirt",
    "brand": "Nike",
    "base_price": 29.99,
    "is_active": true,
    "category": { ... },
    "gallery": [
      {
        "id": 45,
        "uuid": "a1b2c3d4-e5f6-7890-abcd-1234567890ab",
        "order": 1,
        "original_url": "http://localhost/storage/products/1/original.jpg",
        "conversions": [
          {
            "name": "thumb",
            "url": "http://localhost/storage/products/1/thumb.jpg",
            "width": 300,
            "height": 300
          },
          {
            "name": "medium",
            "url": "http://localhost/storage/products/1/medium.jpg",
            "width": 600,
            "height": 600
          },
          {
            "name": "large",
            "url": "http://localhost/storage/products/1/large.jpg",
            "width": 1200,
            "height": 1200
          }
        ],
        "custom_properties": {
          "alt_text": "Front View",
          "caption": ""
        },
        "responsive": {
          "srcset": "http://localhost/storage/products/1/thumb.jpg 300w, http://localhost/storage/products/1/medium.jpg 600w",
          "sizes": "(max-width: 600px) 100vw, 600px"
        },
        "file_name": "original.jpg",
        "mime_type": "image/jpeg",
        "size": 245678
      }
    ],
    "variants": [ ... ],
    "created_at": "2024-01-20T10:30:00.000000Z",
    "updated_at": "2024-01-20T10:30:00.000000Z"
  }
}
```

---

### 3. Update Product

**Endpoint:** `PUT /api/admin/products/{id}` or `POST /api/admin/products/{id}?_method=PUT`

**Content-Type:** `multipart/form-data`

**Request Body:** (all fields optional)
```
name: Updated Blue T-Shirt
description: Updated description
category_id: 4
brand: Nike Pro
base_price: 34.99
is_active: 1
gallery[]: [File] (new images to add)
gallery[]: [File]
gallery_alt[0]: New Image Alt
variants[0][id]: 5 (update existing)
variants[0][price]: 29.99
variants[1][size]: M (create new)
variants[1][color]: Red
```

**cURL Example:**
```bash
curl -X POST "http://localhost/api/admin/products/1?_method=PUT" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json" \
  -F "name=Updated Blue T-Shirt" \
  -F "base_price=34.99" \
  -F "gallery[]=@/path/to/new-image.jpg" \
  -F "gallery_alt[0]=New Product Shot"
```

**Response (200 OK):**
```json
{
  "message": "Product updated successfully",
  "data": { ... }
}
```

---

### 4. Get Product Details

**Endpoint:** `GET /api/admin/products/{id}`

**Response:**
```json
{
  "data": {
    "id": 1,
    "name": "Blue T-Shirt",
    "slug": "blue-t-shirt",
    "description": "Comfortable cotton t-shirt",
    "brand": "Nike",
    "base_price": 29.99,
    "is_active": true,
    "category": {
      "id": 3,
      "name": "T-Shirts",
      "slug": "t-shirts"
    },
    "gallery": [
      {
        "id": 45,
        "uuid": "a1b2c3d4-e5f6-7890-abcd-1234567890ab",
        "order": 1,
        "original_url": "http://localhost/storage/products/1/original.jpg",
        "conversions": [
          {
            "name": "thumb",
            "url": "http://localhost/storage/products/1/thumb.jpg",
            "width": 300,
            "height": 300
          },
          {
            "name": "medium",
            "url": "http://localhost/storage/products/1/medium.jpg",
            "width": 600,
            "height": 600
          },
          {
            "name": "large",
            "url": "http://localhost/storage/products/1/large.jpg",
            "width": 1200,
            "height": 1200
          }
        ],
        "custom_properties": {
          "alt_text": "Blue T-Shirt",
          "caption": "Official product image"
        },
        "responsive": {
          "srcset": "http://localhost/storage/products/1/thumb.jpg 300w, http://localhost/storage/products/1/medium.jpg 600w, http://localhost/storage/products/1/large.jpg 1200w",
          "sizes": "(max-width: 600px) 100vw, (max-width: 1200px) 50vw, 1200px"
        },
        "file_name": "original.jpg",
        "mime_type": "image/jpeg",
        "size": 245678
      }
    ],
    "variants": [
      {
        "id": 1,
        "size": "S",
        "color": "Blue",
        "sku": "BTS-S-BL-123456",
        "price": 24.99,
        "stock": 10
      }
    ],
    "created_at": "2024-01-15T10:30:00.000000Z",
    "updated_at": "2024-01-20T14:22:00.000000Z"
  }
}
```

---

### 5. Delete Product

**Endpoint:** `DELETE /api/admin/products/{id}`

**Response (200 OK):**
```json
{
  "message": "Product deleted successfully"
}
```

Note: This is a soft delete. Images are preserved.

---

### 6. Delete Specific Gallery Image

**Endpoint:** `DELETE /api/admin/products/{productId}/gallery/{mediaId}`

**Example:**
```bash
DELETE /api/admin/products/1/gallery/45
```

**Response (200 OK):**
```json
{
  "message": "Image deleted successfully"
}
```

---

### 7. Reorder Gallery Images

**Endpoint:** `POST /api/admin/products/{productId}/gallery/reorder`

**Request Body:**
```json
{
  "order": [45, 47, 46, 48, 49]
}
```

Array contains media IDs in desired order (first = main image).

**Response (200 OK):**
```json
{
  "message": "Gallery images reordered successfully",
  "data": { ... }
}
```

---

### 8. Update Image Metadata

**Endpoint:** `PATCH /api/admin/products/{productId}/gallery/{mediaId}`

**Request Body:**
```json
{
  "alt_text": "Blue T-Shirt - Front View",
  "caption": "Official product photography by Nike"
}
```

**Response (200 OK):**
```json
{
  "message": "Image metadata updated successfully",
  "data": {
    "id": 45,
    "alt_text": "Blue T-Shirt - Front View",
    "caption": "Official product photography by Nike"
  }
}
```

---

## Frontend Integration

### Vue 3 Admin Panel

#### 1. Upload Multiple Images

```vue
<template>
  <div class="product-form">
    <h2>{{ isEdit ? 'Edit Product' : 'Create Product' }}</h2>
    
    <form @submit.prevent="submitForm">
      <!-- Basic Fields -->
      <input v-model="form.name" placeholder="Product Name" required />
      <textarea v-model="form.description" placeholder="Description" required></textarea>
      <input v-model.number="form.base_price" type="number" step="0.01" placeholder="Base Price" required />
      
      <!-- Image Gallery Upload -->
      <div class="gallery-upload">
        <label>Product Images (Max 10)</label>
        <input 
          type="file" 
          ref="fileInput"
          @change="handleFileSelect" 
          multiple 
          accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
          :disabled="form.gallery.length >= 10"
        />
        
        <!-- Preview Selected Images -->
        <div v-if="form.gallery.length" class="image-previews">
          <div v-for="(file, index) in form.gallery" :key="index" class="preview-item">
            <img :src="getPreviewUrl(file)" :alt="`Preview ${index + 1}`" />
            <input 
              v-model="form.gallery_alt[index]" 
              placeholder="Alt text"
              class="alt-input"
            />
            <input 
              v-model="form.gallery_caption[index]" 
              placeholder="Caption (optional)"
              class="caption-input"
            />
            <button type="button" @click="removeImage(index)" class="remove-btn">
              ✕ Remove
            </button>
          </div>
        </div>
        
        <p class="hint">{{ form.gallery.length }} / 10 images selected</p>
      </div>
      
      <button type="submit" :disabled="loading">
        {{ loading ? 'Saving...' : 'Save Product' }}
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import axios from 'axios'

const props = defineProps({
  productId: { type: Number, default: null }
})

const isEdit = ref(!!props.productId)
const loading = ref(false)
const fileInput = ref(null)

const form = reactive({
  name: '',
  description: '',
  base_price: 0,
  category_id: null,
  brand: '',
  is_active: true,
  gallery: [], // Array of File objects
  gallery_alt: [],
  gallery_caption: []
})

const handleFileSelect = (event) => {
  const files = Array.from(event.target.files)
  const remaining = 10 - form.gallery.length
  const toAdd = files.slice(0, remaining)
  
  // Validate each file
  for (const file of toAdd) {
    // Check size (5MB max)
    if (file.size > 5 * 1024 * 1024) {
      alert(`${file.name} is too large (max 5MB)`)
      continue
    }
    
    // Check dimensions
    validateImageDimensions(file).then(isValid => {
      if (isValid) {
        form.gallery.push(file)
        form.gallery_alt.push(form.name || '')
        form.gallery_caption.push('')
      }
    })
  }
  
  // Reset input
  event.target.value = ''
}

const validateImageDimensions = (file) => {
  return new Promise((resolve) => {
    const img = new Image()
    img.onload = () => {
      const isValid = img.width >= 100 && img.height >= 100 && 
                      img.width <= 4000 && img.height <= 4000
      if (!isValid) {
        alert(`${file.name} has invalid dimensions (must be 100-4000px)`)
      }
      resolve(isValid)
    }
    img.src = URL.createObjectURL(file)
  })
}

const getPreviewUrl = (file) => {
  return URL.createObjectURL(file)
}

const removeImage = (index) => {
  form.gallery.splice(index, 1)
  form.gallery_alt.splice(index, 1)
  form.gallery_caption.splice(index, 1)
}

const submitForm = async () => {
  loading.value = true
  
  // Create FormData
  const formData = new FormData()
  formData.append('name', form.name)
  formData.append('description', form.description)
  formData.append('base_price', form.base_price)
  formData.append('category_id', form.category_id)
  formData.append('brand', form.brand)
  formData.append('is_active', form.is_active ? 1 : 0)
  
  // Append gallery images
  form.gallery.forEach((file, index) => {
    formData.append('gallery[]', file)
    formData.append(`gallery_alt[${index}]`, form.gallery_alt[index])
    formData.append(`gallery_caption[${index}]`, form.gallery_caption[index])
  })
  
  try {
    const url = isEdit.value 
      ? `/api/admin/products/${props.productId}?_method=PUT`
      : '/api/admin/products'
    
    const response = await axios.post(url, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      },
      onUploadProgress: (progressEvent) => {
        const percent = Math.round((progressEvent.loaded * 100) / progressEvent.total)
        console.log(`Upload Progress: ${percent}%`)
      }
    })
    
    alert('Product saved successfully!')
    // Navigate to product list or show success
  } catch (error) {
    console.error('Upload failed:', error.response?.data)
    alert('Failed to save product. Check console for details.')
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.gallery-upload {
  margin: 20px 0;
}

.image-previews {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 15px;
  margin-top: 15px;
}

.preview-item {
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 10px;
}

.preview-item img {
  width: 100%;
  height: 150px;
  object-fit: cover;
  border-radius: 4px;
}

.alt-input, .caption-input {
  width: 100%;
  margin-top: 8px;
  padding: 6px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 12px;
}

.remove-btn {
  width: 100%;
  margin-top: 8px;
  padding: 6px;
  background: #ff4444;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.hint {
  font-size: 12px;
  color: #666;
  margin-top: 8px;
}
</style>
```

#### 2. Display Responsive Gallery

```vue
<template>
  <div class="product-gallery">
    <!-- Main Image -->
    <div class="main-image">
      <img 
        :src="currentImage.conversions.find(c => c.name === 'large').url"
        :srcset="currentImage.responsive.srcset"
        :sizes="currentImage.responsive.sizes"
        :alt="currentImage.custom_properties.alt_text"
        loading="lazy"
      />
    </div>
    
    <!-- Thumbnails -->
    <div class="thumbnails">
      <img 
        v-for="(image, index) in product.gallery"
        :key="image.id"
        :src="image.conversions.find(c => c.name === 'thumb').url"
        :alt="image.custom_properties.alt_text"
        @click="currentIndex = index"
        :class="{ active: currentIndex === index }"
        loading="lazy"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  product: { type: Object, required: true }
})

const currentIndex = ref(0)

const currentImage = computed(() => {
  return props.product.gallery[currentIndex.value]
})
</script>

<style scoped>
.product-gallery {
  max-width: 800px;
  margin: 0 auto;
}

.main-image {
  width: 100%;
  aspect-ratio: 1;
  overflow: hidden;
  border-radius: 8px;
  margin-bottom: 15px;
}

.main-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.thumbnails {
  display: flex;
  gap: 10px;
  overflow-x: auto;
}

.thumbnails img {
  width: 80px;
  height: 80px;
  object-fit: cover;
  border-radius: 4px;
  cursor: pointer;
  border: 2px solid transparent;
  transition: border-color 0.3s;
}

.thumbnails img:hover {
  border-color: #ccc;
}

.thumbnails img.active {
  border-color: #007bff;
}
</style>
```

#### 3. Drag-and-Drop Image Reordering

```vue
<template>
  <div class="gallery-manager">
    <h3>Manage Gallery ({{ images.length }} images)</h3>
    
    <draggable 
      v-model="images" 
      @end="saveOrder"
      item-key="id"
      class="image-grid"
    >
      <template #item="{ element, index }">
        <div class="image-card">
          <img :src="element.conversions.find(c => c.name === 'thumb').url" :alt="element.custom_properties.alt_text" />
          
          <div class="image-controls">
            <button @click="editMetadata(element)" class="btn-edit">
              ✏️ Edit
            </button>
            <button @click="deleteImage(element.id)" class="btn-delete">
              🗑️ Delete
            </button>
          </div>
          
          <div class="order-badge">{{ index + 1 }}</div>
        </div>
      </template>
    </draggable>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import draggable from 'vuedraggable'
import axios from 'axios'

const props = defineProps({
  productId: { type: Number, required: true },
  initialImages: { type: Array, required: true }
})

const images = ref([...props.initialImages])

const saveOrder = async () => {
  const order = images.value.map(img => img.id)
  
  try {
    await axios.post(`/api/admin/products/${props.productId}/gallery/reorder`, {
      order
    }, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      }
    })
    
    alert('Order saved!')
  } catch (error) {
    console.error('Failed to save order:', error)
    alert('Failed to save order')
  }
}

const deleteImage = async (mediaId) => {
  if (!confirm('Delete this image?')) return
  
  try {
    await axios.delete(`/api/admin/products/${props.productId}/gallery/${mediaId}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      }
    })
    
    images.value = images.value.filter(img => img.id !== mediaId)
    alert('Image deleted!')
  } catch (error) {
    console.error('Failed to delete image:', error)
    alert('Failed to delete image')
  }
}

const editMetadata = (image) => {
  const altText = prompt('Alt text:', image.custom_properties.alt_text)
  const caption = prompt('Caption:', image.custom_properties.caption)
  
  if (altText === null) return
  
  updateMetadata(image.id, altText, caption || '')
}

const updateMetadata = async (mediaId, altText, caption) => {
  try {
    await axios.patch(`/api/admin/products/${props.productId}/gallery/${mediaId}`, {
      alt_text: altText,
      caption: caption
    }, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      }
    })
    
    // Update local state
    const img = images.value.find(i => i.id === mediaId)
    if (img) {
      img.custom_properties.alt_text = altText
      img.custom_properties.caption = caption
    }
    
    alert('Metadata updated!')
  } catch (error) {
    console.error('Failed to update metadata:', error)
    alert('Failed to update metadata')
  }
}
</script>

<style scoped>
.image-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 15px;
  margin-top: 20px;
}

.image-card {
  position: relative;
  border: 2px solid #ddd;
  border-radius: 8px;
  padding: 10px;
  cursor: move;
  transition: transform 0.2s;
}

.image-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.image-card img {
  width: 100%;
  height: 150px;
  object-fit: cover;
  border-radius: 4px;
}

.image-controls {
  display: flex;
  gap: 5px;
  margin-top: 10px;
}

.image-controls button {
  flex: 1;
  padding: 6px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 12px;
}

.btn-edit {
  background: #007bff;
  color: white;
}

.btn-delete {
  background: #dc3545;
  color: white;
}

.order-badge {
  position: absolute;
  top: 15px;
  right: 15px;
  background: rgba(0,0,0,0.7);
  color: white;
  width: 30px;
  height: 30px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 14px;
}
</style>
```

---

## Testing

### 1. Unit Tests

Create `tests/Feature/ProductGalleryTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProductGalleryTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        Storage::fake('public');
        
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');
    }

    /** @test */
    public function admin_can_create_product_with_gallery()
    {
        $category = Category::factory()->create();
        
        $response = $this->actingAs($this->admin, 'sanctum')
            ->post('/api/admin/products', [
                'name' => 'Test Product',
                'description' => 'Test description',
                'category_id' => $category->id,
                'base_price' => 29.99,
                'gallery' => [
                    UploadedFile::fake()->image('image1.jpg', 600, 600),
                    UploadedFile::fake()->image('image2.jpg', 600, 600),
                ],
                'gallery_alt' => [
                    0 => 'Image 1 alt text',
                    1 => 'Image 2 alt text',
                ]
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'name',
                'gallery' => [
                    '*' => [
                        'id',
                        'original_url',
                        'conversions',
                        'custom_properties',
                    ]
                ]
            ]
        ]);

        $product = Product::first();
        $this->assertCount(2, $product->getMedia('gallery'));
    }

    /** @test */
    public function admin_can_delete_specific_gallery_image()
    {
        $product = Product::factory()->create();
        
        $media = $product->addMedia(UploadedFile::fake()->image('test.jpg'))
            ->toMediaCollection('gallery');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->delete("/api/admin/products/{$product->id}/gallery/{$media->id}");

        $response->assertStatus(200);
        $this->assertCount(0, $product->fresh()->getMedia('gallery'));
    }

    /** @test */
    public function admin_can_reorder_gallery_images()
    {
        $product = Product::factory()->create();
        
        $media1 = $product->addMedia(UploadedFile::fake()->image('test1.jpg'))
            ->toMediaCollection('gallery');
        $media2 = $product->addMedia(UploadedFile::fake()->image('test2.jpg'))
            ->toMediaCollection('gallery');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->post("/api/admin/products/{$product->id}/gallery/reorder", [
                'order' => [$media2->id, $media1->id]
            ]);

        $response->assertStatus(200);
        
        $orderedMedia = $product->fresh()->getMedia('gallery');
        $this->assertEquals($media2->id, $orderedMedia[0]->id);
        $this->assertEquals($media1->id, $orderedMedia[1]->id);
    }

    /** @test */
    public function admin_can_update_image_metadata()
    {
        $product = Product::factory()->create();
        
        $media = $product->addMedia(UploadedFile::fake()->image('test.jpg'))
            ->toMediaCollection('gallery');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->patch("/api/admin/products/{$product->id}/gallery/{$media->id}", [
                'alt_text' => 'Updated alt text',
                'caption' => 'Updated caption'
            ]);

        $response->assertStatus(200);
        
        $updatedMedia = $media->fresh();
        $this->assertEquals('Updated alt text', $updatedMedia->getCustomProperty('alt_text'));
        $this->assertEquals('Updated caption', $updatedMedia->getCustomProperty('caption'));
    }

    /** @test */
    public function validation_fails_for_oversized_images()
    {
        $category = Category::factory()->create();
        
        // Create 6MB file (exceeds 5MB limit)
        $response = $this->actingAs($this->admin, 'sanctum')
            ->post('/api/admin/products', [
                'name' => 'Test Product',
                'description' => 'Test description',
                'category_id' => $category->id,
                'base_price' => 29.99,
                'gallery' => [
                    UploadedFile::fake()->create('large.jpg', 6000), // 6MB
                ],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('gallery.0');
    }

    /** @test */
    public function validation_fails_for_too_many_images()
    {
        $category = Category::factory()->create();
        
        $images = [];
        for ($i = 0; $i < 11; $i++) {
            $images[] = UploadedFile::fake()->image("image{$i}.jpg");
        }
        
        $response = $this->actingAs($this->admin, 'sanctum')
            ->post('/api/admin/products', [
                'name' => 'Test Product',
                'description' => 'Test description',
                'category_id' => $category->id,
                'base_price' => 29.99,
                'gallery' => $images,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('gallery');
    }
}
```

Run tests:
```bash
php artisan test --filter=ProductGalleryTest
```

### 2. Manual Testing with Postman

**Collection Download:** [Available in this guide's GitHub repo]

**Test Checklist:**
- ✅ Create product with 5 images
- ✅ Update product (add 3 more images)
- ✅ View product (verify all 8 images)
- ✅ Delete image #4
- ✅ Reorder images (drag image #8 to position #1)
- ✅ Update alt text for image #2
- ✅ Verify responsive URLs work
- ✅ Check queue jobs processed (thumb/medium/large conversions)

---

## Troubleshooting

### 1. Images Not Uploading

**Symptom:** 413 Payload Too Large

**Solution:** Increase PHP upload limits

Edit `php.ini`:
```ini
upload_max_filesize = 10M
post_max_size = 12M
max_execution_time = 300
memory_limit = 256M
```

Restart web server:
```bash
sudo systemctl restart php8.1-fpm
sudo systemctl restart nginx
```

---

### 2. Conversions Not Generating

**Symptom:** Thumb/medium/large URLs return 404

**Solution:** Check queue worker is running

```bash
# Check queue status
php artisan queue:work --once

# Check failed jobs
php artisan queue:failed

# Regenerate conversions manually
php artisan media-library:regenerate --ids=45,46,47
```

---

### 3. Storage Symlink Missing

**Symptom:** Images return 404

**Solution:** Recreate symlink

```bash
# Remove old symlink
rm public/storage

# Create new symlink
php artisan storage:link
```

---

### 4. GD Library Missing

**Symptom:** Error "GD Library extension not enabled"

**Solution:** Install GD

```bash
sudo apt-get install php8.1-gd
sudo systemctl restart php8.1-fpm
```

Verify:
```bash
php -m | grep gd
```

---

### 5. Queue Jobs Stuck

**Symptom:** Images stuck at "Processing..."

**Solution:** Restart queue workers

```bash
# Supervisor
sudo supervisorctl restart samer-queue-worker:*

# Or kill and restart manually
php artisan queue:restart
php artisan queue:work --queue=media --tries=3
```

---

### 6. S3 Upload Fails

**Symptom:** Error "Error executing PutObject"

**Solution:** Check AWS credentials and permissions

`.env`:
```env
AWS_ACCESS_KEY_ID=AKIAIOSFODNN7EXAMPLE
AWS_SECRET_ACCESS_KEY=wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=my-bucket
```

Test connection:
```bash
php artisan tinker
Storage::disk('s3')->put('test.txt', 'Hello World');
Storage::disk('s3')->exists('test.txt'); // Should return true
```

IAM Policy for bucket:
```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": [
        "s3:PutObject",
        "s3:GetObject",
        "s3:DeleteObject"
      ],
      "Resource": "arn:aws:s3:::my-bucket/*"
    },
    {
      "Effect": "Allow",
      "Action": "s3:ListBucket",
      "Resource": "arn:aws:s3:::my-bucket"
    }
  ]
}
```

---

## Performance Optimization

### 1. Database Indexes

```php
// In migration file
Schema::table('media', function (Blueprint $table) {
    $table->index(['model_type', 'model_id', 'collection_name']);
    $table->index('uuid');
});
```

### 2. Eager Loading

Always eager load media to reduce queries:

```php
// Bad (N+1 queries)
$products = Product::all();
foreach ($products as $product) {
    $images = $product->getMedia('gallery'); // Query per product
}

// Good (2 queries total)
$products = Product::with('media')->get();
foreach ($products as $product) {
    $images = $product->getMedia('gallery'); // No extra query
}
```

### 3. Cache Product Data

```php
use Illuminate\Support\Facades\Cache;

$product = Cache::remember("product.{$id}", 3600, function () use ($id) {
    return Product::with(['category', 'variants', 'media'])
        ->findOrFail($id);
});
```

### 4. CDN for Static Assets

Use CloudFront or CloudFlare in front of S3:

```env
AWS_URL=https://d111111abcdef8.cloudfront.net
```

### 5. Image Optimization

Add WebP conversion:

```php
// In Product model
$this->addMediaConversion('webp-thumb')
    ->width(300)
    ->height(300)
    ->format('webp')
    ->quality(80);
```

### 6. Lazy Loading

Use native browser lazy loading:

```html
<img src="image.jpg" loading="lazy" alt="Product" />
```

---

## Summary

You now have a production-ready product gallery system with:

✅ **Clean Architecture** - Form Requests, API Resources, dedicated controller  
✅ **Scalable Storage** - Local + S3 support with CDN  
✅ **Queue System** - Non-blocking image conversions  
✅ **Full CRUD** - Create, read, update, delete products and images  
✅ **Image Management** - Reorder, delete, update metadata  
✅ **Frontend Ready** - Vue 3 examples with drag-and-drop  
✅ **Tested** - Unit tests included  
✅ **Production Ready** - Supervisor config, error handling, logging  

### Next Steps

1. ✅ Deploy to staging environment
2. ✅ Configure S3 + CloudFront
3. ✅ Set up Supervisor for queue workers
4. ✅ Run full test suite
5. ✅ Load test with Apache Bench
6. ✅ Monitor with Laravel Telescope
7. ✅ Deploy to production

### Support

For issues or questions:
- 📧 Email: support@samer.com
- 📚 Docs: https://spatie.be/docs/laravel-medialibrary
- 🐛 GitHub: https://github.com/spatie/laravel-medialibrary

---

**Last Updated:** January 2024  
**Laravel Version:** 10.10  
**Spatie Media Library:** 10.15.0
