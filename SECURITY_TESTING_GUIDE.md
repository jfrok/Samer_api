# Security Testing Guide

## Quick Security Test Checklist

This guide helps you verify that all security measures are working correctly.

---

## 1. Test Rate Limiting

### Test Login Rate Limit (5 attempts per minute):

**Using curl:**
```bash
# Attempt 6 login requests rapidly
for i in {1..6}; do
  curl -X POST http://localhost:8000/api/login \
    -H "Content-Type: application/json" \
    -d '{"email":"test@test.com","password":"wrong"}' \
    -w "\nRequest $i: HTTP %{http_code}\n"
  sleep 1
done
```

**Expected Result:**
- First 5 requests: Should return 422 (Validation Error) or 200 (if credentials correct)
- 6th request: Should return **429 Too Many Requests**

**Check in logs:**
```bash
tail -f storage/logs/laravel.log | grep "Failed login attempt"
```

You should see failed login attempts logged with IP address.

---

## 2. Test Security Headers

**Using curl:**
```bash
curl -I http://localhost:8000/api/products
```

**Expected Headers:**
```
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: geolocation=(), microphone=(), camera=()
Content-Type: application/json
```

**In Production (with HTTPS):**
```
Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
```

---

## 3. Test File Upload Security

### Test 1: Upload Legitimate Image
```bash
curl -X POST http://localhost:8000/api/admin/products \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "name=Test Product" \
  -F "category_id=1" \
  -F "base_price=100" \
  -F "gallery[]=@/path/to/valid-image.jpg"
```

**Expected Result:** ✅ 201 Created

### Test 2: Upload PHP File Disguised as Image
Create test file: `shell.php.jpg`
```php
<?php echo "This should be rejected"; ?>
```

```bash
curl -X POST http://localhost:8000/api/admin/products \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "name=Test Product" \
  -F "category_id=1" \
  -F "base_price=100" \
  -F "gallery[]=@shell.php.jpg"
```

**Expected Result:** ❌ 422 Unprocessable Entity
**Error Message:** "The gallery.0 contains suspicious content."

### Test 3: Upload Oversized File (>5MB)
```bash
# Create 6MB test file
dd if=/dev/zero of=large.jpg bs=1M count=6

curl -X POST http://localhost:8000/api/admin/products \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "name=Test Product" \
  -F "category_id=1" \
  -F "base_price=100" \
  -F "gallery[]=@large.jpg"
```

**Expected Result:** ❌ 422 Unprocessable Entity
**Error Message:** "The gallery.0 must not be larger than 5120KB."

### Test 4: Upload File with Wrong MIME Type
Rename a text file to `.jpg`:
```bash
echo "This is not an image" > fake.jpg

curl -X POST http://localhost:8000/api/admin/products \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "name=Test Product" \
  -F "category_id=1" \
  -F "base_price=100" \
  -F "gallery[]=@fake.jpg"
```

**Expected Result:** ❌ 422 Unprocessable Entity
**Error Message:** "The gallery.0 has an invalid file type."

---

## 4. Test CORS Protection

### Test from Unauthorized Origin:

**Browser Console (from http://unauthorized-site.com):**
```javascript
fetch('http://localhost:8000/api/products', {
  method: 'GET',
  credentials: 'include'
})
.then(response => console.log(response))
.catch(error => console.error(error));
```

**Expected Result:** ❌ CORS error in browser console:
```
Access to fetch at 'http://localhost:8000/api/products' from origin 'http://unauthorized-site.com' 
has been blocked by CORS policy
```

### Test from Authorized Origin:

**Browser Console (from http://localhost:3000 or configured origin):**
```javascript
fetch('http://localhost:8000/api/products', {
  method: 'GET',
  credentials: 'include'
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error(error));
```

**Expected Result:** ✅ Success, data returned

---

## 5. Test Token Expiration

### Create Token and Wait:
```bash
# Login to get token
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@samsshop.com","password":"Sam@12345"}'

# Save the token
TOKEN="your_token_here"

# Use token immediately (should work)
curl -X GET http://localhost:8000/api/admin/products \
  -H "Authorization: Bearer $TOKEN"

# Result: ✅ 200 OK, products returned
```

**Test after expiration (if SANCTUM_EXPIRATION=1 for testing):**
```bash
# Wait 2 minutes then try again
sleep 120

curl -X GET http://localhost:8000/api/admin/products \
  -H "Authorization: Bearer $TOKEN"

# Result: ❌ 401 Unauthorized
```

**To test quickly, temporarily set in .env:**
```env
SANCTUM_EXPIRATION=1  # 1 minute
```

---

## 6. Test SQL Injection Prevention

### Test Search with SQL Injection:
```bash
curl -X GET "http://localhost:8000/api/products?search=' OR '1'='1" \
  -H "Accept: application/json"
```

**Expected Result:** 
- ✅ Returns sanitized empty results or filtered products
- ❌ Does NOT return all products
- No SQL error

### Test with UNION Attack:
```bash
curl -X GET "http://localhost:8000/api/products?search=test' UNION SELECT * FROM users--" \
  -H "Accept: application/json"
```

**Expected Result:** 
- ✅ Returns sanitized results
- Does NOT expose user data

---

## 7. Test XSS Prevention

### Test Product Name with Script Tag:
```bash
curl -X POST http://localhost:8000/api/admin/products \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "<script>alert(\"XSS\")</script>Product",
    "category_id": 1,
    "base_price": 100
  }'
```

**Expected Result:** 
- ✅ Product created but `<script>` tags removed or escaped
- When fetching product, name should be: "Product" or "alert(\"XSS\")Product"

**Verify:**
```bash
curl -X GET http://localhost:8000/api/products/{id} \
  -H "Authorization: Bearer YOUR_TOKEN"
```

Response should NOT contain executable `<script>` tags.

---

## 8. Test Authentication Logging

### Generate Failed Login Attempts:
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@test.com","password":"wrongpassword"}'
```

**Check logs:**
```bash
tail -20 storage/logs/laravel.log
```

**Expected Log Entry:**
```
[YYYY-MM-DD HH:MM:SS] local.WARNING: Failed login attempt {"email":"test@test.com","ip":"127.0.0.1","user_agent":"curl/7.x.x","timestamp":"2026-03-23 12:34:56"}
```

### Generate Successful Login:
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@samsshop.com","password":"Sam@12345"}'
```

**Expected Log Entry:**
```
[YYYY-MM-DD HH:MM:SS] local.INFO: User logged in {"user_id":1,"email":"admin@samsshop.com","ip":"127.0.0.1","timestamp":"2026-03-23 12:34:56"}
```

---

## 9. Test HTTPS Enforcement (Production Only)

**Note:** This only works when `APP_ENV=production`

### Test HTTP Redirect:
```bash
curl -I http://api.port.samsmy.com/api/products
```

**Expected Result:**
```
HTTP/1.1 301 Moved Permanently
Location: https://api.port.samsmy.com/api/products
```

### Test HSTS Header:
```bash
curl -I https://api.port.samsmy.com/api/products
```

**Expected Header:**
```
Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
```

---

## 10. Automated Security Scan

### Using OWASP ZAP:
```bash
# Install ZAP
docker pull owasp/zap2docker-stable

# Run baseline scan
docker run -t owasp/zap2docker-stable zap-baseline.py \
  -t http://localhost:8000/api \
  -r zap-report.html
```

### Using Nikto:
```bash
# Install nikto
sudo apt install nikto

# Scan API
nikto -h http://localhost:8000
```

### Check for Known Vulnerabilities:
```bash
# In project directory
composer audit

# Expected output:
# No security vulnerability advisories found
```

---

## Security Test Results Template

Use this template to document your test results:

```markdown
# Security Test Results - [Date]

## Environment
- Environment: [Development/Staging/Production]
- URL: [http://localhost:8000 or production URL]
- Laravel Version: 10.10
- PHP Version: 8.1+

## Test Results

| Test | Status | Notes |
|------|--------|-------|
| Rate Limiting (5/min) | ✅ Pass | Returns 429 on 6th request |
| Security Headers | ✅ Pass | All headers present |
| File Upload - Valid Image | ✅ Pass | Accepted |
| File Upload - PHP File | ✅ Pass | Rejected with error |
| File Upload - Oversized | ✅ Pass | Rejected with error |
| File Upload - Wrong MIME | ✅ Pass | Rejected with error |
| CORS - Unauthorized Origin | ✅ Pass | Blocked |
| CORS - Authorized Origin | ✅ Pass | Allowed |
| Token Expiration | ✅ Pass | Expires after configured time |
| SQL Injection | ✅ Pass | Input sanitized |
| XSS Prevention | ✅ Pass | Scripts removed |
| Failed Login Logging | ✅ Pass | Logged with IP |
| Successful Login Logging | ✅ Pass | Logged with IP |
| HTTPS Enforcement (Production) | ⏸️ Pending | Will test in production |
| HSTS Header (Production) | ⏸️ Pending | Will test in production |

## Vulnerabilities Found
[None / List any issues found]

## Recommendations
[Any additional security improvements needed]

## Next Review Date
[Date for next security audit]

## Tester
[Your Name]
```

---

## Continuous Security Monitoring

### Set Up Daily Log Monitoring:

Create a script: `monitor-security.sh`
```bash
#!/bin/bash

# Count failed login attempts today
FAILED_LOGINS=$(grep "Failed login attempt" storage/logs/laravel-$(date +%Y-%m-%d).log | wc -l)

# Count unauthorized admin attempts today
UNAUTHORIZED=$(grep "Unauthorized admin access" storage/logs/laravel-$(date +%Y-%m-%d).log | wc -l)

echo "Security Report - $(date)"
echo "Failed Login Attempts: $FAILED_LOGINS"
echo "Unauthorized Admin Access: $UNAUTHORIZED"

# Alert if suspicious activity
if [ $FAILED_LOGINS -gt 50 ]; then
    echo "⚠️  HIGH: Excessive failed login attempts!"
    # Send email alert here
fi

if [ $UNAUTHORIZED -gt 5 ]; then
    echo "⚠️  CRITICAL: Multiple unauthorized admin access attempts!"
    # Send email alert here
fi
```

**Run with cron:**
```bash
# Add to crontab
0 9 * * * /path/to/monitor-security.sh | mail -s "Daily Security Report" admin@yourdomain.com
```

---

## Emergency Response

If tests reveal a vulnerability:

1. **Immediately:** Take affected endpoint offline if critical
2. **Within 1 hour:** Implement fix
3. **Within 2 hours:** Re-run security tests
4. **Within 24 hours:** Review all related code for similar issues
5. **Document:** Update security documentation with findings

---

## Additional Testing Resources

- [Postman Security Testing Collection](https://www.postman.com/api-security-testing/)
- [OWASP ZAP Docker](https://hub.docker.com/r/owasp/zap2docker-stable/)
- [Burp Suite Community](https://portswigger.net/burp/communitydownload)
- [Laravel Security Best Practices](https://github.com/Shpionus/laravel-security-checklist)

---

**Remember:** Security is an ongoing process. Run these tests:
- After any security-related code changes
- Before each production deployment
- Monthly as part of regular maintenance
- After framework or dependency updates
