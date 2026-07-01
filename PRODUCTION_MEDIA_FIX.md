# Production Media Storage Fix - Quick Guide

## 🔴 Problem
Images uploaded via API are not appearing in production because:
1. Storage is pointing to wrong directory (API storage instead of dashboard storage)
2. Queue worker not running (image conversions not generated)
3. `public_html` is outside the project directory

## ✅ Solution

Your production structure:
```
/var/www/
├── api.samsmy.cutscal.com/      ← Laravel API web root
│   ├── public/                   ← Laravel public folder
│   ├── app/
│   ├── storage/
│   └── .env
└── dash.samsmy.cutscal.com/     ← Dashboard web root (frontend)
    └── storage/                  ← Images saved here
```

**Key Point:** `api.samsmy.cutscal.com` directory IS the Laravel project root (web accessible).

**Symlink Note:** Dashboard needs `storage/` accessible via web. The symlink command depends on your dashboard structure:
- If `/var/www/dash.samsmy.cutscal.com/` IS the web root → `cd /var/www/dash.samsmy.cutscal.com && ln -s storage storage`
- If you have `/public/` subfolder as web root → `cd /var/www/dash.samsmy.cutscal.com/public && ln -s ../storage storage`

---

## 🚀 Quick Fix (SSH to Production)

### 1. Configure API to use Dashboard Storage

Edit API `.env`:
```bash
cd /var/www/api.samsmy.cutscal.com
nano .env
```

Add/update these lines:
```bash
MEDIA_DISK=dashboard_storage
DASHBOARD_STORAGE_PATH=/var/www/dash.samsmy.cutscal.com/storage
DASHBOARD_STORAGE_URL=https://dash.samsmy.cutscal.com/storage
QUEUE_CONNECTION=redis
```

### 2. Create Required Directories

```bash
# Create storage directories in dashboard
cd /var/www/dash.samsmy.cutscal.com
mkdir -p storage/app/public
mkdir -p storage/media-library/temp

# Set permissions (www-data = web server user)
sudo chown -R www-data:www-data storage
sudo chmod -R 775 storage

# Create temp directory for API
cd /var/www/api.samsmy.cutscal.com
mkdir -p storage/media-library/temp
sudo chown -R www-data:www-data storage/media-library
sudo chmod -R 775 storage/media-library
```

### 3. Link Dashboard Storage (Create Symlink)

**Goal:** Make `storage/` folder accessible via browser at `https://dash.samsmy.cutscal.com/storage/`

**If dashboard root is web-accessible:**
```bash
cd /var/www/dash.samsmy.cutscal.com
ln -s storage storage

# Verify
ls -la | grep storage
# Should show: storage -> storage
```

**If dashboard has a public/ subfolder as web root:**
```bash
cd /var/www/dash.samsmy.cutscal.com/public
ln -s ../storage storage

# Verify
ls -la | grep storage
# Should show: storage -> ../storage
```

**Test it works:**
```bash
# Check if symlink exists
ls -la /var/www/dash.samsmy.cutscal.com/storage
# OR
ls -la /var/www/dash.samsmy.cutscal.com/public/storage
```

### 4. Clear Config Cache

```bash
cd /var/www/api.samsmy.cutscal.com
php artisan config:clear
php artisan cache:clear
```

### 5. Start Queue Worker (for image conversions)

**Option A: Manual test (temporary)**
```bash
php -d memory_limit=512M artisan queue:work redis --sleep=3 --tries=3 --queue=default,media
```
Press Ctrl+C to stop.

**Option B: Supervisor (permanent - recommended)**

Create config:
```bash
sudo nano /etc/supervisor/conf.d/samsmy-queue.conf
```

Paste this (update paths):
```ini
[program:samsmy-queue]
process_name=%(program_name)s_%(process_num)02d
command=/usr/bin/php -d memory_limit=512M /var/www/api.samsmy.cutscal.com/artisan queue:work redis --sleep=3 --tries=3 --queue=default,media --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/api.samsmy.cutscal.com/storage/logs/queue.log
stopwaitsecs=3600
```

Start it:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start samsmy-queue:*
sudo supervisorctl status
```

---

## 🧪 Test It Works

### 1. Test Storage Configuration
```bash
cd /var/www/api.samsmy.cutscal.com
php artisan tinker
```

```php
// Check disk
config('media-library.disk_name');
// Should return: "dashboard_storage"

// Test write
Storage::disk('dashboard_storage')->put('test.txt', 'Hello');
Storage::disk('dashboard_storage')->exists('test.txt');
// Should return: true

// Check path
Storage::disk('dashboard_storage')->path('test.txt');
// Should show: /var/www/dash.samsmy.cutscal.com/storage/app/test.txt

// Cleanup
Storage::disk('dashboard_storage')->delete('test.txt');
exit
```

### 2. Upload Product Image

Using Postman or curl:
```bash
POST https://api.samsmy.cutscal.com/api/admin/products/1
Authorization: Bearer YOUR_TOKEN
Content-Type: multipart/form-data

Body:
  gallery[0]: [select image file]
```

### 3. Check Files Created

```bash
# Check dashboard storage (files should be here)
ls -la /var/www/dash.samsmy.cutscal.com/storage/app/public/
# Should see numbered folders (media IDs)

# Check a specific media folder
ls -la /var/www/dash.samsmy.cutscal.com/storage/app/public/1/
# Should see:
# - Original image file
# - conversions/ folder with thumb, medium, large images
```

### 4. Access Image in Browser

Visit: `https://dash.samsmy.cutscal.com/storage/1/conversions/image-thumb.jpg`

Should display the image (not 404).

---

## 🚨 Common Issues

### "Queue not processing"
```bash
# Check supervisor status
sudo supervisorctl status

# View queue logs
tail -f /var/www/api.samsmy.cutscal.com/storage/logs/queue.log

# Restart queue worker
sudo supervisorctl restart samsmy-queue:*
```

### "Permission denied"
```bash
# Fix dashboard storage
cd /var/www/dash.samsmy.cutscal.com
sudo chown -R www-data:www-data storage
sudo chmod -R 775 storage

# Fix API storage
cd /var/www/api.samsmy.cutscal.com
sudo chown -R www-data:www-data storage
sudo chmod -R 775 storage
```

### "Images still not showing"
```bash
# Check symlink exists
ls -la /var/www/dash.samsmy.cutscal.com/ | grep storage

# If missing, recreate:
cd /var/www/dash.samsmy.cutscal.com
ln -s storage storage
# OR if you have a public/ folder:
# cd /var/www/dash.samsmy.cutscal.com/public && ln -s ../storage storage
```

### "Memory exhausted"
```bash
# Increase PHP memory limit
sudo nano /etc/php/8.1/fpm/php.ini

# Change:
memory_limit = 512M

# Restart PHP
sudo systemctl restart php8.1-fpm
```

---

## 📋 Deployment Checklist

Every deployment:
- [ ] `.env` has `MEDIA_DISK=dashboard_storage`
- [ ] Dashboard storage exists: `/var/www/dash.samsmy.cutscal.com/storage`
- [ ] Symlink exists: `public_html/storage -> ../storage`
- [ ] Permissions: 775, owner: www-data
- [ ] Queue worker running via supervisor
- [ ] Config cache cleared: `php artisan config:clear`
- [ ] Test image upload works
- [ ] Test image URL accessible in browser

---

## 📞 Quick Commands

```bash
# Check queue
sudo supervisorctl status

# Restart queue
sudo supervisorctl restart samsmy-queue:*

# View logs
tail -f /var/www/api.samsmy.cutscal.com/storage/logs/laravel.log
tail -f /var/www/api.samsmy.cutscal.com/storage/logs/queue.log

# Clear cache
cd /var/www/api.samsmy.cutscal.com
php artisan config:clear

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

---

## 🎯 Summary

**3 Required Changes:**
1. ✅ Set `MEDIA_DISK=dashboard_storage` in API `.env` (`/var/www/api.samsmy.cutscal.com/`)
2. ✅ Create symlink in dashboard root: `ln -s storage storage`
3. ✅ Run queue worker via supervisor (for image conversions)

**File Structure:**
```
API saves to    → /var/www/dash.samsmy.cutscal.com/storage/app/public/1/
Symlink         → /var/www/dash.samsmy.cutscal.com/storage (self-link for web access)
Browser access  → https://dash.samsmy.cutscal.com/storage/1/image.jpg
```

**Note:** If dashboard has `public/` subfolder, create symlink there: `cd public && ln -s ../storage storage`

Done! 🚀
