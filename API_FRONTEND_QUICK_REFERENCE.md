# Product Gallery API - Quick Reference

## 🚀 Quick Start for Frontend Developers

### Base Configuration

```javascript
// axios-config.js
import axios from 'axios'

const API = axios.create({
  baseURL: 'http://localhost/api',
  headers: {
    'Accept': 'application/json'
  }
})

// Add token to all requests
API.interceptors.request.use(config => {
  const token = localStorage.getItem('auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

export default API
```

---

## 📸 Image Upload Examples

### 1. Create Product with Images

```javascript
// ProductCreateForm.vue
import API from './axios-config'

async function createProduct() {
  const formData = new FormData()
  
  // Basic fields
  formData.append('name', 'Nike Air Max')
  formData.append('description', 'Premium running shoes')
  formData.append('category_id', 5)
  formData.append('brand', 'Nike')
  formData.append('base_price', 149.99)
  formData.append('is_active', 1)
  
  // Gallery images (from file input)
  const files = document.querySelector('#gallery-input').files
  Array.from(files).forEach((file, index) => {
    formData.append('gallery[]', file)
    formData.append(`gallery_alt[${index}]`, `Nike Air Max - View ${index + 1}`)
    formData.append(`gallery_caption[${index}]`, '') // Optional
  })
  
  // Variants (optional)
  formData.append('variants[0][size]', 'US 9')
  formData.append('variants[0][color]', 'White')
  formData.append('variants[0][price]', 149.99)
  formData.append('variants[0][stock]', 25)
  
  try {
    const response = await API.post('/admin/products', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      },
      onUploadProgress: (progressEvent) => {
        const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total)
        console.log(`Upload: ${percentCompleted}%`)
      }
    })
    
    console.log('Product created:', response.data)
    return response.data
  } catch (error) {
    console.error('Error:', error.response?.data)
    throw error
  }
}
```

### 2. Update Product (Add More Images)

```javascript
async function updateProduct(productId) {
  const formData = new FormData()
  
  // Update basic fields (optional - only send what changed)
  formData.append('name', 'Nike Air Max Pro')
  formData.append('base_price', 169.99)
  
  // Add new gallery images
  const newFiles = document.querySelector('#new-images-input').files
  Array.from(newFiles).forEach((file, index) => {
    formData.append('gallery[]', file)
    formData.append(`gallery_alt[${index}]`, `New Image ${index + 1}`)
  })
  
  // Laravel requires _method for PUT via FormData
  formData.append('_method', 'PUT')
  
  try {
    const response = await API.post(`/admin/products/${productId}?_method=PUT`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    
    console.log('Product updated:', response.data)
    return response.data
  } catch (error) {
    console.error('Error:', error.response?.data)
    throw error
  }
}
```

---

## 🖼️ Display Images

### 3. Show Product Gallery (Responsive)

```vue
<template>
  <div class="product-gallery">
    <!-- Main Image -->
    <div class="main-image">
      <img 
        v-if="currentImage"
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
        v-for="(image, index) in gallery"
        :key="image.id"
        :src="image.conversions.find(c => c.name === 'thumb').url"
        :alt="image.custom_properties.alt_text"
        @click="selectImage(index)"
        :class="{ active: currentIndex === index }"
        loading="lazy"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  gallery: Array // From product.gallery
})

const currentIndex = ref(0)

const currentImage = computed(() => {
  return props.gallery[currentIndex.value]
})

const selectImage = (index) => {
  currentIndex.value = index
}
</script>

<style scoped>
.main-image img {
  width: 100%;
  max-width: 800px;
  height: auto;
  object-fit: contain;
}

.thumbnails {
  display: flex;
  gap: 10px;
  margin-top: 15px;
  overflow-x: auto;
}

.thumbnails img {
  width: 80px;
  height: 80px;
  object-fit: cover;
  cursor: pointer;
  border: 2px solid transparent;
  border-radius: 4px;
}

.thumbnails img.active {
  border-color: #007bff;
}
</style>
```

### 4. Product Card (Listing)

```vue
<template>
  <div class="product-card">
    <router-link :to="`/products/${product.slug}`">
      <!-- Featured Image (first gallery image) -->
      <img 
        v-if="product.featured_image"
        :src="product.featured_image.medium"
        :alt="product.featured_image.alt_text"
        loading="lazy"
        class="product-image"
      />
      
      <div class="product-info">
        <h3>{{ product.name }}</h3>
        <p class="brand">{{ product.brand }}</p>
        
        <!-- Show price range if variants exist -->
        <p v-if="product.price_range" class="price">
          ${{ product.price_range.formatted }}
        </p>
        <p v-else class="price">
          ${{ product.base_price }}
        </p>
        
        <span class="gallery-count">
          📷 {{ product.gallery_count }} photos
        </span>
      </div>
    </router-link>
  </div>
</template>

<script setup>
defineProps({
  product: Object
})
</script>

<style scoped>
.product-card {
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  overflow: hidden;
  transition: transform 0.3s;
}

.product-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.product-image {
  width: 100%;
  height: 250px;
  object-fit: cover;
}

.product-info {
  padding: 15px;
}

.gallery-count {
  font-size: 12px;
  color: #666;
}
</style>
```

---

## 🎯 Gallery Management (Admin)

### 5. Delete Specific Image

```javascript
async function deleteImage(productId, mediaId) {
  if (!confirm('Are you sure you want to delete this image?')) {
    return
  }
  
  try {
    const response = await API.delete(`/admin/products/${productId}/gallery/${mediaId}`)
    console.log('Image deleted:', response.data.message)
    
    // Remove from local state
    gallery.value = gallery.value.filter(img => img.id !== mediaId)
    
    return response.data
  } catch (error) {
    console.error('Error:', error.response?.data)
    throw error
  }
}
```

### 6. Reorder Images (Drag & Drop)

```vue
<template>
  <div class="gallery-editor">
    <h3>Manage Gallery (Drag to Reorder)</h3>
    
    <draggable 
      v-model="images" 
      @end="saveOrder"
      item-key="id"
      class="image-grid"
      handle=".drag-handle"
    >
      <template #item="{ element, index }">
        <div class="image-item">
          <span class="drag-handle">⋮⋮</span>
          <img :src="element.conversions.find(c => c.name === 'thumb').url" />
          <span class="order-number">{{ index + 1 }}</span>
          <button @click="deleteImage(element.id)" class="delete-btn">×</button>
        </div>
      </template>
    </draggable>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import draggable from 'vuedraggable'
import API from './axios-config'

const props = defineProps({
  productId: Number,
  initialImages: Array
})

const images = ref([...props.initialImages])

async function saveOrder() {
  const order = images.value.map(img => img.id)
  
  try {
    const response = await API.post(`/admin/products/${props.productId}/gallery/reorder`, {
      order
    })
    
    console.log('Order saved:', response.data.message)
  } catch (error) {
    console.error('Error:', error.response?.data)
    // Revert order on error
    images.value = [...props.initialImages]
  }
}

async function deleteImage(mediaId) {
  if (!confirm('Delete this image?')) return
  
  try {
    await API.delete(`/admin/products/${props.productId}/gallery/${mediaId}`)
    images.value = images.value.filter(img => img.id !== mediaId)
  } catch (error) {
    console.error('Error:', error.response?.data)
  }
}
</script>

<style scoped>
.image-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 15px;
}

.image-item {
  position: relative;
  border: 2px solid #ddd;
  border-radius: 8px;
  padding: 10px;
  cursor: move;
}

.drag-handle {
  position: absolute;
  top: 5px;
  left: 5px;
  cursor: grab;
  font-size: 20px;
  color: #999;
}

.order-number {
  position: absolute;
  top: 5px;
  right: 5px;
  background: rgba(0,0,0,0.7);
  color: white;
  width: 25px;
  height: 25px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: bold;
}

.delete-btn {
  position: absolute;
  bottom: 10px;
  right: 10px;
  background: #dc3545;
  color: white;
  border: none;
  border-radius: 50%;
  width: 30px;
  height: 30px;
  font-size: 20px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>
```

### 7. Update Image Metadata (Alt Text / Caption)

```javascript
async function updateImageMetadata(productId, mediaId, altText, caption) {
  try {
    const response = await API.patch(`/admin/products/${productId}/gallery/${mediaId}`, {
      alt_text: altText,
      caption: caption
    })
    
    console.log('Metadata updated:', response.data)
    return response.data
  } catch (error) {
    console.error('Error:', error.response?.data)
    throw error
  }
}

// Usage example
const newAltText = prompt('Enter new alt text:')
if (newAltText) {
  updateImageMetadata(productId, mediaId, newAltText, '')
}
```

---

## 📦 Complete Product Fetch

### 8. Fetch Product with Gallery

```javascript
async function fetchProduct(productId) {
  try {
    const response = await API.get(`/admin/products/${productId}`)
    
    const product = response.data.data
    
    console.log('Product:', product)
    console.log('Gallery images:', product.gallery.length)
    
    // Example gallery structure:
    // product.gallery[0].conversions = [
    //   { name: 'thumb', url: '...', width: 300, height: 300 },
    //   { name: 'medium', url: '...', width: 600, height: 600 },
    //   { name: 'large', url: '...', width: 1200, height: 1200 }
    // ]
    
    return product
  } catch (error) {
    console.error('Error:', error.response?.data)
    throw error
  }
}
```

### 9. Fetch Products List (with Pagination)

```javascript
async function fetchProducts(page = 1, filters = {}) {
  try {
    const params = {
      page,
      per_page: 20,
      ...filters // { search: 'nike', category_id: 5, is_active: 1 }
    }
    
    const response = await API.get('/admin/products', { params })
    
    const { data, meta, links } = response.data
    
    console.log('Products:', data)
    console.log('Current page:', meta.current_page)
    console.log('Total products:', meta.total)
    
    // Each product has:
    // - featured_image (first gallery image with thumb/medium)
    // - gallery_count (total images)
    // - price_range (min/max from variants)
    
    return response.data
  } catch (error) {
    console.error('Error:', error.response?.data)
    throw error
  }
}
```

---

## 🛡️ Error Handling

### 10. Handle Validation Errors

```javascript
async function handleProductSubmit(formData) {
  try {
    const response = await API.post('/admin/products', formData)
    return response.data
  } catch (error) {
    if (error.response?.status === 422) {
      // Validation errors
      const errors = error.response.data.errors
      
      // Display errors
      Object.keys(errors).forEach(field => {
        console.error(`${field}:`, errors[field].join(', '))
        
        // Example errors:
        // gallery.0: ["The gallery.0 must be an image.", "The gallery.0 must not be greater than 5120 kilobytes."]
        // name: ["The name field is required."]
        // base_price: ["The base price must be at least 0."]
      })
      
      // Show user-friendly message
      alert('Please fix the following errors:\n' + 
            Object.entries(errors)
              .map(([field, messages]) => `- ${field}: ${messages[0]}`)
              .join('\n'))
    } else if (error.response?.status === 401) {
      // Unauthorized - redirect to login
      router.push('/login')
    } else {
      // Server error
      alert('An error occurred. Please try again.')
    }
    
    throw error
  }
}
```

---

## 🎨 Image Display Helpers

### 11. Get Optimal Image Size

```javascript
// Helper function to get best image size based on viewport
function getOptimalImage(image, containerWidth) {
  const conversions = image.conversions
  
  if (containerWidth <= 300) {
    return conversions.find(c => c.name === 'thumb').url
  } else if (containerWidth <= 600) {
    return conversions.find(c => c.name === 'medium').url
  } else {
    return conversions.find(c => c.name === 'large').url
  }
}

// Usage
const imageUrl = getOptimalImage(product.gallery[0], window.innerWidth)
```

### 12. Preload Main Product Image

```javascript
// Preload first gallery image for faster display
function preloadMainImage(product) {
  if (product.featured_image?.medium) {
    const link = document.createElement('link')
    link.rel = 'preload'
    link.as = 'image'
    link.href = product.featured_image.medium
    document.head.appendChild(link)
  }
}
```

---

## 📊 API Response Examples

### Product List Response

```json
{
  "data": [
    {
      "id": 1,
      "name": "Nike Air Max",
      "slug": "nike-air-max",
      "brand": "Nike",
      "base_price": 149.99,
      "is_active": true,
      "category": {
        "id": 5,
        "name": "Shoes",
        "slug": "shoes"
      },
      "featured_image": {
        "id": 45,
        "thumb": "http://localhost/storage/products/1/thumb.jpg",
        "medium": "http://localhost/storage/products/1/medium.jpg",
        "alt_text": "Nike Air Max - White"
      },
      "gallery_count": 6,
      "variants_count": 4,
      "price_range": {
        "min": 129.99,
        "max": 169.99,
        "formatted": "129.99 - 169.99"
      },
      "created_at": "2024-01-15T10:30:00.000000Z",
      "updated_at": "2024-01-20T14:22:00.000000Z"
    }
  ],
  "links": {
    "first": "http://localhost/api/admin/products?page=1",
    "last": "http://localhost/api/admin/products?page=5",
    "prev": null,
    "next": "http://localhost/api/admin/products?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 15,
    "to": 15,
    "total": 73
  }
}
```

### Product Detail Response

```json
{
  "data": {
    "id": 1,
    "name": "Nike Air Max",
    "slug": "nike-air-max",
    "description": "Premium running shoes with Air Max cushioning",
    "brand": "Nike",
    "base_price": 149.99,
    "is_active": true,
    "category": {
      "id": 5,
      "name": "Shoes",
      "slug": "shoes"
    },
    "gallery": [
      {
        "id": 45,
        "uuid": "a1b2c3d4-e5f6-7890-abcd-1234567890ab",
        "order": 1,
        "original_url": "http://localhost/storage/products/1/original-45.jpg",
        "conversions": [
          {
            "name": "thumb",
            "url": "http://localhost/storage/products/1/conversions/thumb-45.jpg",
            "width": 300,
            "height": 300
          },
          {
            "name": "medium",
            "url": "http://localhost/storage/products/1/conversions/medium-45.jpg",
            "width": 600,
            "height": 600
          },
          {
            "name": "large",
            "url": "http://localhost/storage/products/1/conversions/large-45.jpg",
            "width": 1200,
            "height": 1200
          }
        ],
        "custom_properties": {
          "alt_text": "Nike Air Max - White - Front View",
          "caption": "Official product photography"
        },
        "responsive": {
          "srcset": "http://localhost/storage/products/1/conversions/thumb-45.jpg 300w, http://localhost/storage/products/1/conversions/medium-45.jpg 600w, http://localhost/storage/products/1/conversions/large-45.jpg 1200w",
          "sizes": "(max-width: 600px) 100vw, (max-width: 1200px) 50vw, 1200px"
        },
        "file_name": "original-45.jpg",
        "mime_type": "image/jpeg",
        "size": 245678
      }
    ],
    "variants": [
      {
        "id": 1,
        "size": "US 9",
        "color": "White",
        "sku": "NAM-US9-WHI-123456",
        "price": 149.99,
        "stock": 25
      }
    ],
    "created_at": "2024-01-15T10:30:00.000000Z",
    "updated_at": "2024-01-20T14:22:00.000000Z"
  }
}
```

---

## 🔥 Common Validation Rules

```
name: required, string, max:255
description: required, string
category_id: required, exists:categories,id
brand: nullable, string, max:100
base_price: required, numeric, min:0
is_active: nullable, boolean
gallery: nullable, array, max:10
gallery.*: required, file, mimes:jpeg,jpg,png,gif,webp, max:5120 (5MB)
         dimensions:min_width=100,min_height=100,max_width=4000,max_height=4000
gallery_alt.*: nullable, string, max:255
gallery_caption.*: nullable, string, max:500
```

---

## ⚡ Performance Tips

1. **Use `loading="lazy"`** on all product images
2. **Implement intersection observer** for infinite scroll
3. **Cache product lists** in Vuex/Pinia
4. **Use throttle/debounce** for search input
5. **Preload featured images** for better LCP score
6. **Use responsive images** (`srcset`) for bandwidth savings

---

## 🐛 Debugging

### Check Queue Status

```bash
# In terminal (for developers)
php artisan queue:work --once

# Check failed jobs
php artisan queue:failed
```

### Verify Storage Link

```bash
php artisan storage:link
```

### Test Image Upload

```javascript
// Test with small file first
const testFile = new File(['test'], 'test.jpg', { type: 'image/jpeg' })
const formData = new FormData()
formData.append('gallery[]', testFile)
// ... rest of form data
```

---

## 📞 Support

For API issues, contact:
- Backend Team: backend@samer.com
- API Docs: http://localhost/api/documentation

---

**Last Updated:** January 2024  
**API Version:** 1.6
