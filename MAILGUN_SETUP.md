# Mailgun Configuration Guide for Laravel API

## 1. Mailgun Setup

### Get Mailgun Credentials:
1. Sign up at https://www.mailgun.com/
2. Verify your domain or use the sandbox domain for testing
3. Get your API Key from the dashboard
4. Get your domain name

### Configure Environment Variables:
Update your `.env` file with your actual Mailgun credentials:

```env
# Mail Configuration
MAIL_MAILER=mailgun
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=postmaster@your-mailgun-domain.com
MAIL_PASSWORD=your-mailgun-smtp-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@your-domain.com"
MAIL_FROM_NAME="Your App Name"

# Mailgun Configuration
MAILGUN_DOMAIN=your-mailgun-domain.com
MAILGUN_SECRET=your-mailgun-api-key
MAILGUN_ENDPOINT=api.mailgun.net
```

## 2. Testing the Integration

### Test Email via API:
```bash
# Simple test via GET request
GET /api/test-email/youremail@example.com

# Advanced test via POST request
POST /api/mail/test
{
    "email": "youremail@example.com",
    "message": "Custom test message"
}

# Check configuration
GET /api/mail/config

# Send welcome email
POST /api/mail/welcome
{
    "email": "user@example.com",
    "name": "John Doe"
}
```

## 3. Available API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/test-email/{email}` | Quick test email |
| POST | `/api/mail/test` | Send test email with custom message |
| GET | `/api/mail/config` | Get current mail configuration |
| POST | `/api/mail/welcome` | Send welcome email to new users |

## 4. Integration in Your Code

### Sending Emails in Controllers:
```php
use Illuminate\Support\Facades\Mail;
use App\Mail\TestMail;

// Simple email
Mail::to('user@example.com')->send(new TestMail(['message' => 'Hello!']));

// With error handling
try {
    Mail::to($user->email)->send(new WelcomeMail($user));
    return response()->json(['status' => 'Email sent successfully']);
} catch (\Exception $e) {
    return response()->json(['error' => 'Failed to send email'], 500);
}
```

## 5. Next Steps

1. Create additional Mailable classes for different email types:
   - WelcomeMail
   - PasswordResetMail
   - OrderConfirmationMail
   - NotificationMail

2. Set up email queues for better performance:
   ```php
   Mail::to($user->email)->queue(new WelcomeMail($user));
   ```

3. Configure email templates in `resources/views/emails/`

4. Add email logging and tracking

## 6. Troubleshooting

- Check Laravel logs in `storage/logs/laravel.log`
- Verify Mailgun dashboard for sent emails
- Ensure domain is properly verified in Mailgun
- Check firewall settings for SMTP ports
- Test with sandbox domain first before using custom domain

## 7. Security Notes

- Never commit actual credentials to version control
- Use different Mailgun accounts for development and production
- Implement rate limiting on email endpoints
- Validate email addresses before sending
- Consider implementing email verification for user registration
