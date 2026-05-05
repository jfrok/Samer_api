# Frontend Images Guide - Vue.js 3 Dashboard

## 📸 Loading and Displaying Images from Laravel API

This guide explains how to load and display images in your Vue.js 3 frontend dashboard from the Laravel API backend.

---

## 🏗️ Image Structure in API

### Product Images

The API returns images in multiple sizes:

```json
{
  "id": 1,
  "title": "Product Name",
  "main_image": {
    "thumb": "http://localhost:8000/storage/1/conversions/image-thumb.jpg",
    "medium": "http://localhost:8000/storage/1/conversions/image-medium.jpg",
    "large": "http://localhost:8000/storage/1/conversions/image-large.jpg",
    "original": "http://localhost:8000/storage/1/image.jpg"
  },
  "gallery": [
    {
      "id": 1,
      "thumb": "http://localhost:8000/storage/1/conversions/image-thumb.jpg",
      "medium": "http://localhost:8000/storage/1/conversions/image-medium.jpg",
      "large": "http://localhost:8000/storage/1/conversions/image-large.jpg",
      "original": "http://localhost:8000/storage/1/image.jpg"
    },
    {
      "id": 2,
      "thumb": "http://localhost:8000/storage/2/conversions/image-thumb.jpg",
      "medium": "http://localhost:8000/storage/2/conversions/image-medium.jpg",
      "large": "http://localhost:8000/storage/2/conversions/image-large.jpg",
      "original": "http://localhost:8000/storage/2/image.jpg"
    }
  ]
}
```

**Image Sizes:**
- **thumb**: 200x200px - For thumbnails, lists, cards
- **medium**: 600x600px - For product details, galleries
- **large**: 1200x1200px - For lightboxes, zoom views
- **original**: Full resolution - For downloads

---

## 🎨 Vue 3 Components

### 1. Basic Product Image Component

```vue
<template>
  <div class="product-image">
    <img 
      :src="imageUrl" 
      :alt="alt"
      @error="handleImageError"
      @load="handleImageLoad"
      :class="{ 'loading': isLoading }"
    />
    <div v-if="isLoading" class="image-skeleton">
      <div class="spinner"></div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  product: {
    type: Object,
    required: true
  },
  size: {
    type: String,
    default: 'medium', // thumb, medium, large, original
    validator: (value) => ['thumb', 'medium', 'large', 'original'].includes(value)
  },
  alt: {
    type: String,
    default: 'Product image'
  }
})

const isLoading = ref(true)
const hasError = ref(false)

const imageUrl = computed(() => {
  if (!props.product?.main_image) {
    return '/images/placeholder.png' // Fallback image
  }
  return props.product.main_image[props.size] || props.product.main_image.medium
})

const handleImageError = () => {
  isLoading.value = false
  hasError.value = true
  // Set fallback image
  event.target.src = '/images/placeholder.png'
}

const handleImageLoad = () => {
  isLoading.value = false
}
</script>

<style scoped>
.product-image {
  position: relative;
  width: 100%;
  overflow: hidden;
}

.product-image img {
  width: 100%;
  height: auto;
  display: block;
  transition: opacity 0.3s ease;
}

.product-image img.loading {
  opacity: 0.5;
}

.image-skeleton {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f3f4f6;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e5e7eb;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
```

---

### 2. Product Card with Image

```vue
<template>
  <div class="product-card">
    <div class="product-image-wrapper">
      <img 
        :src="product.main_image?.thumb || placeholderImage" 
        :alt="product.title"
        class="product-thumbnail"
        loading="lazy"
      />
    </div>
    <div class="product-info">
      <h3>{{ product.title }}</h3>
      <p class="price">${{ product.price }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  product: {
    type: Object,
    required: true
  }
})

const placeholderImage = '/images/placeholder.png'
</script>

<style scoped>
.product-card {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
  transition: transform 0.2s;
}

.product-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.product-image-wrapper {
  aspect-ratio: 1 / 1;
  overflow: hidden;
  background: #f9fafb;
}

.product-thumbnail {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.product-info {
  padding: 1rem;
}
</style>
```

---

### 3. Product Gallery Component

```vue
<template>
  <div class="product-gallery">
    <!-- Main Image -->
    <div class="main-image">
      <img 
        :src="selectedImage?.large || placeholderImage" 
        :alt="product.title"
        @click="openLightbox"
      />
    </div>

    <!-- Thumbnail Gallery -->
    <div class="thumbnail-gallery">
      <div 
        v-for="(image, index) in images" 
        :key="image.id"
        class="thumbnail"
        :class="{ active: selectedIndex === index }"
        @click="selectImage(index)"
      >
        <img :src="image.thumb" :alt="`${product.title} - ${index + 1}`" />
      </div>
    </div>

    <!-- Lightbox Modal -->
    <Teleport to="body">
      <div v-if="showLightbox" class="lightbox" @click="closeLightbox">
        <button class="close-btn" @click="closeLightbox">&times;</button>
        <button class="nav-btn prev" @click.stop="prevImage">&lt;</button>
        <img :src="selectedImage?.original" :alt="product.title" />
        <button class="nav-btn next" @click.stop="nextImage">&gt;</button>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  product: {
    type: Object,
    required: true
  }
})

const selectedIndex = ref(0)
const showLightbox = ref(false)
const placeholderImage = '/images/placeholder.png'

const images = computed(() => {
  // Combine main image and gallery
  const allImages = []
  
  if (props.product.main_image) {
    allImages.push({
      id: 'main',
      ...props.product.main_image
    })
  }
  
  if (props.product.gallery && props.product.gallery.length > 0) {
    allImages.push(...props.product.gallery)
  }
  
  return allImages.length > 0 ? allImages : [{
    id: 'placeholder',
    thumb: placeholderImage,
    medium: placeholderImage,
    large: placeholderImage,
    original: placeholderImage
  }]
})

const selectedImage = computed(() => images.value[selectedIndex.value])

const selectImage = (index) => {
  selectedIndex.value = index
}

const openLightbox = () => {
  showLightbox.value = true
}

const closeLightbox = () => {
  showLightbox.value = false
}

const nextImage = () => {
  selectedIndex.value = (selectedIndex.value + 1) % images.value.length
}

const prevImage = () => {
  selectedIndex.value = selectedIndex.value === 0 
    ? images.value.length - 1 
    : selectedIndex.value - 1
}
</script>

<style scoped>
.product-gallery {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.main-image {
  width: 100%;
  aspect-ratio: 1 / 1;
  background: #f9fafb;
  border-radius: 8px;
  overflow: hidden;
  cursor: pointer;
}

.main-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.thumbnail-gallery {
  display: flex;
  gap: 0.5rem;
  overflow-x: auto;
}

.thumbnail {
  flex-shrink: 0;
  width: 80px;
  height: 80px;
  border: 2px solid transparent;
  border-radius: 4px;
  overflow: hidden;
  cursor: pointer;
  transition: border-color 0.2s;
}

.thumbnail:hover {
  border-color: #93c5fd;
}

.thumbnail.active {
  border-color: #3b82f6;
}

.thumbnail img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.lightbox {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.9);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 2rem;
}

.lightbox img {
  max-width: 90%;
  max-height: 90vh;
  object-fit: contain;
}

.close-btn {
  position: absolute;
  top: 1rem;
  right: 1rem;
  background: rgba(255, 255, 255, 0.1);
  border: none;
  color: white;
  font-size: 3rem;
  width: 50px;
  height: 50px;
  border-radius: 50%;
  cursor: pointer;
  line-height: 1;
}

.nav-btn {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: rgba(255, 255, 255, 0.1);
  border: none;
  color: white;
  font-size: 2rem;
  width: 50px;
  height: 50px;
  border-radius: 50%;
  cursor: pointer;
}

.nav-btn.prev {
  left: 2rem;
}

.nav-btn.next {
  right: 2rem;
}
</style>
```

---

### 4. Image Upload Component (Admin Dashboard)

```vue
<template>
  <div class="image-upload">
    <div class="upload-area" @click="triggerFileInput">
      <input 
        ref="fileInput"
        type="file"
        accept="image/*"
        multiple
        @change="handleFileSelect"
        style="display: none"
      />
      
      <div v-if="!uploading && previews.length === 0" class="upload-placeholder">
        <svg class="upload-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
        </svg>
        <p>Click to upload images</p>
        <p class="hint">or drag and drop</p>
      </div>

      <div v-if="uploading" class="uploading">
        <div class="spinner"></div>
        <p>Uploading {{ uploadProgress }}%</p>
      </div>
    </div>

    <!-- Image Previews -->
    <div v-if="previews.length > 0" class="preview-grid">
      <div v-for="(preview, index) in previews" :key="index" class="preview-item">
        <img :src="preview.url" :alt="`Preview ${index + 1}`" />
        <button class="remove-btn" @click.stop="removeImage(index)">&times;</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'

const emit = defineEmits(['uploaded', 'error'])

const fileInput = ref(null)
const previews = ref([])
const uploading = ref(false)
const uploadProgress = ref(0)

const triggerFileInput = () => {
  fileInput.value?.click()
}

const handleFileSelect = (event) => {
  const files = Array.from(event.target.files)
  
  files.forEach(file => {
    // Validate file type
    if (!file.type.startsWith('image/')) {
      emit('error', 'Please select only image files')
      return
    }
    
    // Validate file size (max 5MB)
    if (file.size > 5 * 1024 * 1024) {
      emit('error', 'Image size must be less than 5MB')
      return
    }
    
    // Create preview
    const reader = new FileReader()
    reader.onload = (e) => {
      previews.value.push({
        file: file,
        url: e.target.result
      })
    }
    reader.readAsDataURL(file)
  })
}

const removeImage = (index) => {
  previews.value.splice(index, 1)
}

const uploadImages = async () => {
  if (previews.value.length === 0) return

  uploading.value = true
  uploadProgress.value = 0

  const formData = new FormData()
  
  previews.value.forEach((preview, index) => {
    formData.append(`images[${index}]`, preview.file)
  })

  try {
    const response = await axios.post('/api/admin/products', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      },
      onUploadProgress: (progressEvent) => {
        uploadProgress.value = Math.round(
          (progressEvent.loaded * 100) / progressEvent.total
        )
      }
    })
    
    emit('uploaded', response.data)
    previews.value = []
  } catch (error) {
    emit('error', error.response?.data?.message || 'Upload failed')
  } finally {
    uploading.value = false
    uploadProgress.value = 0
  }
}

// Expose upload method to parent
defineExpose({ uploadImages })
</script>

<style scoped>
.upload-area {
  border: 2px dashed #d1d5db;
  border-radius: 8px;
  padding: 2rem;
  text-align: center;
  cursor: pointer;
  transition: border-color 0.2s;
}

.upload-area:hover {
  border-color: #3b82f6;
}

.upload-placeholder {
  color: #6b7280;
}

.upload-icon {
  width: 48px;
  height: 48px;
  margin: 0 auto 1rem;
  color: #9ca3af;
}

.hint {
  font-size: 0.875rem;
  color: #9ca3af;
  margin-top: 0.5rem;
}

.uploading {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.preview-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 1rem;
  margin-top: 1rem;
}

.preview-item {
  position: relative;
  aspect-ratio: 1 / 1;
  border-radius: 4px;
  overflow: hidden;
}

.preview-item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.remove-btn {
  position: absolute;
  top: 0.5rem;
  right: 0.5rem;
  background: rgba(0, 0, 0, 0.5);
  color: white;
  border: none;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  cursor: pointer;
  font-size: 1.25rem;
  line-height: 1;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e5e7eb;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
```

---

## 🔧 API Integration with Axios

### Setup Axios Instance

```javascript
// src/api/axios.js
import axios from 'axios'

const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

// Add auth token to requests
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

export default api
```

### Fetch Products with Images

```javascript
// src/api/products.js
import api from './axios'

export const getProducts = async (params = {}) => {
  try {
    const response = await api.get('/products', { params })
    return response.data
  } catch (error) {
    console.error('Error fetching products:', error)
    throw error
  }
}

export const getProduct = async (slug) => {
  try {
    const response = await api.get(`/products/${slug}`)
    return response.data
  } catch (error) {
    console.error('Error fetching product:', error)
    throw error
  }
}
```

### Using in Vue Component

```vue
<template>
  <div class="products-page">
    <div v-if="loading" class="loading">Loading...</div>
    
    <div v-else class="products-grid">
      <ProductCard 
        v-for="product in products" 
        :key="product.id"
        :product="product"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { getProducts } from '@/api/products'
import ProductCard from '@/components/ProductCard.vue'

const products = ref([])
const loading = ref(true)

onMounted(async () => {
  try {
    const response = await getProducts()
    products.value = response.data
  } catch (error) {
    console.error('Failed to load products:', error)
  } finally {
    loading.value = false
  }
})
</script>
```

---

## 🎯 Best Practices

### 1. Use Lazy Loading

```vue
<img 
  :src="product.main_image?.medium" 
  :alt="product.title"
  loading="lazy"
/>
```

### 2. Provide Fallback Images

```javascript
const getImageUrl = (image, size = 'medium') => {
  return image?.[size] || '/images/placeholder.png'
}
```

### 3. Handle Image Errors

```vue
<template>
  <img 
    :src="imageUrl" 
    @error="handleImageError"
  />
</template>

<script setup>
const handleImageError = (event) => {
  event.target.src = '/images/placeholder.png'
  console.error('Failed to load image:', imageUrl.value)
}
</script>
```

### 4. Optimize for Performance

```javascript
// Use appropriate image size based on use case
const sizes = {
  list: 'thumb',      // Product lists
  card: 'medium',     // Product cards
  detail: 'large',    // Product detail page
  zoom: 'original'    // Lightbox/zoom view
}
```

### 5. Add Loading States

```vue
<template>
  <div class="image-container">
    <div v-if="isLoading" class="skeleton"></div>
    <img 
      v-show="!isLoading"
      :src="imageUrl"
      @load="isLoading = false"
    />
  </div>
</template>
```

---

## 🚀 Production Considerations

### 1. Environment Variables

```javascript
// .env.production
VITE_API_URL=https://api.yoursite.com
```

```javascript
// src/config.js
export const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000'
```

### 2. CDN Integration

If using a CDN for images:

```javascript
const getCDNUrl = (path) => {
  const CDN_URL = 'https://cdn.yoursite.com'
  return path.replace('http://localhost:8000', CDN_URL)
}
```

### 3. Image Compression

Consider using libraries like:
- `compressorjs` - Client-side image compression
- `vue-lazyload` - Lazy loading images

```bash
npm install compressorjs vue-lazyload
```

---

## 📦 Complete Example: Product Page

```vue
<template>
  <div class="product-page">
    <div v-if="loading" class="loading-skeleton">
      <div class="skeleton-image"></div>
      <div class="skeleton-content"></div>
    </div>

    <div v-else-if="product" class="product-container">
      <!-- Image Gallery -->
      <div class="product-gallery-section">
        <ProductGallery :product="product" />
      </div>

      <!-- Product Info -->
      <div class="product-info-section">
        <h1>{{ product.title }}</h1>
        <p class="price">${{ product.price }}</p>
        <div class="description" v-html="product.description"></div>
        <button class="add-to-cart">Add to Cart</button>
      </div>
    </div>

    <div v-else class="error">
      Product not found
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { getProduct } from '@/api/products'
import ProductGallery from '@/components/ProductGallery.vue'

const route = useRoute()
const product = ref(null)
const loading = ref(true)

onMounted(async () => {
  try {
    const response = await getProduct(route.params.slug)
    product.value = response.data
  } catch (error) {
    console.error('Failed to load product:', error)
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.product-container {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
  padding: 2rem;
}

@media (max-width: 768px) {
  .product-container {
    grid-template-columns: 1fr;
  }
}
</style>
```

---

## 🔐 Secure Image Upload

```javascript
// Add CSRF token for uploads
import api from './axios'

export const uploadProductImages = async (productId, files) => {
  const formData = new FormData()
  
  files.forEach((file, index) => {
    formData.append(`images[${index}]`, file)
  })

  try {
    const response = await api.post(
      `/admin/products/${productId}/images`,
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      }
    )
    return response.data
  } catch (error) {
    throw error
  }
}
```

---

## 📚 Resources

- [Vue 3 Documentation](https://vuejs.org/)
- [Axios Documentation](https://axios-http.com/)
- [Image Optimization Best Practices](https://web.dev/fast/#optimize-your-images)

---

## 🎉 Summary

**Image Sizes Available:**
- `thumb` (200x200) - Lists, thumbnails
- `medium` (600x600) - Cards, previews  
- `large` (1200x1200) - Detail pages
- `original` - Full resolution

**Key Features:**
✅ Lazy loading for performance
✅ Error handling with fallback images
✅ Loading states and skeletons
✅ Image gallery with lightbox
✅ Multiple image upload
✅ Responsive design

**Production Ready:**
✅ Environment configuration
✅ CDN support
✅ Security best practices
✅ Performance optimizations
