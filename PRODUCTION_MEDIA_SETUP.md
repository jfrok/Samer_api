# Production Media Storage Setup Guide

## Issue
Images are not being saved to storage in production because:
1. Wrong storage disk configuration
2. Queue worker not processing image conversions
3. Missing directory permissions

---

## 🔧 Fix Steps

### 1. Update Production .env

SSH to your production server and add these lines to `.env`:

```bash
# Media Storage Configuration
MEDIA_DISK=dashboard_storage
DASHBOARD_STORAGE_PATH=/var/www/dash.samsmy.cutscal.com/storage
DASHBOARD_STORAGE_URL=https://dash.samsmy.cutscal.com/storage

# Queue Configuration (for image processing)
QUEUE_CONNECTION=redis
QUEUE_CONVERSIONS_BY_DEFAULT=true
```

**Important:** Replace the paths with your actual production paths!

To find your dashboard storage path:
```bash
cd /var/www
ls -la | grep dashboard
```

---

### 2. Create Required Directories

```bash
cd /var/www/dash.samsmy.cutscal.com/storage

# Create app directories
mkdir -p app/public

# Create media library temp directory
mkdir -p media-library/temp

# Set proper permissions
chmod -R 775 app
chmod -R 775 media-library
chown -R www-data:www-data app
chown -R www-data:www-data media-library
```

---

### 3. Clear Configuration Cache

```bash
cd /path/to/your/api

# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Recache configuration
php artisan config:cache
```

---

### 4. Install & Configure Redis (Recommended)

**Why Redis?** Better performance and reliability for queues in production.

```bash
# Install Redis
sudo apt update
sudo apt install redis-server

# Start Redis
sudo systemctl start redis-server
sudo systemctl enable redis-server

# Test Redis
redis-cli ping
# Should return: PONG
```

Update `.env`:
```bash
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

---

### 5. Setup Queue Worker with Supervisor

Create supervisor configuration:

```bash
sudo nano /etc/supervisor/conf.d/laravel-queue-worker.conf
```

Add this configuration:

```ini
[program:laravel-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=/usr/bin/php -d memory_limit=512M /path/to/your/api/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --queue=default,media
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/your/api/storage/logs/queue-worker.log
stopwaitsecs=3600
```

**Important Changes:**
- Replace `/path/to/your/api/` with your actual API path
- `memory_limit=512M` - Required for image processing
- `numprocs=2` - Run 2 worker processes (adjust based on server resources)
- `--queue=default,media` - Process both queues

Start the worker:

```bash
# Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update

# Start the worker
sudo supervisorctl start laravel-queue-worker:*

# Check status
sudo supervisorctl status
```

---

### 6. Monitor Queue Worker

```bash
# View worker logs
tail -f /path/to/your/api/storage/logs/queue-worker.log

# Check queue status
php artisan queue:work --once --queue=media

# Check failed jobs
php artisan queue:failed

# Restart worker after code changes
sudo supervisorctl restart laravel-queue-worker:*
```

---

## 🧪 Testing After Setup

### Test 1: Check Storage Configuration

```bash
php artisan tinker
```

```php
// Check which disk is being used
config('media-library.disk_name');
// Should return: "dashboard_storage"

// Check disk path
config('filesystems.disks.dashboard_storage.root');
// Should return your dashboard storage path

// Test file write
Storage::disk('dashboard_storage')->put('test.txt', 'Hello World');

// Check if file exists
Storage::disk('dashboard_storage')->exists('test.txt');
// Should return: true

// Clean up
Storage::disk('dashboard_storage')->delete('test.txt');

exit
```

### Test 2: Upload Product Image via API

```bash
# Using curl or Postman
POST https://your-api-domain.com/api/admin/products/{id}
Authorization: Bearer YOUR_TOKEN
Content-Type: multipart/form-data

# Body:
gallery[0]: [image file]
```

### Test 3: Check Queue Processing

```bash
# Check pending jobs
php artisan tinker
DB::table('jobs')->count();
// Should decrease as worker processes jobs

# Check if conversions were generated
$media = \Spatie\MediaLibrary\MediaCollections\Models\Media::latest()->first();
$media->generated_conversions;
// Should show: ["thumb" => true, "medium" => true, "large" => true]

exit
```

### Test 4: Access Image URL

Visit in browser:
```
https://dash.samsmy.cutscal.com/storage/[media-id]/conversions/[filename]-thumb.jpg
```

---

## 🚨 Troubleshooting

### Images Still Not Saving

1. **Check PHP Memory Limit:**
   ```bash
   php -i | grep memory_limit
   # Should be at least 256M, recommend 512M
   ```

   Update `/etc/php/8.1/fpm/php.ini`:
   ```ini
   memory_limit = 512M
   ```

   Restart PHP-FPM:
   ```bash
   sudo systemctl restart php8.1-fpm
   ```

2. **Check GD Extension:**
   ```bash
   php -m | grep gd
   # Should show: gd
   ```

   If missing:
   ```bash
   sudo apt install php8.1-gd
   sudo systemctl restart php8.1-fpm
   ```

3. **Check Directory Permissions:**
   ```bash
   # Dashboard storage should be writable by web server
   ls -la /var/www/dash.samsmy.cutscal.com/storage
   # Owner should be: www-data:www-data
   # Permissions should be: drwxrwxr-x (775)
   ```

4. **Check Laravel Logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

5. **Check Queue Failed Jobs:**
   ```bash
   php artisan queue:failed
   
   # If jobs are failing, check the exception
   php artisan tinker
   DB::table('failed_jobs')->latest()->first()->exception;
   
   # Retry after fixing
   php artisan queue:retry all
   ```

### Queue Worker Keeps Stopping

1. **Check Supervisor Status:**
   ```bash
   sudo supervisorctl status
   # Should show: RUNNING
   ```

2. **Check Worker Logs:**
   ```bash
   tail -f storage/logs/queue-worker.log
   ```

3. **Increase Timeout:**
   Edit supervisor config and increase `stopwaitsecs` to 3600 (1 hour)

### Permission Denied Errors

```bash
# Fix API storage permissions
cd /path/to/your/api
sudo chown -R www-data:www-data storage
sudo chmod -R 775 storage

# Fix dashboard storage permissions
cd /var/www/dash.samsmy.cutscal.com
sudo chown -R www-data:www-data storage
sudo chmod -R 775 storage
```

---

## 📊 Production Checklist

Before marking as complete, verify:

- [ ] `.env` has `MEDIA_DISK=dashboard_storage`
- [ ] `.env` has correct `DASHBOARD_STORAGE_PATH` (absolute path)
- [ ] `.env` has correct `DASHBOARD_STORAGE_URL` (HTTPS URL)
- [ ] Redis is installed and running
- [ ] Supervisor is configured and running 2 queue workers
- [ ] Dashboard storage directory exists and is writable (775)
- [ ] `storage/media-library/temp` exists and is writable
- [ ] PHP memory limit is 512M or higher
- [ ] PHP GD extension is installed
- [ ] Configuration cache cleared (`php artisan config:clear`)
- [ ] Test image upload succeeds
- [ ] Image conversions are generated (thumb, medium, large)
- [ ] Image URLs are publicly accessible via browser

---

## 🔄 Deployment Workflow

**Every time you deploy code changes:**

```bash
# 1. Update code
git pull origin main

# 2. Install dependencies
composer install --no-dev --optimize-autoloader

# 3. Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# 4. Run migrations (if any)
php artisan migrate --force

# 5. Recache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Restart queue workers
sudo supervisorctl restart laravel-queue-worker:*

# 7. Restart PHP-FPM (if needed)
sudo systemctl restart php8.1-fpm
```

---

## 📞 Quick Commands Reference

```bash
# Check queue status
php artisan queue:work --once
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Restart workers
sudo supervisorctl restart laravel-queue-worker:*

# Check worker status
sudo supervisorctl status

# View logs
tail -f storage/logs/laravel.log
tail -f storage/logs/queue-worker.log

# Clear caches
php artisan config:clear && php artisan config:cache

# Test storage
php artisan tinker
Storage::disk('dashboard_storage')->put('test.txt', 'test');
Storage::disk('dashboard_storage')->exists('test.txt');
```

---

## 🎯 Summary

The main issue is that production needs:
1. ✅ **MEDIA_DISK** set to `dashboard_storage` in `.env`
2. ✅ **Queue worker** running with `--queue=default,media`
3. ✅ **512M memory limit** for PHP and queue worker
4. ✅ **Proper permissions** on storage directories (775, www-data)

Follow the steps above and your images will upload correctly in production! 🚀
