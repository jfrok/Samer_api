# API Endpoints Reference

**Base URL:** `https://api.samsmy.cutscal.com/api`

---

## 🔐 Authentication

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/register` | No | Register new user (simple) |
| POST | `/register/email` | No | Send OTP to email (Step 1) |
| POST | `/register/validate-otp` | No | Verify OTP code (Step 2) |
| POST | `/register/complete` | No | Complete registration with OTP (Step 3) |
| POST | `/login` | No | Login user |
| POST | `/logout` | Yes | Logout current user |

### Register (Simple - No OTP)
```http
POST /register
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "phone": "+1234567890"
}
```

**Response:**
```json
{
  "token": "1|abc123...",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  }
}
```

### Register with OTP (3-Step Process)

#### Step 1: Send OTP to Email
```http
POST /register/email
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

**Errors:**
- `409` - Email already registered
- `500` - Failed to send email

#### Step 2: Verify OTP
```http
POST /register/validate-otp
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

**Errors:**
- `422` - Invalid or expired OTP

#### Step 3: Complete Registration
```http
POST /register/complete
{
  "email": "john@example.com",
  "name": "John Doe",
  "password": "password123",
  "password_confirmation": "password123",
  "phone": "+1234567890",
  "otp": "123456"
}
```

**Response (200):**
```json
{
  "token": "1|abc123...",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "email_verified_at": "2026-05-10T12:00:00.000000Z"
  }
}
```

**Errors:**
- `401` - Invalid or expired OTP
- `422` - Validation errors

### Login
```http
POST /login
{
  "email": "john@example.com",
  "password": "password123"
}
```

---

## 👤 User Profile

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/profile` | Yes | Get current user profile |
| PUT | `/profile` | Yes | Update profile |
| PUT | `/profile/password` | Yes | Change password |

### Update Profile
```http
PUT /profile
{
  "name": "John Smith",
  "phone": "+1234567890"
}
```

### Change Password
```http
PUT /profile/password
{
  "current_password": "oldpass",
  "password": "newpass123",
  "password_confirmation": "newpass123"
}
```

---

## 🛍️ Products

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/products` | No | Get all products (paginated) |
| GET | `/products/{id_or_slug}` | No | Get single product |
| GET | `/products/latest` | No | Get latest products |

### Get Products with Filters
```http
GET /products?search=laptop&category_id=5&sort_by=base_price&sort_order=asc&page=1
```

**Query Parameters:**
- `search` - Search in name, description, brand
- `category_id` - Filter by category
- `sort_by` - `created_at`, `name`, `base_price`, `updated_at`
- `sort_order` - `asc` or `desc`
- `page` - Page number

### Get Latest Products
```http
GET /products/latest?limit=8
```

**Query Parameters:**
- `limit` - Number of products (default: 8, max: 50)

---

## 📂 Categories

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/categories` | No | Get all categories (tree) |
| GET | `/categories/{id_or_slug}` | No | Get single category |

---

## 🛒 Cart

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/cart` | Yes | Get cart items |
| POST | `/cart/add` | Yes | Add item to cart |
| PUT | `/cart/{cart_item_id}` | Yes | Update cart item quantity |
| DELETE | `/cart/{cart_item_id}` | Yes | Remove item from cart |

### Add to Cart
```http
POST /cart/add
{
  "product_variant_id": 1,
  "quantity": 2
}
```

### Update Cart Item
```http
PUT /cart/{cart_item_id}
{
  "quantity": 3
}
```

---

## 📦 Orders

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/orders` | Yes | Get user's orders (paginated) |
| POST | `/orders` | Optional | Create new order |
| GET | `/orders/ref/{reference}` | Yes | Get order by reference (must own) |
| GET | `/orders/track/{reference}` | No | Track order (public) |

### Create Order
```http
POST /orders
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
  "notes": "Optional delivery notes"
}
```

### Get My Orders
```http
GET /orders?page=1
```

### Get Order Details
```http
GET /orders/ref/REF-XYZ123ABC
Authorization: Bearer YOUR_TOKEN
```

### Track Order (Public)
```http
GET /orders/track/REF-XYZ123ABC
```

---

## 📍 Cities

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/cities` | No | Get all active cities |

---

## ❤️ Favorites

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/favorites` | Yes | Get favorite products |
| POST | `/favorites` | Yes | Add product to favorites |
| DELETE | `/favorites/{product_id}` | Yes | Remove from favorites |

### Add to Favorites
```http
POST /favorites
{
  "product_id": 1
}
```

---

## 📊 Admin Endpoints

### Products

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/admin/products` | Admin | Create product |
| PUT | `/admin/products/{id}` | Admin | Update product |
| DELETE | `/admin/products/{id}` | Admin | Delete product |
| GET | `/admin/products/dashboard-stats` | Admin | Get dashboard statistics |

### Orders

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/admin/orders` | Admin | Get all orders (paginated) |
| GET | `/admin/orders/{id}` | Admin | Get order details |
| PUT | `/admin/orders/{id}` | Admin | Update order status |
| DELETE | `/admin/orders/{id}` | Admin | Soft delete order |

### Categories

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/admin/categories` | Admin | Create category |
| PUT | `/admin/categories/{id}` | Admin | Update category |
| DELETE | `/admin/categories/{id}` | Admin | Delete category |

### Cities

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/admin/cities` | Admin | Create city |
| PUT | `/admin/cities/{id}` | Admin | Update city |
| DELETE | `/admin/cities/{id}` | Admin | Delete city |

---

## 🔑 Authentication Header

For endpoints requiring authentication, include:
```http
Authorization: Bearer YOUR_TOKEN_HERE
```

---

## 📄 Response Format

### Success Response
```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 100
  }
}
```

### Error Response
```json
{
  "message": "Error message",
  "errors": {
    "field": ["Error detail"]
  }
}
```

---

## 🖼️ Image URLs

Product images are returned with multiple sizes:

```json
{
  "id": 10,
  "url": "https://dash.samsmy.cutscal.com/storage/10/image.jpg",
  "thumb": "https://dash.samsmy.cutscal.com/storage/10/conversions/image-thumb.jpg",
  "medium": "https://dash.samsmy.cutscal.com/storage/10/conversions/image-medium.jpg",
  "large": "https://dash.samsmy.cutscal.com/storage/10/conversions/image-large.jpg"
}
```

**Sizes:**
- **thumb**: 200x200px - Lists, thumbnails
- **medium**: 600x600px - Grid cards
- **large**: 1200x1200px - Detail pages

---

## 📊 HTTP Status Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 201 | Created |
| 204 | Success (no content) |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Validation Error |
| 429 | Too Many Requests |
| 500 | Server Error |

---

## 🧪 Quick Test

```bash
# Login
curl -X POST https://api.samsmy.cutscal.com/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'

# Get Products
curl https://api.samsmy.cutscal.com/api/products

# Get Product by ID
curl https://api.samsmy.cutscal.com/api/products/1

# Add to Cart
curl -X POST https://api.samsmy.cutscal.com/api/cart/add \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"product_variant_id":1,"quantity":2}'

# Track Order
curl https://api.samsmy.cutscal.com/api/orders/track/REF-ABC123
```

---

## 📞 Support

**Email:** support@samsmy.com  
**API Version:** 1.8
