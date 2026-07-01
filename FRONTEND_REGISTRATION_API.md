# Registration API — Frontend Integration

**Base URL:** `https://api.samsmy.cutscal.com/api`

---

## 🔐 Registration Options

### Option 1: Simple Registration (No OTP)

**POST** `/register`

```json
{
  "name": "John Doe",
  "email": "user@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "phone": "+1234567890"
}
```

**Response (201):**
```json
{
  "token": "1|abc123...",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com"
  }
}
```

---

## ✉️ Option 2: OTP Verification (Recommended)

### Step 1: Send OTP to Email

**POST** `/register/email`

```json
{
  "email": "user@example.com",
  "language": "ar"
}
```

**Success (200):**
```json
{
  "message": "OTP sent successfully"
}
```

**Errors:**
- `409` → Email already registered
- `422` → Invalid email format

---

### Step 2: Verify OTP

**POST** `/register/validate-otp`

```json
{
  "email": "user@example.com",
  "otp": "123456"
}
```

**Success (200):**
```json
{
  "message": "OTP verified successfully"
}
```

**Error:**
- `422` → Invalid or expired OTP

---

### Step 3: Complete Registration

**POST** `/register/complete`

```json
{
  "email": "user@example.com",
  "name": "John Doe",
  "password": "password123",
  "password_confirmation": "password123",
  "phone": "+1234567890",
  "otp": "123456"
}
```

**Success (200):**
```json
{
  "token": "1|abc123...",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "email_verified_at": "2026-05-10T12:00:00.000000Z"
  }
}
```

**Errors:**
- `401` → Invalid or already used OTP
- `422` → Validation errors

---

## 📋 Important Notes

### OTP Details
- ⏱️ **Expires:** 10 minutes
- 🔒 **One-time use:** Cannot be reused
- 🌍 **Languages:** `"ar"` (Arabic) or `"en"` (English)

### Headers Required
```
Content-Type: application/json
Accept: application/json
```

### Store Token After Success
```javascript
localStorage.setItem('auth_token', response.token);
localStorage.setItem('auth_user', JSON.stringify(response.user));
```

### Use Token for Authenticated Requests
```
Authorization: Bearer YOUR_TOKEN
```

---

## 🧪 Quick Test (PowerShell)

```powershell
# Step 1: Send OTP
$body = @{ email = "test@example.com"; language = "ar" } | ConvertTo-Json
Invoke-RestMethod -Uri "http://localhost:8000/api/register/email" -Method POST -ContentType "application/json" -Body $body

# Step 2: Verify OTP (replace 123456 with actual code from email)
$body = @{ email = "test@example.com"; otp = "123456" } | ConvertTo-Json
Invoke-RestMethod -Uri "http://localhost:8000/api/register/validate-otp" -Method POST -ContentType "application/json" -Body $body

# Step 3: Complete
$body = @{
    email = "test@example.com"
    name = "Test User"
    password = "password123"
    password_confirmation = "password123"
    otp = "123456"
} | ConvertTo-Json
Invoke-RestMethod -Uri "http://localhost:8000/api/register/complete" -Method POST -ContentType "application/json" -Body $body
```

---

## 🚨 Error Handling

All `422` validation errors return:
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email has already been taken."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

Display the first error for each field to the user.

---

## 📞 Support

For issues or questions, contact the backend team.

**API Version:** 1.8
