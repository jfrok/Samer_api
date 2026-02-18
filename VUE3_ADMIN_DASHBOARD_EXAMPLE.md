# Vue 3 Admin Dashboard - Complete Example

## 📋 Table of Contents
1. [Project Setup](#project-setup)
2. [File Structure](#file-structure)
3. [Core Components](#core-components)
4. [Complete Code Examples](#complete-code-examples)
5. [Installation Guide](#installation-guide)

---

## Project Setup

### 1. Create Vue 3 Project

```bash
# Using Vite (Recommended)
npm create vite@latest samer-admin -- --template vue

cd samer-admin
npm install

# Install required dependencies
npm install axios pinia vue-router vuedraggable@next
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init -p
```

### 2. Install UI Library (Optional but Recommended)

```bash
# Option 1: Element Plus (Recommended)
npm install element-plus
npm install @element-plus/icons-vue

# Option 2: Ant Design Vue
npm install ant-design-vue

# Option 3: PrimeVue
npm install primevue primeicons
```

---

## File Structure

```
samer-admin/
├── src/
│   ├── api/
│   │   ├── axios.js          # Axios configuration
│   │   ├── products.js       # Product API calls
│   │   └── auth.js           # Authentication API
│   ├── components/
│   │   ├── Layout/
│   │   │   ├── AdminLayout.vue
│   │   │   ├── Sidebar.vue
│   │   │   └── Navbar.vue
│   │   ├── Products/
│   │   │   ├── ProductList.vue
│   │   │   ├── ProductForm.vue
│   │   │   ├── ImageGallery.vue
│   │   │   └── ImageUploader.vue
│   │   └── Common/
│   │       ├── LoadingSpinner.vue
│   │       └── ConfirmDialog.vue
│   ├── views/
│   │   ├── Dashboard.vue
│   │   ├── Products/
│   │   │   ├── ProductsIndex.vue
│   │   │   ├── ProductCreate.vue
│   │   │   └── ProductEdit.vue
│   │   └── Login.vue
│   ├── stores/
│   │   ├── auth.js
│   │   └── products.js
│   ├── router/
│   │   └── index.js
│   ├── App.vue
│   └── main.js
├── index.html
└── package.json
```

---

## Complete Code Examples

### 1️⃣ **API Configuration** (`src/api/axios.js`)

```javascript
import axios from 'axios'
import { ElMessage } from 'element-plus'
import router from '@/router'

const API = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost/api',
  timeout: 30000,
  headers: {
    'Accept': 'application/json',
  }
})

// Request Interceptor: Add token to all requests
API.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response Interceptor: Handle errors globally
API.interceptors.response.use(
  (response) => {
    return response
  },
  (error) => {
    if (error.response) {
      switch (error.response.status) {
        case 401:
          // Unauthorized - redirect to login
          localStorage.removeItem('auth_token')
          router.push('/login')
          ElMessage.error('Session expired. Please login again.')
          break
        case 403:
          ElMessage.error('You do not have permission to perform this action.')
          break
        case 404:
          ElMessage.error('Resource not found.')
          break
        case 422:
          // Validation errors - handled by component
          break
        case 500:
          ElMessage.error('Server error. Please try again later.')
          break
        default:
          ElMessage.error('An error occurred. Please try again.')
      }
    } else if (error.request) {
      ElMessage.error('Network error. Please check your connection.')
    }
    return Promise.reject(error)
  }
)

export default API
```

---

### 2️⃣ **Products API** (`src/api/products.js`)

```javascript
import API from './axios'

export const productsAPI = {
  // Get all products with filters
  getProducts(params = {}) {
    return API.get('/admin/products', { params })
  },

  // Get single product
  getProduct(id) {
    return API.get(`/admin/products/${id}`)
  },

  // Create product with images
  createProduct(formData) {
    return API.post('/admin/products', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      },
      onUploadProgress: (progressEvent) => {
        const percentCompleted = Math.round(
          (progressEvent.loaded * 100) / progressEvent.total
        )
        // You can emit this to update a progress bar
        console.log(`Upload Progress: ${percentCompleted}%`)
      }
    })
  },

  // Update product
  updateProduct(id, formData) {
    formData.append('_method', 'PUT')
    return API.post(`/admin/products/${id}?_method=PUT`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
  },

  // Delete product
  deleteProduct(id) {
    return API.delete(`/admin/products/${id}`)
  },

  // Delete specific gallery image
  deleteGalleryImage(productId, mediaId) {
    return API.delete(`/admin/products/${productId}/gallery/${mediaId}`)
  },

  // Reorder gallery images
  reorderGallery(productId, order) {
    return API.post(`/admin/products/${productId}/gallery/reorder`, { order })
  },

  // Update image metadata
  updateImageMetadata(productId, mediaId, data) {
    return API.patch(`/admin/products/${productId}/gallery/${mediaId}`, data)
  }
}
```

---

### 3️⃣ **Main Layout** (`src/components/Layout/AdminLayout.vue`)

```vue
<template>
  <el-container class="admin-layout">
    <!-- Sidebar -->
    <el-aside width="250px" class="sidebar">
      <div class="logo">
        <h1>🛍️ Samer Admin</h1>
      </div>
      
      <el-menu
        :default-active="activeMenu"
        router
        background-color="#001529"
        text-color="#fff"
        active-text-color="#1890ff"
      >
        <el-menu-item index="/dashboard">
          <el-icon><House /></el-icon>
          <span>Dashboard</span>
        </el-menu-item>
        
        <el-menu-item index="/products">
          <el-icon><Box /></el-icon>
          <span>Products</span>
        </el-menu-item>
        
        <el-menu-item index="/categories">
          <el-icon><Menu /></el-icon>
          <span>Categories</span>
        </el-menu-item>
        
        <el-menu-item index="/orders">
          <el-icon><ShoppingCart /></el-icon>
          <span>Orders</span>
        </el-menu-item>
        
        <el-menu-item index="/users">
          <el-icon><User /></el-icon>
          <span>Users</span>
        </el-menu-item>
        
        <el-menu-item index="/settings">
          <el-icon><Setting /></el-icon>
          <span>Settings</span>
        </el-menu-item>
      </el-menu>
    </el-aside>

    <!-- Main Content -->
    <el-container>
      <!-- Top Navbar -->
      <el-header class="navbar">
        <div class="navbar-left">
          <el-breadcrumb separator="/">
            <el-breadcrumb-item :to="{ path: '/dashboard' }">Home</el-breadcrumb-item>
            <el-breadcrumb-item v-for="crumb in breadcrumbs" :key="crumb.path">
              {{ crumb.name }}
            </el-breadcrumb-item>
          </el-breadcrumb>
        </div>
        
        <div class="navbar-right">
          <el-dropdown trigger="click">
            <div class="user-info">
              <el-avatar :size="32" src="https://via.placeholder.com/150" />
              <span>{{ user?.name || 'Admin' }}</span>
            </div>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item @click="router.push('/profile')">
                  <el-icon><User /></el-icon>
                  Profile
                </el-dropdown-item>
                <el-dropdown-item divided @click="handleLogout">
                  <el-icon><SwitchButton /></el-icon>
                  Logout
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </div>
      </el-header>

      <!-- Main Content Area -->
      <el-main class="main-content">
        <router-view v-slot="{ Component }">
          <transition name="fade" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </el-main>
    </el-container>
  </el-container>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { 
  House, 
  Box, 
  Menu, 
  ShoppingCart, 
  User, 
  Setting,
  SwitchButton
} from '@element-plus/icons-vue'
import { ElMessageBox, ElMessage } from 'element-plus'

const route = useRoute()
const router = useRouter()

const user = ref({
  name: 'Admin User',
  email: 'admin@samer.com'
})

const activeMenu = computed(() => route.path)

const breadcrumbs = computed(() => {
  const paths = route.path.split('/').filter(p => p)
  return paths.slice(1).map((path, index) => ({
    name: path.charAt(0).toUpperCase() + path.slice(1),
    path: '/' + paths.slice(0, index + 2).join('/')
  }))
})

const handleLogout = async () => {
  try {
    await ElMessageBox.confirm('Are you sure you want to logout?', 'Logout', {
      confirmButtonText: 'Yes',
      cancelButtonText: 'Cancel',
      type: 'warning'
    })
    
    localStorage.removeItem('auth_token')
    ElMessage.success('Logged out successfully')
    router.push('/login')
  } catch (error) {
    // User cancelled
  }
}
</script>

<style scoped>
.admin-layout {
  height: 100vh;
}

.sidebar {
  background-color: #001529;
  overflow-y: auto;
}

.logo {
  height: 64px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.05);
  margin-bottom: 20px;
}

.logo h1 {
  color: white;
  font-size: 20px;
  margin: 0;
}

.navbar {
  background: white;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 24px;
  box-shadow: 0 1px 4px rgba(0,21,41,.08);
}

.user-info {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
}

.main-content {
  background: #f0f2f5;
  padding: 24px;
  overflow-y: auto;
}

/* Fade transition */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
```

---

### 4️⃣ **Dashboard View** (`src/views/Dashboard.vue`)

```vue
<template>
  <div class="dashboard">
    <h1>Dashboard</h1>
    
    <!-- Stats Cards -->
    <el-row :gutter="20" class="stats-row">
      <el-col :xs="24" :sm="12" :lg="6">
        <el-card class="stat-card">
          <div class="stat-icon products">
            <el-icon><Box /></el-icon>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ stats.products }}</div>
            <div class="stat-label">Total Products</div>
          </div>
        </el-card>
      </el-col>
      
      <el-col :xs="24" :sm="12" :lg="6">
        <el-card class="stat-card">
          <div class="stat-icon orders">
            <el-icon><ShoppingCart /></el-icon>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ stats.orders }}</div>
            <div class="stat-label">Total Orders</div>
          </div>
        </el-card>
      </el-col>
      
      <el-col :xs="24" :sm="12" :lg="6">
        <el-card class="stat-card">
          <div class="stat-icon users">
            <el-icon><User /></el-icon>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ stats.users }}</div>
            <div class="stat-label">Total Users</div>
          </div>
        </el-card>
      </el-col>
      
      <el-col :xs="24" :sm="12" :lg="6">
        <el-card class="stat-card">
          <div class="stat-icon revenue">
            <el-icon><Money /></el-icon>
          </div>
          <div class="stat-content">
            <div class="stat-value">${{ stats.revenue }}</div>
            <div class="stat-label">Total Revenue</div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- Quick Actions -->
    <el-card class="section-card" style="margin-top: 24px">
      <template #header>
        <div class="card-header">
          <span>Quick Actions</span>
        </div>
      </template>
      
      <div class="quick-actions">
        <el-button 
          type="primary" 
          size="large"
          @click="router.push('/products/create')"
        >
          <el-icon><Plus /></el-icon>
          Add New Product
        </el-button>
        
        <el-button 
          type="success" 
          size="large"
          @click="router.push('/orders')"
        >
          <el-icon><View /></el-icon>
          View Orders
        </el-button>
        
        <el-button 
          type="warning" 
          size="large"
          @click="router.push('/categories')"
        >
          <el-icon><Menu /></el-icon>
          Manage Categories
        </el-button>
      </div>
    </el-card>

    <!-- Recent Products -->
    <el-card class="section-card" style="margin-top: 24px">
      <template #header>
        <div class="card-header">
          <span>Recent Products</span>
          <el-button 
            type="primary" 
            link
            @click="router.push('/products')"
          >
            View All →
          </el-button>
        </div>
      </template>
      
      <el-table :data="recentProducts" style="width: 100%">
        <el-table-column label="Image" width="80">
          <template #default="{ row }">
            <el-image
              v-if="row.featured_image"
              :src="row.featured_image.thumb"
              fit="cover"
              style="width: 50px; height: 50px; border-radius: 4px"
            />
          </template>
        </el-table-column>
        
        <el-table-column prop="name" label="Product Name" />
        
        <el-table-column prop="brand" label="Brand" width="120" />
        
        <el-table-column label="Price" width="120">
          <template #default="{ row }">
            ${{ row.base_price }}
          </template>
        </el-table-column>
        
        <el-table-column label="Status" width="100">
          <template #default="{ row }">
            <el-tag :type="row.is_active ? 'success' : 'danger'">
              {{ row.is_active ? 'Active' : 'Inactive' }}
            </el-tag>
          </template>
        </el-table-column>
        
        <el-table-column label="Actions" width="150" fixed="right">
          <template #default="{ row }">
            <el-button 
              type="primary" 
              size="small"
              link
              @click="router.push(`/products/${row.id}/edit`)"
            >
              Edit
            </el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { 
  Box, 
  ShoppingCart, 
  User, 
  Money, 
  Plus,
  View,
  Menu
} from '@element-plus/icons-vue'
import { productsAPI } from '@/api/products'

const router = useRouter()

const stats = ref({
  products: 0,
  orders: 0,
  users: 0,
  revenue: 0
})

const recentProducts = ref([])

onMounted(async () => {
  // Load dashboard stats
  stats.value = {
    products: 248,
    orders: 1429,
    users: 3842,
    revenue: '45,290'
  }
  
  // Load recent products
  try {
    const response = await productsAPI.getProducts({ per_page: 5 })
    recentProducts.value = response.data.data
  } catch (error) {
    console.error('Failed to load recent products:', error)
  }
})
</script>

<style scoped>
.dashboard {
  max-width: 1400px;
}

.dashboard h1 {
  margin: 0 0 24px 0;
  font-size: 28px;
  font-weight: 600;
}

.stats-row {
  margin-bottom: 24px;
}

.stat-card {
  display: flex;
  align-items: center;
  padding: 24px;
}

.stat-card :deep(.el-card__body) {
  display: flex;
  align-items: center;
  width: 100%;
  padding: 0;
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 16px;
}

.stat-icon :deep(.el-icon) {
  font-size: 28px;
  color: white;
}

.stat-icon.products {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-icon.orders {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.stat-icon.users {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.stat-icon.revenue {
  background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: 28px;
  font-weight: 600;
  color: #303133;
  margin-bottom: 4px;
}

.stat-label {
  font-size: 14px;
  color: #909399;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: 600;
}

.quick-actions {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
}
</style>
```

---

### 5️⃣ **Products List** (`src/views/Products/ProductsIndex.vue`)

```vue
<template>
  <div class="products-page">
    <div class="page-header">
      <h1>Products Management</h1>
      <el-button 
        type="primary" 
        size="large"
        @click="router.push('/products/create')"
      >
        <el-icon><Plus /></el-icon>
        Add Product
      </el-button>
    </div>

    <!-- Filters -->
    <el-card class="filters-card">
      <el-form :inline="true" :model="filters">
        <el-form-item label="Search">
          <el-input
            v-model="filters.search"
            placeholder="Product name..."
            clearable
            @clear="loadProducts"
            @keyup.enter="loadProducts"
          >
            <template #prefix>
              <el-icon><Search /></el-icon>
            </template>
          </el-input>
        </el-form-item>
        
        <el-form-item label="Category">
          <el-select
            v-model="filters.category_id"
            placeholder="All Categories"
            clearable
            @change="loadProducts"
          >
            <el-option
              v-for="cat in categories"
              :key="cat.id"
              :label="cat.name"
              :value="cat.id"
            />
          </el-select>
        </el-form-item>
        
        <el-form-item label="Status">
          <el-select
            v-model="filters.is_active"
            placeholder="All"
            clearable
            @change="loadProducts"
          >
            <el-option label="Active" :value="1" />
            <el-option label="Inactive" :value="0" />
          </el-select>
        </el-form-item>
        
        <el-form-item>
          <el-button type="primary" @click="loadProducts">
            <el-icon><Search /></el-icon>
            Search
          </el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- Products Table -->
    <el-card class="table-card" style="margin-top: 16px">
      <el-table
        v-loading="loading"
        :data="products"
        style="width: 100%"
      >
        <el-table-column label="Image" width="100">
          <template #default="{ row }">
            <el-image
              v-if="row.featured_image"
              :src="row.featured_image.thumb"
              :preview-src-list="[row.featured_image.medium]"
              fit="cover"
              style="width: 60px; height: 60px; border-radius: 4px; cursor: pointer"
            />
            <div v-else class="no-image">No Image</div>
          </template>
        </el-table-column>
        
        <el-table-column prop="name" label="Product Name" min-width="200" />
        
        <el-table-column prop="brand" label="Brand" width="120" />
        
        <el-table-column label="Category" width="150">
          <template #default="{ row }">
            {{ row.category?.name || '-' }}
          </template>
        </el-table-column>
        
        <el-table-column label="Price" width="120">
          <template #default="{ row }">
            <span v-if="row.price_range">
              ${{ row.price_range.formatted }}
            </span>
            <span v-else>
              ${{ row.base_price }}
            </span>
          </template>
        </el-table-column>
        
        <el-table-column label="Gallery" width="100">
          <template #default="{ row }">
            📷 {{ row.gallery_count || 0 }}
          </template>
        </el-table-column>
        
        <el-table-column label="Status" width="100">
          <template #default="{ row }">
            <el-switch
              v-model="row.is_active"
              @change="toggleStatus(row)"
            />
          </template>
        </el-table-column>
        
        <el-table-column label="Actions" width="200" fixed="right">
          <template #default="{ row }">
            <el-button
              type="primary"
              size="small"
              link
              @click="router.push(`/products/${row.id}/edit`)"
            >
              <el-icon><Edit /></el-icon>
              Edit
            </el-button>
            
            <el-button
              type="danger"
              size="small"
              link
              @click="handleDelete(row)"
            >
              <el-icon><Delete /></el-icon>
              Delete
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- Pagination -->
      <div style="margin-top: 16px; display: flex; justify-content: flex-end">
        <el-pagination
          v-model:current-page="pagination.current_page"
          v-model:page-size="pagination.per_page"
          :page-sizes="[10, 15, 20, 50]"
          :total="pagination.total"
          layout="total, sizes, prev, pager, next"
          @size-change="loadProducts"
          @current-change="loadProducts"
        />
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus, Search, Edit, Delete } from '@element-plus/icons-vue'
import { productsAPI } from '@/api/products'

const router = useRouter()

const loading = ref(false)
const products = ref([])
const categories = ref([
  { id: 1, name: 'Electronics' },
  { id: 2, name: 'Clothing' },
  { id: 3, name: 'Shoes' },
  { id: 4, name: 'Accessories' }
])

const filters = ref({
  search: '',
  category_id: null,
  is_active: null
})

const pagination = ref({
  current_page: 1,
  per_page: 15,
  total: 0
})

const loadProducts = async () => {
  loading.value = true
  try {
    const params = {
      page: pagination.value.current_page,
      per_page: pagination.value.per_page,
      ...filters.value
    }
    
    const response = await productsAPI.getProducts(params)
    products.value = response.data.data
    pagination.value.total = response.data.meta.total
  } catch (error) {
    ElMessage.error('Failed to load products')
  } finally {
    loading.value = false
  }
}

const toggleStatus = async (product) => {
  try {
    const formData = new FormData()
    formData.append('is_active', product.is_active ? 1 : 0)
    
    await productsAPI.updateProduct(product.id, formData)
    ElMessage.success('Product status updated')
  } catch (error) {
    product.is_active = !product.is_active
    ElMessage.error('Failed to update status')
  }
}

const handleDelete = async (product) => {
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to delete "${product.name}"?`,
      'Delete Product',
      {
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        type: 'warning'
      }
    )
    
    await productsAPI.deleteProduct(product.id)
    ElMessage.success('Product deleted successfully')
    loadProducts()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('Failed to delete product')
    }
  }
}

onMounted(() => {
  loadProducts()
})
</script>

<style scoped>
.products-page {
  max-width: 1400px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.page-header h1 {
  margin: 0;
  font-size: 28px;
  font-weight: 600;
}

.no-image {
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f5f5f5;
  border-radius: 4px;
  font-size: 12px;
  color: #999;
}
</style>
```

---

### 6️⃣ **Product Form with Image Upload** (`src/views/Products/ProductCreate.vue`)

```vue
<template>
  <div class="product-form-page">
    <div class="page-header">
      <h1>{{ isEdit ? 'Edit Product' : 'Create Product' }}</h1>
      <el-button @click="router.back()">
        <el-icon><Back /></el-icon>
        Back
      </el-button>
    </div>

    <el-form
      ref="formRef"
      :model="form"
      :rules="rules"
      label-width="140px"
      label-position="left"
    >
      <!-- Basic Information -->
      <el-card class="form-card">
        <template #header>
          <span class="card-title">Basic Information</span>
        </template>
        
        <el-form-item label="Product Name" prop="name">
          <el-input
            v-model="form.name"
            placeholder="Enter product name"
            maxlength="255"
            show-word-limit
          />
        </el-form-item>
        
        <el-form-item label="Description" prop="description">
          <el-input
            v-model="form.description"
            type="textarea"
            :rows="4"
            placeholder="Enter product description"
          />
        </el-form-item>
        
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="Category" prop="category_id">
              <el-select
                v-model="form.category_id"
                placeholder="Select category"
                style="width: 100%"
              >
                <el-option
                  v-for="cat in categories"
                  :key="cat.id"
                  :label="cat.name"
                  :value="cat.id"
                />
              </el-select>
            </el-form-item>
          </el-col>
          
          <el-col :span="12">
            <el-form-item label="Brand" prop="brand">
              <el-input
                v-model="form.brand"
                placeholder="Enter brand name"
              />
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="Base Price" prop="base_price">
              <el-input-number
                v-model="form.base_price"
                :min="0"
                :precision="2"
                :controls="false"
                style="width: 100%"
              />
            </el-form-item>
          </el-col>
          
          <el-col :span="12">
            <el-form-item label="Status" prop="is_active">
              <el-switch
                v-model="form.is_active"
                active-text="Active"
                inactive-text="Inactive"
              />
            </el-form-item>
          </el-col>
        </el-row>
      </el-card>

      <!-- Product Gallery -->
      <el-card class="form-card" style="margin-top: 16px">
        <template #header>
          <span class="card-title">Product Gallery (Max 10 images)</span>
        </template>
        
        <el-form-item>
          <!-- Upload Button -->
          <el-upload
            ref="uploadRef"
            :auto-upload="false"
            :on-change="handleImageSelect"
            :on-remove="handleImageRemove"
            :file-list="[]"
            :show-file-list="false"
            accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
            multiple
            :limit="10"
            :disabled="form.gallery.length >= 10"
          >
            <el-button 
              type="primary"
              :disabled="form.gallery.length >= 10"
            >
              <el-icon><Plus /></el-icon>
              Upload Images ({{ form.gallery.length }}/10)
            </el-button>
          </el-upload>
          
          <div class="upload-hint">
            Max file size: 5MB per image • Supported formats: JPG, PNG, GIF, WebP • Min dimensions: 100x100px
          </div>
        </el-form-item>

        <!-- Image Preview Grid -->
        <div v-if="form.gallery.length" class="image-grid">
          <div
            v-for="(item, index) in form.gallery"
            :key="index"
            class="image-card"
          >
            <div class="image-wrapper">
              <img :src="item.preview" :alt="`Image ${index + 1}`" />
              <div class="image-overlay">
                <el-button
                  type="danger"
                  size="small"
                  circle
                  @click="removeGalleryImage(index)"
                >
                  <el-icon><Delete /></el-icon>
                </el-button>
              </div>
            </div>
            
            <el-input
              v-model="item.alt_text"
              size="small"
              placeholder="Alt text for SEO"
              style="margin-top: 8px"
            />
            
            <el-input
              v-model="item.caption"
              size="small"
              placeholder="Caption (optional)"
              style="margin-top: 4px"
            />
          </div>
        </div>
      </el-card>

      <!-- Submit Buttons -->
      <div class="form-actions">
        <el-button @click="router.back()">Cancel</el-button>
        <el-button
          type="primary"
          :loading="submitting"
          @click="handleSubmit"
        >
          {{ isEdit ? 'Update Product' : 'Create Product' }}
        </el-button>
      </div>
    </el-form>

    <!-- Upload Progress Dialog -->
    <el-dialog
      v-model="showProgress"
      title="Uploading..."
      width="400px"
      :close-on-click-modal="false"
      :close-on-press-escape="false"
      :show-close="false"
    >
      <el-progress :percentage="uploadProgress" />
      <p style="text-align: center; margin-top: 16px">
        Please wait while we upload your images...
      </p>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { Back, Plus, Delete } from '@element-plus/icons-vue'
import { productsAPI } from '@/api/products'

const router = useRouter()
const route = useRoute()
const isEdit = ref(!!route.params.id)

const formRef = ref(null)
const uploadRef = ref(null)
const submitting = ref(false)
const showProgress = ref(false)
const uploadProgress = ref(0)

const categories = ref([
  { id: 1, name: 'Electronics' },
  { id: 2, name: 'Clothing' },
  { id: 3, name: 'Shoes' },
  { id: 4, name: 'Accessories' }
])

const form = reactive({
  name: '',
  description: '',
  category_id: null,
  brand: '',
  base_price: 0,
  is_active: true,
  gallery: [] // Array of { file: File, preview: string, alt_text: string, caption: string }
})

const rules = {
  name: [
    { required: true, message: 'Please enter product name', trigger: 'blur' }
  ],
  description: [
    { required: true, message: 'Please enter description', trigger: 'blur' }
  ],
  category_id: [
    { required: true, message: 'Please select category', trigger: 'change' }
  ],
  base_price: [
    { required: true, message: 'Please enter base price', trigger: 'blur' },
    { type: 'number', min: 0, message: 'Price must be at least 0', trigger: 'blur' }
  ]
}

const handleImageSelect = (file) => {
  // Validate file size (5MB)
  if (file.size > 5 * 1024 * 1024) {
    ElMessage.error(`${file.name} is too large. Max size is 5MB.`)
    return
  }
  
  // Validate dimensions
  validateImageDimensions(file.raw).then(isValid => {
    if (isValid) {
      form.gallery.push({
        file: file.raw,
        preview: URL.createObjectURL(file.raw),
        alt_text: form.name || '',
        caption: ''
      })
    }
  })
}

const validateImageDimensions = (file) => {
  return new Promise((resolve) => {
    const img = new Image()
    img.onload = () => {
      const isValid = 
        img.width >= 100 && 
        img.height >= 100 && 
        img.width <= 4000 && 
        img.height <= 4000
      
      if (!isValid) {
        ElMessage.error(
          `${file.name} has invalid dimensions. Must be 100-4000px.`
        )
      }
      resolve(isValid)
    }
    img.onerror = () => resolve(false)
    img.src = URL.createObjectURL(file)
  })
}

const handleImageRemove = (file) => {
  const index = form.gallery.findIndex(item => item.file === file.raw)
  if (index > -1) {
    form.gallery.splice(index, 1)
  }
}

const removeGalleryImage = (index) => {
  URL.revokeObjectURL(form.gallery[index].preview)
  form.gallery.splice(index, 1)
}

const handleSubmit = async () => {
  if (!formRef.value) return
  
  await formRef.value.validate(async (valid) => {
    if (!valid) {
      ElMessage.error('Please fill in all required fields')
      return
    }
    
    submitting.value = true
    showProgress.value = true
    uploadProgress.value = 0
    
    try {
      // Create FormData
      const formData = new FormData()
      formData.append('name', form.name)
      formData.append('description', form.description)
      formData.append('category_id', form.category_id)
      formData.append('brand', form.brand)
      formData.append('base_price', form.base_price)
      formData.append('is_active', form.is_active ? 1 : 0)
      
      // Append gallery images
      form.gallery.forEach((item, index) => {
        formData.append('gallery[]', item.file)
        formData.append(`gallery_alt[${index}]`, item.alt_text)
        formData.append(`gallery_caption[${index}]`, item.caption)
      })
      
      // Upload with progress
      const config = {
        onUploadProgress: (progressEvent) => {
          uploadProgress.value = Math.round(
            (progressEvent.loaded * 100) / progressEvent.total
          )
        }
      }
      
      let response
      if (isEdit.value) {
        response = await productsAPI.updateProduct(route.params.id, formData, config)
      } else {
        response = await productsAPI.createProduct(formData, config)
      }
      
      ElMessage.success(
        isEdit.value ? 'Product updated successfully!' : 'Product created successfully!'
      )
      
      router.push('/products')
    } catch (error) {
      console.error('Submit error:', error)
      
      if (error.response?.status === 422) {
        const errors = error.response.data.errors
        const firstError = Object.values(errors)[0][0]
        ElMessage.error(firstError)
      } else {
        ElMessage.error('Failed to save product. Please try again.')
      }
    } finally {
      submitting.value = false
      showProgress.value = false
      uploadProgress.value = 0
    }
  })
}

onMounted(async () => {
  if (isEdit.value) {
    // Load product data for editing
    try {
      const response = await productsAPI.getProduct(route.params.id)
      const product = response.data.data
      
      form.name = product.name
      form.description = product.description
      form.category_id = product.category?.id
      form.brand = product.brand
      form.base_price = product.base_price
      form.is_active = product.is_active
      
      // Note: Existing images are managed separately in gallery manager
    } catch (error) {
      ElMessage.error('Failed to load product')
      router.back()
    }
  }
})
</script>

<style scoped>
.product-form-page {
  max-width: 1000px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.page-header h1 {
  margin: 0;
  font-size: 28px;
  font-weight: 600;
}

.card-title {
  font-weight: 600;
  font-size: 16px;
}

.form-card {
  margin-bottom: 16px;
}

.upload-hint {
  margin-top: 8px;
  font-size: 12px;
  color: #909399;
}

.image-grid {
 display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 16px;
  margin-top: 16px;
}

.image-card {
  border: 1px solid #dcdfe6;
  border-radius: 4px;
  padding: 12px;
  transition: all 0.3s;
}

.image-card:hover {
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
}

.image-wrapper {
  position: relative;
  width: 100%;
  height: 160px;
  border-radius: 4px;
  overflow: hidden;
}

.image-wrapper img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.image-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.3s;
}

.image-wrapper:hover .image-overlay {
  opacity: 1;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 24px;
  padding-top: 24px;
  border-top: 1px solid #dcdfe6;
}
</style>
```

---

### 7️⃣ **Image Gallery Manager** (`src/components/Products/ImageGallery.vue`)

```vue
<template>
  <div class="gallery-manager">
    <div class="gallery-header">
      <h3>Product Gallery ({{ images.length }} images)</h3>
      <el-button
        type="primary"
        size="small"
        @click="emit('add-images')"
        :disabled="images.length >= 10"
      >
        <el-icon><Plus /></el-icon>
        Add More Images
      </el-button>
    </div>

    <el-empty v-if="!images.length" description="No images uploaded yet" />

    <draggable
      v-else
      v-model="localImages"
      @end="handleReorder"
      item-key="id"
      class="gallery-grid"
      :animation="200"
    >
      <template #item="{ element, index }">
        <div class="gallery-item">
          <div class="item-badge">{{ index + 1 }}</div>
          
          <div class="item-image">
            <img
              :src="element.conversions?.find(c => c.name === 'thumb')?.url || element.original_url"
              :alt="element.custom_properties?.alt_text"
            />
            
            <div class="item-overlay">
              <el-button
                type="primary"
                size="small"
                circle
                @click="handleEdit(element)"
              >
                <el-icon><Edit /></el-icon>
              </el-button>
              
              <el-button
                type="danger"
                size="small"
                circle
                @click="handleDelete(element)"
              >
                <el-icon><Delete /></el-icon>
              </el-button>
            </div>
          </div>
          
          <div class="item-info">
            <div class="info-text">
              {{ element.custom_properties?.alt_text || 'No alt text' }}
            </div>
            <div class="info-meta">
              {{ formatFileSize(element.size) }} • {{ element.mime_type }}
            </div>
          </div>
          
          <div class="drag-handle">
            <el-icon><Rank /></el-icon>
          </div>
        </div>
      </template>
    </draggable>

    <!-- Edit Metadata Dialog -->
    <el-dialog
      v-model="showEditDialog"
      title="Edit Image Details"
      width="500px"
    >
      <el-form v-if="editingImage" label-position="top">
        <el-form-item label="Alt Text (for SEO)">
          <el-input
            v-model="editForm.alt_text"
            placeholder="Describe the image"
            maxlength="255"
            show-word-limit
          />
        </el-form-item>
        
        <el-form-item label="Caption (optional)">
          <el-input
            v-model="editForm.caption"
            type="textarea"
            :rows="3"
            placeholder="Additional description"
            maxlength="500"
            show-word-limit
          />
        </el-form-item>

        <div class="preview-image">
          <img
            :src="editingImage.conversions?.find(c => c.name === 'medium')?.url"
            alt="Preview"
          />
        </div>
      </el-form>
      
      <template #footer>
        <el-button @click="showEditDialog = false">Cancel</el-button>
        <el-button type="primary" @click="saveMetadata">Save</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus, Edit, Delete, Rank } from '@element-plus/icons-vue'
import draggable from 'vuedraggable'
import { productsAPI } from '@/api/products'

const props = defineProps({
  productId: {
    type: Number,
    required: true
  },
  images: {
    type: Array,
    required: true
  }
})

const emit = defineEmits(['update:images', 'add-images'])

const localImages = computed({
  get: () => props.images,
  set: (value) => emit('update:images', value)
})

const showEditDialog = ref(false)
const editingImage = ref(null)
const editForm = ref({
  alt_text: '',
  caption: ''
})

const formatFileSize = (bytes) => {
  if (!bytes) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}

const handleReorder = async () => {
  try {
    const order = localImages.value.map(img => img.id)
    await productsAPI.reorderGallery(props.productId, order)
    ElMessage.success('Images reordered successfully')
  } catch (error) {
    ElMessage.error('Failed to reorder images')
    // Revert on error (parent component should handle this)
  }
}

const handleEdit = (image) => {
  editingImage.value = image
  editForm.value = {
    alt_text: image.custom_properties?.alt_text || '',
    caption: image.custom_properties?.caption || ''
  }
  showEditDialog.value = true
}

const saveMetadata = async () => {
  try {
    await productsAPI.updateImageMetadata(
      props.productId,
      editingImage.value.id,
      editForm.value
    )
    
    // Update local state
    const index = localImages.value.findIndex(img => img.id === editingImage.value.id)
    if (index > -1) {
      localImages.value[index].custom_properties = {
        ...localImages.value[index].custom_properties,
        ...editForm.value
      }
    }
    
    ElMessage.success('Image details updated')
    showEditDialog.value = false
  } catch (error) {
    ElMessage.error('Failed to update image details')
  }
}

const handleDelete = async (image) => {
  try {
    await ElMessageBox.confirm(
      'Are you sure you want to delete this image?',
      'Delete Image',
      {
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        type: 'warning'
      }
    )
    
    await productsAPI.deleteGalleryImage(props.productId, image.id)
    
    // Remove from local state
    const newImages = localImages.value.filter(img => img.id !== image.id)
    emit('update:images', newImages)
    
    ElMessage.success('Image deleted successfully')
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('Failed to delete image')
    }
  }
}
</script>

<style scoped>
.gallery-manager {
  padding: 20px;
}

.gallery-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.gallery-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
}

.gallery-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 16px;
}

.gallery-item {
  position: relative;
  border: 2px solid #dcdfe6;
  border-radius: 8px;
  padding: 12px;
  background: white;
  cursor: move;
  transition: all 0.3s;
}

.gallery-item:hover {
  border-color: #409eff;
  box-shadow: 0 2px 12px rgba(64, 158, 255, 0.2);
  transform: translateY(-2px);
}

.item-badge {
  position: absolute;
  top: 8px;
  right: 8px;
  background: rgba(0, 0, 0, 0.7);
  color: white;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: bold;
  z-index: 2;
}

.item-image {
  position: relative;
  width: 100%;
  height: 180px;
  border-radius: 6px;
  overflow: hidden;
  margin-bottom: 12px;
}

.item-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.item-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  opacity: 0;
  transition: opacity 0.3s;
}

.item-image:hover .item-overlay {
  opacity: 1;
}

.item-info {
  margin-bottom: 8px;
}

.info-text {
  font-size: 13px;
  color: #303133;
  margin-bottom: 4px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.info-meta {
  font-size: 11px;
  color: #909399;
}

.drag-handle {
  text-align: center;
  color: #909399;
  padding: 4px 0;
  border-top: 1px solid #dcdfe6;
  margin-top: 8px;
  cursor: move;
}

.drag-handle:hover {
  color: #409eff;
}

.preview-image {
  margin-top: 16px;
  text-align: center;
}

.preview-image img {
  max-width: 100%;
  max-height: 300px;
  border-radius: 4px;
}
</style>
```

---

### 8️⃣ **Main Configuration Files**

#### **main.js**

```javascript
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'
import * as ElementPlusIconsVue from '@element-plus/icons-vue'
import App from './App.vue'
import router from './router'
import './style.css'

const app = createApp(App)
const pinia = createPinia()

// Register all icons
for (const [key, component] of Object.entries(ElementPlusIconsVue)) {
  app.component(key, component)
}

app.use(pinia)
app.use(router)
app.use(ElementPlus)

app.mount('#app')
```

#### **router/index.js**

```javascript
import { createRouter, createWebHistory } from 'vue-router'
import AdminLayout from '@/components/Layout/AdminLayout.vue'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/Login.vue')
  },
  {
    path: '/',
    component: AdminLayout,
    meta: { requiresAuth: true },
    redirect: '/dashboard',
    children: [
      {
        path: 'dashboard',
        name: 'Dashboard',
        component: () => import('@/views/Dashboard.vue')
      },
      {
        path: 'products',
        name: 'ProductsIndex',
        component: () => import('@/views/Products/ProductsIndex.vue')
      },
      {
        path: 'products/create',
        name: 'ProductCreate',
        component: () => import('@/views/Products/ProductCreate.vue')
      },
      {
        path: 'products/:id/edit',
        name: 'ProductEdit',
        component: () => import('@/views/Products/ProductEdit.vue')
      }
    ]
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

// Navigation guard
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('auth_token')
  
  if (to.meta.requiresAuth && !token) {
    next('/login')
  } else if (to.path === '/login' && token) {
    next('/dashboard')
  } else {
    next()
  }
})

export default router
```

#### **.env**

```env
VITE_API_URL=http://localhost/api
VITE_APP_NAME=Samer Admin
```

#### **package.json**

```json
{
  "name": "samer-admin",
  "version": "1.0.0",
  "type": "module",
  "scripts": {
    "dev": "vite",
    "build": "vite build",
    "preview": "vite preview"
  },
  "dependencies": {
    "vue": "^3.4.0",
    "vue-router": "^4.2.5",
    "pinia": "^2.1.7",
    "axios": "^1.6.5",
    "element-plus": "^2.5.0",
    "@element-plus/icons-vue": "^2.3.1",
    "vuedraggable": "^4.1.0"
  },
  "devDependencies": {
    "@vitejs/plugin-vue": "^5.0.3",
    "vite": "^5.0.11"
  }
}
```

---

## Installation Guide

### Step 1: Create Project

```bash
npm create vite@latest samer-admin -- --template vue
cd samer-admin
```

### Step 2: Install Dependencies

```bash
npm install axios pinia vue-router vuedraggable@next element-plus @element-plus/icons-vue
```

### Step 3: Copy Files

Copy all the component files from this guide into your project following the file structure above.

### Step 4: Configure

Create `.env` file:

```env
VITE_API_URL=http://localhost/api
```

### Step 5: Run Development Server

```bash
npm run dev
```

Open http://localhost:5173

---

## 🎯 Key Features

✅ **Complete Admin Dashboard** with sidebar navigation  
✅ **Product Management** - Create, edit, delete products  
✅ **Image Upload** - Multiple images with preview  
✅ **Drag & Drop Reordering** - vuedraggable integration  
✅ **Image Metadata** - Edit alt text and captions  
✅ **Real-time Upload Progress** - Progress bar during upload  
✅ **Responsive Design** - Works on all screen sizes  
✅ **Error Handling** - User-friendly error messages  
✅ **Authentication** - Login/logout with token management  

---

## 📸 Screenshots

Your dashboard will include:
- **Sidebar Navigation** with icons
- **Stats Cards** showing products, orders, users, revenue
- **Product List** with search, filters, pagination
- **Product Form** with image upload and preview
- **Image Gallery Manager** with drag-and-drop reordering

---

## 🚀 Next Steps

1. Customize the UI to match your brand
2. Add more filters to the product list
3. Implement categories management
4. Add orders management
5. Add user roles and permissions UI
6. Implement real-time notifications

---

**Enjoy building your admin dashboard! 🎉**
