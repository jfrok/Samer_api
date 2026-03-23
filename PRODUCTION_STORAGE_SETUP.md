# Production Storage Setup Guide

## Overview

This guide explains how to configure the Laravel API to store uploaded images in the dashboard's storage folder instead of the API's storage folder. This is useful when your frontend dashboard needs direct access to images.

---

## Folder Structure

```
/var/www/
├── api.port.samsmy.com/              (Laravel API - current project)
│   ├── app/
│   ├── config/
│   ├── .env
│   └── ...
│
└── dashboard.port.samsmy.com/        (Frontend Dashboard)
    ├── public/
    └── storage/                       ← Images stored here
        ├── 1/
        ├── 2/
        └── ...
```

---

## Configuration Steps

### 1. Update .env File (Production Server)

Edit your production `.env` file:

```env
# Change MEDIA_DISK from 'public' to 'dashboard_storage'
MEDIA_DISK=dashboard_storage

# Set the absolute path to dashboard storage
DASHBOARD_STORAGE_PATH=/var/www/dashboard.port.samsmy.com/storage

# Set the public URL where images will be accessed
DASHBOARD_STORAGE_URL=https://dashboard.port.samsmy.com/storage
```

### 2. Create Storage Directory

On your production server, create the storage directory if it doesn't exist:

```bash
# SSH into your server
ssh user@your-server.com

# Create storage directory
mkdir -p /var/www/dashboard.port.samsmy.com/storage

# Set proper permissions
chmod -R 775 /var/www/dashboard.port.samsmy.com/storage

# Set owner (replace 'www-data' with your web server user)
chown -R www-data:www-data /var/www/dashboard.port.samsmy.com/storage
```

### 3. Configure Web Server

You need to ensure the dashboard's storage folder is accessible via HTTP.

#### For Nginx

Edit your dashboard nginx config:

```nginx
server {
    server_name dashboard.port.samsmy.com;
    root /var/www/dashboard.port.samsmy.com/public;

    # Allow access to storage folder
    location /storage {
        alias /var/www/dashboard.port.samsmy.com/storage;
        access_log off;
        expires max;
        add_header Cache-Control "public, immutable";
    }

    # Rest of your config...
}
```

Reload nginx:
```bash
sudo nginx -t
sudo systemctl reload nginx
```

#### For Apache

Edit your dashboard Apache config or `.htaccess`:

```apache
<VirtualHost *:80>
    ServerName dashboard.port.samsmy.com
    DocumentRoot /var/www/dashboard.port.samsmy.com/public

    # Allow access to storage folder
    Alias /storage /var/www/dashboard.port.samsmy.com/storage
    <Directory /var/www/dashboard.port.samsmy.com/storage>
        Options Indexes FollowSymLinks
        AllowOverride None
        Require all granted
    </Directory>

    # Rest of your config...
</VirtualHost>
```

Reload Apache:
```bash
sudo apachectl configtest
sudo systemctl reload apache2
```

---

## Environment Examples

### Development (.env)
```env
APP_ENV=local
MEDIA_DISK=public
# Images stored in: storage/app/public
# Accessed via: http://localhost:8000/storage/...
```

### Production (.env)
```env
APP_ENV=production
MEDIA_DISK=dashboard_storage
DASHBOARD_STORAGE_PATH=/var/www/dashboard.port.samsmy.com/storage
DASHBOARD_STORAGE_URL=https://dashboard.port.samsmy.com/storage
# Images stored in: /var/www/dashboard.port.samsmy.com/storage/
# Accessed via: https://dashboard.port.samsmy.com/storage/...
```

---

## Testing the Setup

### 1. Upload Test Image

```bash
# Using curl to upload an image
curl -X POST https://api.port.samsmy.com/api/admin/products \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "name=Test Product" \
  -F "category_id=1" \
  -F "base_price=100" \
  -F "gallery[]=@test-image.jpg"
```

### 2. Check File Location

```bash
# SSH into server
ssh user@your-server.com

# Verify image was saved in dashboard storage
ls -la /var/www/dashboard.port.samsmy.com/storage/

# You should see numbered folders (1, 2, 3, etc.)
# Each contains the uploaded images and conversions
```

### 3. Access Image via URL

The API response will include URLs like:
```json
{
  "gallery": [
    {
      "original_url": "https://dashboard.port.samsmy.com/storage/1/image.jpg",
      "conversions": {
        "thumb": "https://dashboard.port.samsmy.com/storage/1/conversions/image-thumb.jpg",
        "medium": "https://dashboard.port.samsmy.com/storage/1/conversions/image-medium.jpg"
      }
    }
  ]
}
```

Try accessing these URLs in your browser to verify they work.

---

## Permissions Troubleshooting

### Issue: "Permission denied" when uploading

**Solution:**
```bash
# Give web server write permissions
sudo chown -R www-data:www-data /var/www/dashboard.port.samsmy.com/storage
sudo chmod -R 775 /var/www/dashboard.port.samsmy.com/storage

# If using SELinux (CentOS/RHEL)
sudo chcon -R -t httpd_sys_rw_content_t /var/www/dashboard.port.samsmy.com/storage
```

### Issue: Images upload but return 404

**Possible causes:**
1. Web server not configured to serve storage folder
2. Symlink not created
3. Wrong DASHBOARD_STORAGE_URL in .env

**Solution:**
```bash
# Verify nginx/apache config includes storage alias
# Verify .env has correct URL
# Test direct file access:
curl -I https://dashboard.port.samsmy.com/storage/1/test.jpg
```

---

## Migration from Local to Production

If you already have images in local development:

### Option 1: Copy existing images

```bash
# From local to server
scp -r storage/app/public/* user@server:/var/www/dashboard.port.samsmy.com/storage/

# Set permissions
ssh user@server "chown -R www-data:www-data /var/www/dashboard.port.samsmy.com/storage"
```

### Option 2: Update database URLs

If your media table has full URLs stored:

```sql
-- Update media table URLs
UPDATE media 
SET custom_properties = JSON_SET(
    custom_properties, 
    '$.url', 
    REPLACE(
        JSON_EXTRACT(custom_properties, '$.url'),
        'http://localhost:8000/storage',
        'https://dashboard.port.samsmy.com/storage'
    )
)
WHERE custom_properties LIKE '%localhost%';
```

---

## Advanced: Using Symbolic Link (Alternative)

Instead of copying files, you can create a symlink:

```bash
# Create symlink in dashboard pointing to API storage
ln -s /var/www/api.port.samsmy.com/storage/app/public \
      /var/www/dashboard.port.samsmy.com/storage

# Verify symlink
ls -la /var/www/dashboard.port.samsmy.com/storage
# Should show: storage -> /var/www/api.port.samsmy.com/storage/app/public
```

Then keep using `MEDIA_DISK=public` in .env, but configure nginx/apache to serve the symlink.

---

## Security Considerations

### 1. Restrict File Types

Already configured in Product model:
```php
->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
```

### 2. Set Max File Size

In `config/media-library.php`:
```php
'max_file_size' => 1024 * 1024 * 10, // 10MB
```

### 3. Prevent Directory Listing

**Nginx:**
```nginx
location /storage {
    alias /var/www/dashboard.port.samsmy.com/storage;
    autoindex off;  # Disable directory listing
}
```

**Apache:**
```apache
<Directory /var/www/dashboard.port.samsmy.com/storage>
    Options -Indexes  # Disable directory listing
</Directory>
```

### 4. Add Security Headers

**Nginx:**
```nginx
location /storage {
    alias /var/www/dashboard.port.samsmy.com/storage;
    add_header X-Content-Type-Options "nosniff";
    add_header X-Frame-Options "SAMEORIGIN";
}
```

---

## Multiple Environment Setup

### .env.development
```env
MEDIA_DISK=public
DASHBOARD_STORAGE_PATH=storage/app/public
DASHBOARD_STORAGE_URL=http://localhost:8000/storage
```

### .env.staging
```env
MEDIA_DISK=dashboard_storage
DASHBOARD_STORAGE_PATH=/var/www/staging.dashboard.port.samsmy.com/storage
DASHBOARD_STORAGE_URL=https://staging.dashboard.port.samsmy.com/storage
```

### .env.production
```env
MEDIA_DISK=dashboard_storage
DASHBOARD_STORAGE_PATH=/var/www/dashboard.port.samsmy.com/storage
DASHBOARD_STORAGE_URL=https://dashboard.port.samsmy.com/storage
```

---

## Benefits of This Setup

✅ **Direct File Access** - Frontend can access images without going through API  
✅ **Better Performance** - No API overhead for serving images  
✅ **Easier CDN Integration** - Point CDN to dashboard domain  
✅ **Cleaner URLs** - Images served from dashboard domain  
✅ **Separation of Concerns** - API and storage are independent  

---

## Summary

1. **Update .env** → Set `MEDIA_DISK=dashboard_storage`
2. **Create storage folder** → `/var/www/dashboard.port.samsmy.com/storage`
3. **Set permissions** → `chmod 775` and `chown www-data`
4. **Configure web server** → Add `/storage` location block
5. **Test upload** → Verify images save to correct location
6. **Test access** → Verify images accessible via URL

Your images will now be stored in the dashboard storage folder and served directly from `https://dashboard.port.samsmy.com/storage/`! 🎉
