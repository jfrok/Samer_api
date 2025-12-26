<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\TestMail;
use App\Mail\WelcomeMail;
use App\Mail\PasswordResetMail;

class EmailNotificationService
{
    /**
     * Send a test email
     */
    public function sendTestEmail(string $email, string $message = null, string $language = 'ar'): array
    {
        try {
            // Set application locale for this email
            $originalLocale = app()->getLocale();
            app()->setLocale($language);

            $data = ['message' => $message ?? __('emails.test.message')];

            Mail::to($email)->send(new TestMail($data));

            Log::info('Test email sent successfully', [
                'recipient' => $email,
                'message' => $data['message'],
                'language' => $language,
                'mailer' => config('mail.default'),
                'timestamp' => now()
            ]);

            // Restore original locale
            app()->setLocale($originalLocale);

            return [
                'success' => true,
                'message' => 'Test email sent successfully',
                'recipient' => $email,
                'language' => $language,
                'mailer' => config('mail.default'),
                'sent_at' => now()->format('Y-m-d H:i:s')
            ];

        } catch (\Exception $e) {
            // Restore original locale in case of error
            if (isset($originalLocale)) {
                app()->setLocale($originalLocale);
            }

            Log::error('Failed to send test email', [
                'recipient' => $email,
                'language' => $language,
                'error' => $e->getMessage(),
                'mailer' => config('mail.default'),
                'timestamp' => now()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send test email: ' . $e->getMessage(),
                'error_code' => $e->getCode(),
                'mailer_config' => $this->getMailerConfig()
            ];
        }
    }

    /**
     * Send a welcome email to new user
     */
    public function sendWelcomeEmail(string $email, string $name, string $verificationUrl = null, string $language = 'ar'): array
    {
        try {
            // Set application locale for this email
            $originalLocale = app()->getLocale();
            app()->setLocale($language);

            $user = [
                'name' => $name,
                'email' => $email
            ];

            Mail::to($email)->send(new WelcomeMail($user, $verificationUrl));

            Log::info('Welcome email sent successfully', [
                'recipient' => $email,
                'user_name' => $name,
                'language' => $language,
                'has_verification' => !is_null($verificationUrl),
                'mailer' => config('mail.default'),
                'timestamp' => now()
            ]);

            // Restore original locale
            app()->setLocale($originalLocale);

            return [
                'success' => true,
                'message' => 'Welcome email sent successfully',
                'recipient' => $email,
                'language' => $language,
                'type' => 'welcome',
                'features' => [
                    'professional_design' => true,
                    'mobile_responsive' => true,
                    'email_verification' => !is_null($verificationUrl),
                    'branded_content' => true
                ]
            ];

        } catch (\Exception $e) {
            // Restore original locale in case of error
            if (isset($originalLocale)) {
                app()->setLocale($originalLocale);
            }

            Log::error('Failed to send welcome email', [
                'recipient' => $email,
                'user_name' => $name,
                'language' => $language,
                'error' => $e->getMessage(),
                'timestamp' => now()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send welcome email: ' . $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
    }

    /**
     * Send a password reset email
     */
    public function sendPasswordResetEmail(string $email, string $resetUrl, string $token, array $userData = [], string $language = 'ar'): array
    {
        try {
            // Set application locale for this email
            $originalLocale = app()->getLocale();
            app()->setLocale($language);

            $user = array_merge([
                'name' => 'User',
                'email' => $email
            ], $userData);

            Mail::to($email)->send(new PasswordResetMail($user, $resetUrl, $token));

            Log::info('Password reset email sent successfully', [
                'recipient' => $email,
                'language' => $language,
                'token_ref' => substr($token, 0, 8) . '...',
                'expires_at' => now()->addMinutes(60)->format('Y-m-d H:i:s'),
                'mailer' => config('mail.default'),
                'timestamp' => now()
            ]);

            // Restore original locale
            app()->setLocale($originalLocale);

            return [
                'success' => true,
                'message' => 'Password reset email sent successfully',
                'recipient' => $email,
                'type' => 'password_reset',
                'security_features' => [
                    'token_expiration' => '60 minutes',
                    'single_use_token' => true,
                    'secure_transport' => true,
                    'ip_tracking' => true,
                    'timestamp_logging' => true
                ],
                'expires_at' => now()->addMinutes(60)->format('Y-m-d H:i:s')
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send password reset email', [
                'recipient' => $email,
                'token_ref' => substr($token, 0, 8) . '...',
                'error' => $e->getMessage(),
                'timestamp' => now()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send password reset email: ' . $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
    }

    /**
     * Get comprehensive mailer configuration info
     */
    public function getMailerConfig(): array
    {
        $mailgunDomain = config('services.mailgun.domain');
        $mailgunSecret = config('services.mailgun.secret');

        return [
            'default_mailer' => config('mail.default'),
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
            'mailgun_domain' => $mailgunDomain,
            'mailgun_endpoint' => config('services.mailgun.endpoint'),
            'mailgun_configured' => !empty($mailgunDomain) && !empty($mailgunSecret),
            'using_sandbox' => strpos($mailgunDomain, 'sandbox') !== false,
            'environment' => config('app.env'),
            'debug_mode' => config('app.debug'),
            'frontend_url' => config('app.frontend_url'),
            'features' => [
                'professional_templates' => true,
                'mobile_responsive' => true,
                'security_headers' => true,
                'branded_design' => true,
                'token_expiration' => config('auth.passwords.users.expire') . ' minutes',
                'rate_limiting' => true,
                'error_logging' => true,
                'dark_mode_support' => true
            ],
            'email_types' => [
                'test' => 'Testing and configuration verification',
                'welcome' => 'New user registration confirmation',
                'password_reset' => 'Secure password recovery',
                'verification' => 'Email address verification',
                'notification' => 'System and user notifications'
            ]
        ];
    }

    /**
     * Send a notification email (generic)
     */
    public function sendNotificationEmail(string $email, string $subject, string $message, array $additionalData = []): array
    {
        try {
            $data = array_merge([
                'message' => $message,
                'subject' => $subject,
                'timestamp' => now()->format('F j, Y \a\t g:i A')
            ], $additionalData);

            Mail::to($email)->send(new TestMail($data));

            Log::info('Notification email sent successfully', [
                'recipient' => $email,
                'subject' => $subject,
                'mailer' => config('mail.default'),
                'timestamp' => now()
            ]);

            return [
                'success' => true,
                'message' => 'Notification email sent successfully',
                'recipient' => $email,
                'subject' => $subject,
                'type' => 'notification'
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send notification email', [
                'recipient' => $email,
                'subject' => $subject,
                'error' => $e->getMessage(),
                'timestamp' => now()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send notification email: ' . $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
    }

    /**
     * Validate email configuration
     */
    public function validateConfiguration(): array
    {
        $config = $this->getMailerConfig();
        $issues = [];
        $score = 100;

        // Check Mailgun configuration
        if (!$config['mailgun_configured']) {
            $issues[] = 'Mailgun credentials not configured';
            $score -= 30;
        }

        // Check if using placeholder values
        if (strpos($config['mailgun_domain'], 'your-mailgun-domain') !== false) {
            $issues[] = 'Using placeholder Mailgun domain';
            $score -= 25;
        }

        if (strpos($config['mailgun_secret'], 'your-mailgun-api-key') !== false) {
            $issues[] = 'Using placeholder Mailgun API key';
            $score -= 25;
        }

        // Check environment
        if ($config['environment'] === 'production' && $config['using_sandbox']) {
            $issues[] = 'Using sandbox domain in production environment';
            $score -= 20;
        }

        return [
            'is_configured' => $config['mailgun_configured'],
            'configuration_score' => max(0, $score),
            'status' => $score >= 80 ? 'excellent' : ($score >= 60 ? 'good' : ($score >= 40 ? 'needs_attention' : 'critical')),
            'issues' => $issues,
            'recommendations' => $this->getRecommendations($config, $issues),
            'config_summary' => $config
        ];
    }

    /**
     * Get configuration recommendations
     */
    private function getRecommendations(array $config, array $issues): array
    {
        $recommendations = [];

        if (in_array('Mailgun credentials not configured', $issues)) {
            $recommendations[] = 'Configure your Mailgun API credentials in the .env file';
        }

        if ($config['using_sandbox']) {
            $recommendations[] = 'For production, configure a verified domain instead of sandbox';
            $recommendations[] = 'Add recipient emails to authorized recipients for sandbox testing';
        }

        if ($config['environment'] === 'production' && $config['debug_mode']) {
            $recommendations[] = 'Disable debug mode in production environment';
        }

        if (empty($recommendations)) {
            $recommendations[] = 'Your email configuration looks great! Ready for production use.';
        }

        return $recommendations;
    }
}
