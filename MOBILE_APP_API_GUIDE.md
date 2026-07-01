# Mobile App API Documentation

## 📱 Overview

This API provides endpoints for the Samsmy e-commerce mobile application. The API uses **Laravel Sanctum** for authentication and returns JSON responses.

**Base URL:** `https://api.samsmy.cutscal.com/api`

---

## 🔐 Authentication

### Register New User (Simple)
```http
POST /register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "securepassword123",
  "password_confirmation": "securepassword123",
  "phone": "+1234567890"
}
```

**Response:**
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "customer"
  },
  "token": "1|abc123xyz..."
}
```

### Register with OTP Verification (Recommended)

#### Step 1: Send OTP to Email
```http
POST /register/email
Content-Type: application/json

{
  "email": "john@example.com",
  "language": "ar"
}
```

**Response (200):**
```json
{
  "message": "OTP sent successfully"
}
```

**Error Responses:**
- `409 Conflict` - Email already registered
- `500 Server Error` - Failed to send email

#### Step 2: Verify OTP Code
```http
POST /register/validate-otp
Content-Type: application/json

{
  "email": "john@example.com",
  "otp": "123456"
}
```

**Response (200):**
```json
{
  "message": "OTP verified successfully"
}
```

**Error Response:**
- `422 Unprocessable Entity` - Invalid or expired OTP

#### Step 3: Complete Registration
```http
POST /register/complete
Content-Type: application/json

{
  "email": "john@example.com",
  "name": "John Doe",
  "password": "securepassword123",
  "password_confirmation": "securepassword123",
  "phone": "+1234567890",
  "otp": "123456"
}
```

**Response (200):**
```json
{
  "token": "1|abc123xyz...",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+1234567890",
    "email_verified_at": "2026-05-10T12:00:00.000000Z",
    "role": "customer"
  }
}
```

**Error Responses:**
- `401 Unauthorized` - Invalid or expired OTP
- `422 Unprocessable Entity` - Validation errors

**OTP Details:**
- OTP expires after 10 minutes
- OTP is sent in both English and Arabic based on `language` parameter
- OTP can only be used once

### Login
```http
POST /login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "securepassword123"
}
```

**Response:**
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "customer"
  },
  "token": "2|def456uvw..."
}
```

### Logout
```http
POST /logout
Authorization: Bearer YOUR_TOKEN
```

**Response:**
```json
{
  "message": "Logged out successfully"
}
```

### Using the Token

Include the token in all authenticated requests:
```http
Authorization: Bearer YOUR_TOKEN
```

---

## 👤 User Profile

### Get Current User Profile
```http
GET /profile
Authorization: Bearer YOUR_TOKEN
```

**Response:**
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "+1234567890",
  "role": "customer",
  "email_verified_at": "2026-05-01T10:00:00.000000Z",
  "created_at": "2026-05-01T10:00:00.000000Z"
}
```

### Update Profile
```http
PUT /profile
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json

{
  "name": "John Smith",
  "phone": "+1234567890"
}
```

### Change Password
```http
PUT /profile/password
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json

{
  "current_password": "oldpassword",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

---

## 🛍️ Products

### Get All Products (with Search & Filters)
```http
GET /products?search=laptop&category_id=5&sort_by=base_price&sort_order=asc&page=1
```

**Query Parameters:**
- `search` (optional): Search in product name, description, brand
- `category_id` (optional): Filter by category (includes subcategories)
- `sort_by` (optional): `created_at`, `name`, `base_price`, `updated_at` (default: `created_at`)
- `sort_order` (optional): `asc` or `desc` (default: `desc`)
- `page` (optional): Page number for pagination

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Gaming Laptop",
      "slug": "gaming-laptop",
      "description": "High performance laptop for gaming",
      "brand": "TechBrand",
      "base_price": 1299.99,
      "is_active": true,
      "category": {
        "id": 5,
        "name": "Laptops",
        "slug": "laptops"
      },
      "main_image": {
        "id": 10,
        "url": "https://dash.samsmy.cutscal.com/storage/10/laptop-main.jpg",
        "thumb": "https://dash.samsmy.cutscal.com/storage/10/conversions/laptop-main-thumb.jpg",
        "medium": "https://dash.samsmy.cutscal.com/storage/10/conversions/laptop-main-medium.jpg",
        "large": "https://dash.samsmy.cutscal.com/storage/10/conversions/laptop-main-large.jpg"
      },
      "gallery": [
        {
          "id": 11,
          "url": "https://dash.samsmy.cutscal.com/storage/11/laptop-side.jpg",
          "thumb": "https://dash.samsmy.cutscal.com/storage/11/conversions/laptop-side-thumb.jpg",
          "medium": "https://dash.samsmy.cutscal.com/storage/11/conversions/laptop-side-medium.jpg"
        }
      ],
      "variants": [
        {
          "id": 1,
          "size": "15 inch",
          "color": "#000000",
          "price": 1299.99,
          "stock": 10,
          "sku": "TEC-15-INCH-000000",
          "is_in_stock": true
        }
      ]
    }
  ],
  "links": {
    "first": "https://api.samsmy.cutscal.com/api/products?page=1",
    "last": "https://api.samsmy.cutscal.com/api/products?page=5",
    "prev": null,
    "next": "https://api.samsmy.cutscal.com/api/products?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 20,
    "to": 20,
    "total": 100
  }
}
```

### Get Single Product
```http
GET /products/{id_or_slug}
```

**Examples:**
- `GET /products/1` (by ID)
- `GET /products/gaming-laptop` (by slug)

**Response:** Same structure as single product in list above.

### Get Latest Products
```http
GET /products/latest?limit=8
```

**Query Parameters:**
- `limit` (optional): Number of products to return (default: 8, max: 50)

**Response:** Array of products (same structure as products list).

---

## 📂 Categories

### Get All Categories (Tree Structure)
```http
GET /categories
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Electronics",
      "slug": "electronics",
      "description": "All electronic items",
      "parent_id": null,
      "is_active": true,
      "products_count": 50,
      "children": [
        {
          "id": 5,
          "name": "Laptops",
          "slug": "laptops",
          "parent_id": 1,
          "products_count": 15,
          "children": []
        }
      ]
    }
  ]
}
```

### Get Single Category
```http
GET /categories/{id_or_slug}
```

**Response:**
```json
{
  "id": 5,
  "name": "Laptops",
  "slug": "laptops",
  "description": "Laptop computers",
  "parent_id": 1,
  "is_active": true,
  "products_count": 15,
  "parent": {
    "id": 1,
    "name": "Electronics",
    "slug": "electronics"
  },
  "children": []
}
```

---

## 🛒 Cart

### Add to Cart
```http
POST /cart/add
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json

{
  "product_variant_id": 1,
  "quantity": 2
}
```

**Response:**
```json
{
  "id": 10,
  "user_id": 1,
  "product_variant_id": 1,
  "quantity": 2,
  "product_variant": {
    "id": 1,
    "size": "15 inch",
    "color": "#000000",
    "price": 1299.99,
    "stock": 10,
    "product": {
      "id": 1,
      "name": "Gaming Laptop",
      "slug": "gaming-laptop"
    }
  }
}
```

### Get Cart Items
```http
GET /cart
Authorization: Bearer YOUR_TOKEN
```

**Response:**
```json
{
  "data": [
    {
      "id": 10,
      "quantity": 2,
      "product_variant": {
        "id": 1,
        "size": "15 inch",
        "color": "#000000",
        "price": 1299.99,
        "stock": 10,
        "product": {
          "id": 1,
          "name": "Gaming Laptop",
          "main_image": {
            "thumb": "https://dash.samsmy.cutscal.com/storage/10/conversions/laptop-thumb.jpg"
          }
        }
      },
      "subtotal": 2599.98
    }
  ],
  "summary": {
    "total_items": 2,
    "total_price": 2599.98
  }
}
```

### Update Cart Item
```http
PUT /cart/{cart_item_id}
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json

{
  "quantity": 3
}
```

### Remove from Cart
```http
DELETE /cart/{cart_item_id}
Authorization: Bearer YOUR_TOKEN
```

**Response:**
```json
{
  "message": "Item removed from cart"
}
```

---

## 📦 Orders

### Create Order (Checkout)
```http
POST /orders
Authorization: Bearer YOUR_TOKEN (optional for guest checkout)
Content-Type: application/json

{
  "items": [
    {
      "product_variant_id": 1,
      "quantity": 2,
      "price": 1299.99
    }
  ],
  "shipping_address": {
    "name": "John Doe",
    "phone": "+1234567890",
    "city_id": 1,
    "address_line": "123 Main St",
    "postal_code": "12345"
  },
  "payment_method": "cash_on_delivery",
  "notes": "Please call before delivery"
}
```

**Response:**
```json
{
  "id": 100,
  "order_number": "ORD-20260508-100",
  "reference_number": "REF-XYZ123ABC",
  "status": "pending",
  "payment_status": "pending",
  "payment_method": "cash_on_delivery",
  "total_amount": 2599.98,
  "items": [
    {
      "id": 1,
      "product_variant_id": 1,
      "quantity": 2,
      "price": 1299.99,
      "subtotal": 2599.98,
      "product_name": "Gaming Laptop",
      "variant_details": "15 inch, Black"
    }
  ],
  "shipping_address": {
    "name": "John Doe",
    "phone": "+1234567890",
    "city": "New York",
    "address_line": "123 Main St",
    "postal_code": "12345"
  },
  "created_at": "2026-05-08T14:30:00.000000Z",
  "tracking_url": "https://dash.samsmy.cutscal.com/order/REF-XYZ123ABC"
}
```

### Get My Orders (Authenticated)
```http
GET /orders?page=1
Authorization: Bearer YOUR_TOKEN
```

**Response:**
```json
{
  "data": [
    {
      "id": 100,
      "order_number": "ORD-20260508-100",
      "reference_number": "REF-XYZ123ABC",
      "status": "pending",
      "payment_status": "pending",
      "total_amount": 2599.98,
      "items_count": 1,
      "created_at": "2026-05-08T14:30:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 5
  }
}
```

### Get Order Details by Reference Number (Authenticated)
```http
GET /orders/ref/{reference_number}
Authorization: Bearer YOUR_TOKEN
```

**Example:** `GET /orders/ref/REF-XYZ123ABC`

**Response:** Full order details (same as create order response).

### Track Order (Public - No Auth Required)
```http
GET /orders/track/{reference_number}
```

**Example:** `GET /orders/track/REF-XYZ123ABC`

**Response:**
```json
{
  "order_number": "ORD-20260508-100",
  "reference_number": "REF-XYZ123ABC",
  "status": "processing",
  "payment_status": "paid",
  "total_amount": 2599.98,
  "created_at": "2026-05-08T14:30:00.000000Z",
  "status_history": [
    {
      "status": "pending",
      "timestamp": "2026-05-08T14:30:00Z"
    },
    {
      "status": "processing",
      "timestamp": "2026-05-08T15:00:00Z"
    }
  ]
}
```

---

## 📍 Cities

### Get All Cities
```http
GET /cities
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "New York",
      "country": "USA",
      "is_active": true
    },
    {
      "id": 2,
      "name": "Los Angeles",
      "country": "USA",
      "is_active": true
    }
  ]
}
```

---

## ❤️ Favorites (Wishlist)

### Get Favorite Products
```http
GET /favorites
Authorization: Bearer YOUR_TOKEN
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Gaming Laptop",
      "base_price": 1299.99,
      "main_image": {
        "thumb": "https://dash.samsmy.cutscal.com/storage/10/conversions/laptop-thumb.jpg"
      }
    }
  ]
}
```

### Add to Favorites
```http
POST /favorites
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json

{
  "product_id": 1
}
```

**Response:**
```json
{
  "message": "Product added to favorites",
  "is_favorite": true
}
```

### Remove from Favorites
```http
DELETE /favorites/{product_id}
Authorization: Bearer YOUR_TOKEN
```

**Response:**
```json
{
  "message": "Product removed from favorites",
  "is_favorite": false
}
```

---

## 🖼️ Image URLs

All product images are served from the dashboard storage with automatic conversions:

### Image Sizes
- **Original**: Full size uploaded image
- **Thumb**: 200x200px (for lists, thumbnails)
- **Medium**: 600x600px (for product cards)
- **Large**: 1200x1200px (for detail view)

### Image Object Structure
```json
{
  "id": 10,
  "url": "https://dash.samsmy.cutscal.com/storage/10/laptop.jpg",
  "thumb": "https://dash.samsmy.cutscal.com/storage/10/conversions/laptop-thumb.jpg",
  "medium": "https://dash.samsmy.cutscal.com/storage/10/conversions/laptop-medium.jpg",
  "large": "https://dash.samsmy.cutscal.com/storage/10/conversions/laptop-large.jpg"
}
```

### Best Practices
- Use **thumb** for product lists and cart items
- Use **medium** for product cards in grid view
- Use **large** for product detail pages
- Always include a placeholder image for loading states

---

## 📄 Pagination

All list endpoints return paginated results:

```json
{
  "data": [...],
  "links": {
    "first": "https://api.samsmy.cutscal.com/api/products?page=1",
    "last": "https://api.samsmy.cutscal.com/api/products?page=10",
    "prev": null,
    "next": "https://api.samsmy.cutscal.com/api/products?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 10,
    "per_page": 20,
    "to": 20,
    "total": 200
  }
}
```

**To load next page:** Use the `links.next` URL or increment the `page` parameter.

---

## ⚠️ Error Handling

### Error Response Format
```json
{
  "message": "Error description",
  "errors": {
    "field_name": [
      "Specific error message"
    ]
  }
}
```

### HTTP Status Codes
- **200 OK**: Success
- **201 Created**: Resource created successfully
- **204 No Content**: Success with no response body
- **400 Bad Request**: Invalid request
- **401 Unauthorized**: Missing or invalid token
- **403 Forbidden**: Insufficient permissions
- **404 Not Found**: Resource not found
- **422 Unprocessable Entity**: Validation errors
- **429 Too Many Requests**: Rate limit exceeded
- **500 Internal Server Error**: Server error

### Common Error Examples

**Validation Error (422):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

**Unauthorized (401):**
```json
{
  "message": "Unauthenticated."
}
```

**Not Found (404):**
```json
{
  "error": "Product not found"
}
```

---

## 🔒 Security Best Practices

### 1. Store Tokens Securely
- **iOS**: Use Keychain
- **Android**: Use EncryptedSharedPreferences
- **Never** store tokens in plain text

### 2. Handle Token Expiration
```swift
// Example: iOS Swift
if response.statusCode == 401 {
    // Token expired, redirect to login
    clearToken()
    showLoginScreen()
}
```

### 3. Use HTTPS Only
All API calls must use `https://` protocol.

### 4. Implement Rate Limiting
The API has rate limits. Handle 429 responses gracefully:
```json
{
  "message": "Too many requests. Please try again later."
}
```

### 5. Validate Input
Always validate user input before sending to API.

---

## 🚀 Performance Optimization

### 1. Cache Images
```swift
// Use image caching libraries:
// iOS: SDWebImage, Kingfisher
// Android: Glide, Picasso
```

### 2. Implement Pull-to-Refresh
Allow users to refresh data manually.

### 3. Use Appropriate Image Sizes
```javascript
// Product List → Use thumb (200x200)
imageUrl = product.main_image.thumb

// Product Detail → Use large (1200x1200)
imageUrl = product.main_image.large
```

### 4. Pagination
Load data in pages (20 items default). Implement infinite scroll or "Load More" button.

### 5. Search Debouncing
Delay search requests by 300-500ms while user is typing:
```javascript
// Pseudo-code
let searchTimer;
function onSearchInput(query) {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        searchProducts(query);
    }, 300);
}
```

---

## 📱 Example Integration

### React Native Example
```javascript
import axios from 'axios';

const API_BASE_URL = 'https://api.samsmy.cutscal.com/api';

// Create axios instance
const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Add token to requests
api.interceptors.request.use((config) => {
  const token = getStoredToken(); // Your token storage
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Handle errors globally
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Token expired, logout
      clearToken();
      navigateToLogin();
    }
    return Promise.reject(error);
  }
);

// Login
async function login(email, password) {
  const response = await api.post('/login', { email, password });
  saveToken(response.data.token);
  return response.data.user;
}

// Get Products
async function getProducts(page = 1, search = '') {
  const response = await api.get('/products', {
    params: { page, search }
  });
  return response.data;
}

// Create Order
async function createOrder(orderData) {
  const response = await api.post('/orders', orderData);
  return response.data;
}
```

### Swift (iOS) Example
```swift
import Foundation

class APIClient {
    static let shared = APIClient()
    let baseURL = "https://api.samsmy.cutscal.com/api"
    
    func login(email: String, password: String, completion: @escaping (Result<User, Error>) -> Void) {
        guard let url = URL(string: "\(baseURL)/login") else { return }
        
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        
        let body = ["email": email, "password": password]
        request.httpBody = try? JSONEncoder().encode(body)
        
        URLSession.shared.dataTask(with: request) { data, response, error in
            // Handle response
        }.resume()
    }
    
    func getProducts(page: Int, search: String?, completion: @escaping (Result<ProductResponse, Error>) -> Void) {
        var components = URLComponents(string: "\(baseURL)/products")!
        components.queryItems = [
            URLQueryItem(name: "page", value: "\(page)")
        ]
        if let search = search, !search.isEmpty {
            components.queryItems?.append(URLQueryItem(name: "search", value: search))
        }
        
        guard let url = components.url else { return }
        
        var request = URLRequest(url: url)
        if let token = getToken() {
            request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        }
        
        URLSession.shared.dataTask(with: request) { data, response, error in
            // Handle response
        }.resume()
    }
}
```

---

## 🧪 Testing the API

### Using cURL
```bash
# Login
curl -X POST https://api.samsmy.cutscal.com/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"john@example.com","password":"password123"}'

# Get Products (with token)
curl -X GET "https://api.samsmy.cutscal.com/api/products?page=1" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Create Order
curl -X POST https://api.samsmy.cutscal.com/api/orders \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d @order.json
```

### Using Postman
1. Import the endpoints above
2. Set environment variable `base_url` = `https://api.samsmy.cutscal.com/api`
3. Set environment variable `token` after login
4. Use `{{base_url}}` and `{{token}}` in requests

---

## 📞 Support

For API issues or questions:
- **Email**: support@samsmy.com
- **Documentation**: https://api.samsmy.cutscal.com/docs

---

## 📝 Changelog

### Version 1.8 (Current)
- ✅ Product search and filtering
- ✅ Category tree structure
- ✅ Guest checkout support
- ✅ Order tracking without authentication
- ✅ Image conversions (thumb, medium, large)
- ✅ Favorites/wishlist functionality
- ✅ Pagination on all list endpoints

---

## 🎯 Quick Start Checklist

- [ ] Implement token storage (Keychain/EncryptedSharedPreferences)
- [ ] Set up axios/URLSession with base URL
- [ ] Add Authorization header interceptor
- [ ] Implement login/register screens
- [ ] Handle 401 errors (token expiration)
- [ ] Use appropriate image sizes (thumb/medium/large)
- [ ] Implement image caching
- [ ] Add pull-to-refresh
- [ ] Implement pagination/infinite scroll
- [ ] Add search debouncing
- [ ] Handle network errors gracefully
- [ ] Test with production API URL

Good luck building your mobile app! 🚀
