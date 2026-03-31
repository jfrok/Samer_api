# Category Icon Selector - Frontend Implementation Guide

## Overview
This document provides instructions for implementing a category icon selection feature in the frontend dashboard. Users can select predefined icons from a modal window when creating or editing categories.

---

## Backend API Endpoints

### 1. Get Available Icons
**Endpoint:** `GET /api/admin/categories/icons/available`  
**Authentication:** Required (admin only)

**Response:**
```json
{
  "success": true,
  "data": {
    "grouped": {
      "fashion": [
        {
          "key": "tshirt",
          "name": "T-Shirt",
          "class": "fa-solid fa-shirt"
        },
        {
          "key": "dress",
          "name": "Dress",
          "class": "fa-solid fa-user-tie"
        }
      ],
      "electronics": [
        {
          "key": "laptop",
          "name": "Laptop",
          "class": "fa-solid fa-laptop"
        }
      ],
      "home": [...],
      "sports": [...],
      "food": [...],
      "books": [...],
      "beauty": [...],
      "kids": [...],
      "automotive": [...],
      "pets": [...],
      "office": [...],
      "general": [...]
    },
    "flat": [
      {
        "key": "tshirt",
        "name": "T-Shirt",
        "class": "fa-solid fa-shirt",
        "category": "fashion"
      },
      ...
    ],
    "total": 60
  }
}
```

### 2. Create Category with Icon
**Endpoint:** `POST /api/admin/categories`  
**Authentication:** Required (admin only)

**Request Body:**
```json
{
  "name": "Electronics",
  "slug": "electronics",
  "description": "Electronic devices and accessories",
  "icon": "laptop",
  "parent_id": null
}
```

**Response:**
```json
{
  "success": true,
  "message": "Category created successfully",
  "data": {
    "id": 1,
    "name": "Electronics",
    "slug": "electronics",
    "description": "Electronic devices and accessories",
    "icon": "laptop",
    "parent_id": null
  }
}
```

### 3. Update Category Icon
**Endpoint:** `PUT /api/admin/categories/{id}`  
**Authentication:** Required (admin only)

**Request Body:**
```json
{
  "name": "Electronics",
  "icon": "mobile"
}
```

### 4. Get Categories with Icons
**Endpoint:** `GET /api/categories`  
**Authentication:** Not required (public)

**Response:**
```json
[
  {
    "id": 1,
    "name": "Electronics",
    "slug": "electronics",
    "description": "Electronic devices",
    "icon": "laptop",
    "parent_id": null,
    "children": [...]
  }
]
```

---

## Frontend Implementation

### Step 1: Install Font Awesome (if not already installed)

```bash
npm install --save @fortawesome/fontawesome-free
# or
yarn add @fortawesome/fontawesome-free
```

In your main CSS/JS entry file:
```javascript
import '@fortawesome/fontawesome-free/css/all.min.css';
```

Or use CDN in your `index.html`:
```html
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
```

---

### Step 2: Create Icon Selector Component

#### React Example:

```jsx
// components/CategoryIconSelector.jsx
import React, { useState, useEffect } from 'react';
import axios from 'axios';
import './CategoryIconSelector.css';

const CategoryIconSelector = ({ selectedIcon, onIconSelect, onClose }) => {
  const [icons, setIcons] = useState({ grouped: {}, flat: [] });
  const [activeTab, setActiveTab] = useState('fashion');
  const [searchTerm, setSearchTerm] = useState('');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchIcons();
  }, []);

  const fetchIcons = async () => {
    try {
      const response = await axios.get('/api/admin/categories/icons/available', {
        headers: {
          Authorization: `Bearer ${localStorage.getItem('token')}`
        }
      });
      
      if (response.data.success) {
        setIcons(response.data.data);
        setLoading(false);
      }
    } catch (error) {
      console.error('Failed to fetch icons:', error);
      setLoading(false);
    }
  };

  const handleIconClick = (iconKey) => {
    onIconSelect(iconKey);
    onClose();
  };

  const filteredIcons = searchTerm
    ? icons.flat.filter(icon =>
        icon.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        icon.key.toLowerCase().includes(searchTerm.toLowerCase())
      )
    : icons.grouped[activeTab] || [];

  if (loading) {
    return <div className="icon-selector-loading">Loading icons...</div>;
  }

  return (
    <div className="icon-selector-overlay" onClick={onClose}>
      <div className="icon-selector-modal" onClick={(e) => e.stopPropagation()}>
        <div className="icon-selector-header">
          <h2>Select Category Icon</h2>
          <button className="close-btn" onClick={onClose}>
            <i className="fa-solid fa-times"></i>
          </button>
        </div>

        <div className="icon-selector-search">
          <input
            type="text"
            placeholder="Search icons..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="search-input"
          />
        </div>

        {!searchTerm && (
          <div className="icon-selector-tabs">
            {Object.keys(icons.grouped).map(category => (
              <button
                key={category}
                className={`tab-btn ${activeTab === category ? 'active' : ''}`}
                onClick={() => setActiveTab(category)}
              >
                {category.charAt(0).toUpperCase() + category.slice(1)}
              </button>
            ))}
          </div>
        )}

        <div className="icon-selector-grid">
          {filteredIcons.map(icon => (
            <div
              key={icon.key}
              className={`icon-item ${selectedIcon === icon.key ? 'selected' : ''}`}
              onClick={() => handleIconClick(icon.key)}
              title={icon.name}
            >
              <i className={icon.class}></i>
              <span className="icon-name">{icon.name}</span>
            </div>
          ))}
        </div>

        {filteredIcons.length === 0 && (
          <div className="no-icons">No icons found</div>
        )}
      </div>
    </div>
  );
};

export default CategoryIconSelector;
```

#### Vue 3 Example:

```vue
<!-- components/CategoryIconSelector.vue -->
<template>
  <div class="icon-selector-overlay" @click="$emit('close')">
    <div class="icon-selector-modal" @click.stop>
      <div class="icon-selector-header">
        <h2>Select Category Icon</h2>
        <button class="close-btn" @click="$emit('close')">
          <i class="fa-solid fa-times"></i>
        </button>
      </div>

      <div class="icon-selector-search">
        <input
          v-model="searchTerm"
          type="text"
          placeholder="Search icons..."
          class="search-input"
        />
      </div>

      <div v-if="!searchTerm" class="icon-selector-tabs">
        <button
          v-for="category in Object.keys(icons.grouped)"
          :key="category"
          :class="['tab-btn', { active: activeTab === category }]"
          @click="activeTab = category"
        >
          {{ category.charAt(0).toUpperCase() + category.slice(1) }}
        </button>
      </div>

      <div class="icon-selector-grid">
        <div
          v-for="icon in filteredIcons"
          :key="icon.key"
          :class="['icon-item', { selected: selectedIcon === icon.key }]"
          @click="handleIconClick(icon.key)"
          :title="icon.name"
        >
          <i :class="icon.class"></i>
          <span class="icon-name">{{ icon.name }}</span>
        </div>
      </div>

      <div v-if="filteredIcons.length === 0" class="no-icons">
        No icons found
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  selectedIcon: String
});

const emit = defineEmits(['iconSelect', 'close']);

const icons = ref({ grouped: {}, flat: [] });
const activeTab = ref('fashion');
const searchTerm = ref('');
const loading = ref(true);

onMounted(async () => {
  await fetchIcons();
});

const fetchIcons = async () => {
  try {
    const response = await axios.get('/api/admin/categories/icons/available', {
      headers: {
        Authorization: `Bearer ${localStorage.getItem('token')}`
      }
    });
    
    if (response.data.success) {
      icons.value = response.data.data;
      loading.value = false;
    }
  } catch (error) {
    console.error('Failed to fetch icons:', error);
    loading.value = false;
  }
};

const filteredIcons = computed(() => {
  if (searchTerm.value) {
    return icons.value.flat.filter(icon =>
      icon.name.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
      icon.key.toLowerCase().includes(searchTerm.value.toLowerCase())
    );
  }
  return icons.value.grouped[activeTab.value] || [];
});

const handleIconClick = (iconKey) => {
  emit('iconSelect', iconKey);
  emit('close');
};
</script>
```

---

### Step 3: CSS Styling

```css
/* CategoryIconSelector.css */
.icon-selector-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  animation: fadeIn 0.2s ease;
}

.icon-selector-modal {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 800px;
  max-height: 90vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
  animation: slideUp 0.3s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideUp {
  from { transform: translateY(50px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

.icon-selector-header {
  padding: 20px;
  border-bottom: 1px solid #e0e0e0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.icon-selector-header h2 {
  margin: 0;
  font-size: 24px;
  color: #333;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #666;
  transition: color 0.2s;
}

.close-btn:hover {
  color: #333;
}

.icon-selector-search {
  padding: 20px;
  border-bottom: 1px solid #e0e0e0;
}

.search-input {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 16px;
  transition: border-color 0.2s;
}

.search-input:focus {
  outline: none;
  border-color: #4CAF50;
}

.icon-selector-tabs {
  display: flex;
  overflow-x: auto;
  padding: 0 20px;
  gap: 10px;
  border-bottom: 1px solid #e0e0e0;
  background: #f9f9f9;
}

.tab-btn {
  padding: 12px 20px;
  border: none;
  background: transparent;
  cursor: pointer;
  font-size: 14px;
  color: #666;
  white-space: nowrap;
  transition: all 0.2s;
  border-bottom: 3px solid transparent;
}

.tab-btn:hover {
  color: #333;
  background: rgba(0, 0, 0, 0.05);
}

.tab-btn.active {
  color: #4CAF50;
  border-bottom-color: #4CAF50;
  font-weight: 600;
}

.icon-selector-grid {
  padding: 20px;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
  gap: 15px;
  overflow-y: auto;
  max-height: 400px;
}

.icon-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 15px;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  background: white;
}

.icon-item:hover {
  border-color: #4CAF50;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(76, 175, 80, 0.2);
}

.icon-item.selected {
  border-color: #4CAF50;
  background: #f0f9f1;
}

.icon-item i {
  font-size: 32px;
  margin-bottom: 8px;
  color: #333;
}

.icon-name {
  font-size: 12px;
  color: #666;
  text-align: center;
  word-break: break-word;
}

.no-icons {
  text-align: center;
  padding: 40px;
  color: #999;
  font-size: 16px;
}

.icon-selector-loading {
  text-align: center;
  padding: 40px;
  font-size: 18px;
  color: #666;
}

/* Responsive Design */
@media (max-width: 768px) {
  .icon-selector-modal {
    width: 95%;
    max-height: 95vh;
  }

  .icon-selector-grid {
    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    gap: 10px;
  }

  .icon-item {
    padding: 10px;
  }

  .icon-item i {
    font-size: 24px;
  }
}
```

---

### Step 4: Usage in Category Form

#### React Example:

```jsx
// pages/CategoryForm.jsx
import React, { useState } from 'react';
import CategoryIconSelector from '../components/CategoryIconSelector';
import axios from 'axios';

const CategoryForm = () => {
  const [formData, setFormData] = useState({
    name: '',
    slug: '',
    description: '',
    icon: '',
    parent_id: null
  });
  const [showIconSelector, setShowIconSelector] = useState(false);

  const handleIconSelect = (iconKey) => {
    setFormData(prev => ({ ...prev, icon: iconKey }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    try {
      const response = await axios.post('/api/admin/categories', formData, {
        headers: {
          Authorization: `Bearer ${localStorage.getItem('token')}`
        }
      });
      
      if (response.data.success) {
        alert('Category created successfully!');
        // Reset form or redirect
      }
    } catch (error) {
      console.error('Failed to create category:', error);
      alert('Failed to create category');
    }
  };

  const getIconClass = (iconKey) => {
    // You can fetch this from the icons list or hardcode common ones
    const iconMap = {
      'tshirt': 'fa-solid fa-shirt',
      'laptop': 'fa-solid fa-laptop',
      'mobile': 'fa-solid fa-mobile-screen',
      // ... add more mappings or fetch from API
    };
    return iconMap[iconKey] || 'fa-solid fa-box';
  };

  return (
    <div className="category-form">
      <h1>Create Category</h1>
      
      <form onSubmit={handleSubmit}>
        <div className="form-group">
          <label>Category Name</label>
          <input
            type="text"
            value={formData.name}
            onChange={(e) => setFormData(prev => ({ ...prev, name: e.target.value }))}
            required
          />
        </div>

        <div className="form-group">
          <label>Description</label>
          <textarea
            value={formData.description}
            onChange={(e) => setFormData(prev => ({ ...prev, description: e.target.value }))}
          />
        </div>

        <div className="form-group">
          <label>Icon</label>
          <div className="icon-selector-trigger">
            <button
              type="button"
              className="select-icon-btn"
              onClick={() => setShowIconSelector(true)}
            >
              {formData.icon ? (
                <>
                  <i className={getIconClass(formData.icon)}></i>
                  <span>{formData.icon}</span>
                </>
              ) : (
                'Select Icon'
              )}
            </button>
            {formData.icon && (
              <button
                type="button"
                className="clear-icon-btn"
                onClick={() => setFormData(prev => ({ ...prev, icon: '' }))}
              >
                Clear
              </button>
            )}
          </div>
        </div>

        <button type="submit" className="submit-btn">
          Create Category
        </button>
      </form>

      {showIconSelector && (
        <CategoryIconSelector
          selectedIcon={formData.icon}
          onIconSelect={handleIconSelect}
          onClose={() => setShowIconSelector(false)}
        />
      )}
    </div>
  );
};

export default CategoryForm;
```

---

## Available Icon Categories

- **fashion** - Clothing, shoes, accessories
- **electronics** - Laptops, phones, cameras
- **home** - Furniture, kitchen, cleaning
- **sports** - Sports equipment, fitness
- **books** - Books, stationery, education
- **beauty** - Cosmetics, health products
- **food** - Food items, beverages
- **kids** - Baby products, toys
- **automotive** - Cars, tools
- **pets** - Pet supplies
- **office** - Office supplies, business
- **general** - Generic icons (gifts, stars, tags)

---

## Testing

### Test the Icon API:
```bash
curl -X GET "http://localhost:8000/api/admin/categories/icons/available" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Test Category Creation with Icon:
```bash
curl -X POST "http://localhost:8000/api/admin/categories" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Electronics",
    "description": "Electronic devices",
    "icon": "laptop"
  }'
```

---

## Notes

1. **Icon Validation**: The API validates that only valid icon keys are accepted
2. **Optional Field**: The icon field is optional - categories can exist without icons
3. **Font Awesome 6**: All icons use Font Awesome 6 (free version)
4. **60+ Icons**: 60+ predefined icons grouped into 12 categories
5. **Cache**: Category list is cached for 1 hour - clear cache after updates using `/api/categories/clear-cache`

---

## Support

For questions or issues, contact the backend team or refer to:
- Config file: `config/category-icons.php`
- Controller: `app/Http/Controllers/API/CategoryController.php`
- Model: `app/Models/Category.php`
