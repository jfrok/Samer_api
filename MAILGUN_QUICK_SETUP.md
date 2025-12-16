# 🔧 Quick Mailgun Setup Guide

## Step 1: Get Your Mailgun Credentials

### A. Sign up for Mailgun (Free Account Available)
1. Go to https://www.mailgun.com/
2. Click "Sign Up" and create a free account
3. Verify your email address

### B. Get Your API Key
1. Login to your Mailgun dashboard
2. Go to **Settings → API Keys**
3. Copy your **"Private API key"** (starts with `key-...`)
   - Example: `key-1234567890abcdef1234567890abcdef`

### C. Get Your Domain
**Option 1: Use Sandbox Domain (for testing)**
1. Go to **Sending → Domains** 
2. You'll see a sandbox domain like: `sandboxXXXXXXXX.mailgun.org`
3. Copy this domain name

**Option 2: Add Your Own Domain (for production)**
1. Click "Add New Domain"
2. Enter your domain (e.g., `mydomain.com`)
3. Follow DNS setup instructions
4. Wait for verification

## Step 2: Update Your .env File

Replace these values in your `.env` file:

```env
# For Sandbox Domain (Testing)
MAILGUN_DOMAIN=sandbox12345678.mailgun.org
MAILGUN_SECRET=key-1234567890abcdef1234567890abcdef

MAIL_USERNAME=postmaster@sandbox12345678.mailgun.org  
MAIL_FROM_ADDRESS="test@sandbox12345678.mailgun.org"

# OR for Custom Domain (Production)
MAILGUN_DOMAIN=yourdomain.com
MAILGUN_SECRET=key-1234567890abcdef1234567890abcdef

MAIL_USERNAME=postmaster@yourdomain.com
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
```

## Step 3: Test Your Setup

### Quick Test via API:
```bash
# After updating your .env file, test with:
curl http://localhost:8000/api/test-email/youremail@gmail.com
```

### Using Sandbox Domain:
- You can only send emails to **authorized recipients**
- Go to **Sending → Domains → [Your Sandbox]** 
- Add your email to "Authorized Recipients"

## 🚨 Important Notes:

1. **Sandbox Limitations**: 
   - Can only send to authorized recipients
   - Limited to 300 emails/day
   
2. **Custom Domain**: 
   - Need DNS configuration
   - Can send to any email after verification
   
3. **API vs SMTP**: 
   - Your setup uses both API (for sending) and SMTP credentials
   - API key is your main credential

## 🔍 Common Issues:

- **401 Error**: Wrong API key or domain
- **403 Error**: Domain not verified or recipient not authorized (sandbox)
- **Rate Limit**: Free account has sending limits

## ✅ Once configured, your endpoints will work:
- `GET /api/test-email/{email}`
- `POST /api/mail/test`
- `GET /api/mail/config`

---
**Need help?** Check your Mailgun dashboard logs for detailed error messages.
