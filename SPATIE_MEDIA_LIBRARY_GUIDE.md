# Spatie Media Library - Implementation Guide

## Overview

This project uses **Spatie Laravel Media Library v10.15** for comprehensive media management (images, documents, files). It provides an elegant way to associate files with Eloquent models and handles file uploads, conversions, and storage.

### Why Spatie Media Library?

- **Easy file management** - Associate multiple files with any model
- **Automatic conversions** - Auto-generate thumbnails and image variants
- **Responsive images** - Create multiple sizes for different devices
- **Custom properties** - Store metadata with media files
- **Collections** - Organize media into different collections (avatar, gallery, documents)
- **Built-in validation** - MIME type and size validation
- **Cloud storage support** - Works with S3, local, and other drivers

---

## Installation Status

✅ **Package installed:** `spatie/laravel-medialibrary: ^10.15`  
✅ **Database migrated:** Media table created (`2026_02_12_002206_create_media_table.php`)  
✅ **Configuration published:** `config/media-library.php`

---

## Current Implementation

### User Model - Avatar Support

The `User` model is configured with media library to support avatar uploads:

```php
// app/Models/User.php

use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Authenticatable implements HasMedia
{
    use InteractsWithMedia;

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()  // Only one avatar per user
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    }
}
```

**Features:**
- **Collection:** `avatar`
- **Single file:** Automatically replaces old avatar when new one is uploaded
- **Accepted formats:** JPEG, PNG, GIF, WebP
- **Auto-cleanup:** Old files are deleted when replaced

---

## How to Use Media Library

### 1. Add Media Support to a Model

```php
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        // Main product image (single)
        $this->addMediaCollection('main_image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->registerMediaConversions(function () {
                $this->addMediaConversion('thumb')
                    ->width(150)
                    ->height(150);
                    
                $this->addMediaConversion('medium')
                    ->width(500)
                    ->height(500);
            });

        // Product gallery (multiple images)
        $this->addMediaCollection('gallery')
            ->registerMediaConversions(function () {
                $this->addMediaConversion('thumb')
                    ->width(200)
                    ->height(200);
            });

        // Product documents
        $this->addMediaCollection('documents')
            ->acceptsMimeTypes(['application/pdf', 'application/msword']);
    }
}
```

### 2. Upload Files

```php
// Upload from request
$user->addMediaFromRequest('avatar')->toMediaCollection('avatar');

// Upload from path
$user->addMedia(storage_path('temp/avatar.jpg'))
    ->toMediaCollection('avatar');

// Upload from URL
$user->addMediaFromUrl('https://example.com/image.jpg')
    ->toMediaCollection('avatar');

// Upload with custom properties
$user->addMediaFromRequest('avatar')
    ->withCustomProperties(['uploaded_by' => auth()->id()])
    ->toMediaCollection('avatar');
```

### 3. Retrieve Media

```php
// Get first media item
$avatar = $user->getFirstMedia('avatar');

// Get all media in collection
$galleryImages = $product->getMedia('gallery');

// Get media URL
$avatarUrl = $user->getFirstMediaUrl('avatar');

// Get conversion URL
$thumbnailUrl = $user->getFirstMediaUrl('avatar', 'thumb');

// Get all URLs
$urls = $user->getMedia('gallery')->map(function ($media) {
    return $media->getUrl();
});
```

### 4. Delete Media

```php
// Delete specific media
$media = $user->getFirstMedia('avatar');
$media->delete();

// Clear entire collection
$user->clearMediaCollection('avatar');

// Clear all media
$user->clearMediaCollectionExcept('avatar'); // Keep avatar, delete others
```

---

## API Implementation Examples

### Upload User Avatar

**Controller Example:**

```php
// app/Http/Controllers/API/ProfileController.php

public function uploadAvatar(Request $request)
{
    $request->validate([
        'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048', // 2MB max
    ]);

    $user = auth()->user();

    // Upload and replace old avatar
    $user->addMediaFromRequest('avatar')
        ->toMediaCollection('avatar');

    return response()->json([
        'message' => 'Avatar uploaded successfully',
        'avatar_url' => $user->getFirstMediaUrl('avatar'),
        'thumbnail_url' => $user->getFirstMediaUrl('avatar', 'thumb'),
    ]);
}

public function deleteAvatar()
{
    $user = auth()->user();
    $user->clearMediaCollection('avatar');

    return response()->json([
        'message' => 'Avatar deleted successfully',
    ]);
}

public function show(Request $request)
{
    $user = $request->user();

    return response()->json([
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar_url' => $user->getFirstMediaUrl('avatar'),
            'roles' => $user->roles->pluck('name'),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]
    ]);
}
```

**Routes:**

```php
// routes/api.php

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar']);
    Route::delete('/profile/avatar', [ProfileController::class, 'deleteAvatar']);
    Route::get('/profile', [ProfileController::class, 'show']);
});
```

---

## Frontend Integration

### Upload Avatar (JavaScript/React)

```javascript
// Upload avatar
async function uploadAvatar(file) {
  const formData = new FormData();
  formData.append('avatar', file);

  const response = await fetch('http://localhost:8000/api/profile/avatar', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      // Don't set Content-Type - browser will set it with boundary
    },
    body: formData
  });

  const data = await response.json();
  console.log('Avatar uploaded:', data.avatar_url);
  return data;
}

// Delete avatar
async function deleteAvatar() {
  const response = await fetch('http://localhost:8000/api/profile/avatar', {
    method: 'DELETE',
    headers: {
      'Authorization': `Bearer ${token}`,
    }
  });

  return await response.json();
}

// Get user with avatar
async function getUserProfile() {
  const response = await fetch('http://localhost:8000/api/profile', {
    headers: {
      'Authorization': `Bearer ${token}`,
    }
  });

  const data = await response.json();
  return data.user; // Contains avatar_url
}
```

### React Component Example

```jsx
import React, { useState } from 'react';

function AvatarUpload() {
  const [avatarUrl, setAvatarUrl] = useState('');
  const [uploading, setUploading] = useState(false);

  const handleFileChange = async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    setUploading(true);
    const formData = new FormData();
    formData.append('avatar', file);

    try {
      const response = await fetch('http://localhost:8000/api/profile/avatar', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
        },
        body: formData
      });

      const data = await response.json();
      setAvatarUrl(data.avatar_url);
      alert('Avatar uploaded successfully!');
    } catch (error) {
      console.error('Upload failed:', error);
      alert('Failed to upload avatar');
    } finally {
      setUploading(false);
    }
  };

  return (
    <div>
      <h3>Upload Avatar</h3>
      {avatarUrl && <img src={avatarUrl} alt="Avatar" width="150" />}
      <input 
        type="file" 
        accept="image/*" 
        onChange={handleFileChange}
        disabled={uploading}
      />
      {uploading && <p>Uploading...</p>}
    </div>
  );
}

export default AvatarUpload;
```

---

## Advanced Features

### 1. Image Conversions (Thumbnails)

Create different sizes automatically:

```php
public function registerMediaCollections(): void
{
    $this->addMediaCollection('avatar')
        ->singleFile()
        ->registerMediaConversions(function () {
            $this->addMediaConversion('thumb')
                ->width(150)
                ->height(150)
                ->sharpen(10);
                
            $this->addMediaConversion('medium')
                ->width(300)
                ->height(300)
                ->optimize();
                
            $this->addMediaConversion('large')
                ->width(800)
                ->height(800)
                ->quality(90);
        });
}

// Usage
$thumbUrl = $user->getFirstMediaUrl('avatar', 'thumb');
$mediumUrl = $user->getFirstMediaUrl('avatar', 'medium');
$largeUrl = $user->getFirstMediaUrl('avatar', 'large');
```

### 2. Responsive Images

Generate multiple sizes for responsive images:

```php
$this->addMediaConversion('responsive')
    ->withResponsiveImages()
    ->fit(Manipulations::FIT_CROP, 800, 600);

// Get responsive image srcset
$media = $user->getFirstMedia('avatar');
$srcset = $media->getSrcset('responsive');
```

### 3. Custom Properties

Store metadata with media:

```php
// Add custom properties on upload
$user->addMediaFromRequest('avatar')
    ->withCustomProperties([
        'uploaded_by' => auth()->id(),
        'description' => 'User profile photo',
        'alt_text' => 'Profile picture of ' . $user->name,
    ])
    ->toMediaCollection('avatar');

// Retrieve custom properties
$media = $user->getFirstMedia('avatar');
$uploadedBy = $media->getCustomProperty('uploaded_by');
$altText = $media->getCustomProperty('alt_text');
```

### 4. File Validation

```php
public function uploadDocument(Request $request)
{
    $request->validate([
        'document' => [
            'required',
            'file',
            'mimes:pdf,doc,docx',
            'max:10240', // 10MB
        ],
    ]);

    $user = auth()->user();
    
    $user->addMediaFromRequest('document')
        ->toMediaCollection('documents');

    return response()->json(['message' => 'Document uploaded']);
}
```

### 5. Temporary Uploads

```php
// Upload to temporary collection first
$media = $model->addMediaFromRequest('image')
    ->toMediaCollection('temp');

// Move to permanent collection after validation
$media->move($model, 'gallery');
```

---

## Common Use Cases

### Product Images

```php
// app/Models/Product.php
public function registerMediaCollections(): void
{
    $this->addMediaCollection('main_image')->singleFile();
    $this->addMediaCollection('gallery'); // Multiple images
}

// Upload main image
$product->addMediaFromRequest('main_image')->toMediaCollection('main_image');

// Upload gallery images
foreach ($request->file('gallery_images') as $image) {
    $product->addMedia($image)->toMediaCollection('gallery');
}

// Get URLs
$mainImageUrl = $product->getFirstMediaUrl('main_image');
$galleryUrls = $product->getMedia('gallery')->map->getUrl();
```

### Category Icons

```php
public function registerMediaCollections(): void
{
    $this->addMediaCollection('icon')
        ->singleFile()
        ->acceptsMimeTypes(['image/svg+xml', 'image/png']);
}
```

### Order Receipts

```php
public function registerMediaCollections(): void
{
    $this->addMediaCollection('receipt')
        ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png']);
}
```

---

## Configuration

The media library configuration is located at `config/media-library.php`. Key settings:

```php
return [
    // Default disk for media storage
    'disk_name' => env('MEDIA_DISK', 'public'),

    // Maximum file size (bytes)
    'max_file_size' => 1024 * 1024 * 10, // 10MB

    // Path prefix for media files
    'path_generator' => null, // Uses default

    // Queue conversions
    'queue_conversions_by_default' => env('QUEUE_CONVERSIONS', false),
];
```

---

## Database Structure

The `media` table structure:

```sql
- id
- model_type (e.g., 'App\Models\User')
- model_id (e.g., user ID)
- uuid
- collection_name (e.g., 'avatar')
- name (original file name)
- file_name (stored file name)
- mime_type
- disk
- size (bytes)
- manipulations (JSON)
- custom_properties (JSON)
- responsive_images (JSON)
- order_column
- created_at
- updated_at
```

---

## Testing

```php
// Test avatar upload
public function test_user_can_upload_avatar()
{
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('avatar.jpg', 600, 600);

    $this->actingAs($user)
        ->post('/api/profile/avatar', ['avatar' => $file])
        ->assertStatus(200);

    $this->assertTrue($user->hasMedia('avatar'));
}
```

---

## Troubleshooting

### Issue: Images not appearing

**Solution:** Make sure storage is linked:
```bash
php artisan storage:link
```

### Issue: Conversions not generated

**Solution:** Install image processing library:
```bash
# For GD
sudo apt-get install php-gd

# For ImageMagick
sudo apt-get install php-imagick
```

### Issue: File permissions

**Solution:** Ensure storage directories are writable:
```bash
chmod -R 775 storage
chmod -R 775 public/storage
```

---

## Next Steps

### Recommended Implementations

1. **Product Images**
   - Add media support to Product model
   - Create upload endpoints
   - Implement image gallery

2. **Category Icons**
   - Add icon collection to Category model
   - Upload SVG or PNG icons

3. **Order Receipts**
   - Store PDF receipts for orders
   - Allow customers to download

4. **User Documents**
   - ID verification uploads
   - Business documents for sellers

---

## Resources

- **Official Documentation:** https://spatie.be/docs/laravel-medialibrary/v10/introduction
- **Package Repository:** https://github.com/spatie/laravel-medialibrary
- **Image Manipulations:** https://spatie.be/docs/laravel-medialibrary/v10/converting-images/defining-conversions

---

## Support

For issues or questions about Spatie Media Library in this project, check:
1. Official documentation
2. GitHub issues: https://github.com/spatie/laravel-medialibrary/issues
3. Stack Overflow: Tag `laravel-medialibrary`

---

**Last Updated:** February 14, 2026  
**Package Version:** spatie/laravel-medialibrary v10.15  
**Laravel Version:** 10.10
