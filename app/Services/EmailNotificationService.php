<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailNotificationService
{
    /**
     * Send a test email
     */
    public function sendTestEmail(string $email, ?string $message = null, string $language = 'ar'): array
    {
        try {
            $subject = $language === 'ar' ? 'رسالة اختبار من متجر سامر' : 'Test Email from Samer Store';
            $body = $message ?: ($language === 'ar' ?
                'هذه رسالة اختبار للتأكد من عمل نظام البريد الإلكتروني.' :
                'This is a test email to verify the email system is working.');

            Mail::raw($body, function ($mail) use ($email, $subject) {
                $mail->to($email)->subject($subject);
            });

            return [
                'success' => true,
                'message' => $language === 'ar' ? 'تم إرسال الرسالة بنجاح' : 'Email sent successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to send test email: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $language === 'ar' ? 'فشل في إرسال الرسالة' : 'Failed to send email',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate mail configuration
     */
    public function validateConfiguration(): array
    {
        $config = config('mail');
        $issues = [];

        if (empty($config['mailers'][$config['default']]['domain'])) {
            $issues[] = 'Mailgun domain not configured';
        }

        if (empty($config['mailers'][$config['default']]['secret'])) {
            $issues[] = 'Mailgun secret not configured';
        }

        if (empty($config['from']['address'])) {
            $issues[] = 'From address not configured';
        }

        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'config' => [
                'driver' => $config['default'],
                'host' => $config['mailers'][$config['default']]['host'] ?? null,
                'port' => $config['mailers'][$config['default']]['port'] ?? null,
                'encryption' => $config['mailers'][$config['default']]['encryption'] ?? null,
                'from_address' => $config['from']['address'] ?? null,
                'from_name' => $config['from']['name'] ?? null,
            ]
        ];
    }

    /**
     * Send welcome email
     */
    public function sendWelcomeEmail(string $email, string $name, string $verificationUrl, string $language = 'ar'): array
    {
        try {
            $subject = $language === 'ar' ? 'مرحباً بك في متجر سامر' : 'Welcome to Samer Store';

            Mail::send([], [], function ($mail) use ($email, $subject, $name, $verificationUrl, $language) {
                $mail->to($email)->subject($subject);

                $body = $language === 'ar' ?
                    "مرحباً {$name},\n\nشكراً لتسجيلك في متجر سامر. يرجى التحقق من بريدك الإلكتروني بالنقر على الرابط التالي:\n{$verificationUrl}\n\nمع خالص التحية,\nفريق متجر سامر" :
                    "Hello {$name},\n\nThank you for registering at Samer Store. Please verify your email by clicking the following link:\n{$verificationUrl}\n\nBest regards,\nSamer Store Team";

                $mail->setBody($body);
            });

            return [
                'success' => true,
                'message' => $language === 'ar' ? 'تم إرسال رسالة الترحيب بنجاح' : 'Welcome email sent successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to send welcome email: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $language === 'ar' ? 'فشل في إرسال رسالة الترحيب' : 'Failed to send welcome email',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail(string $email, string $resetUrl, string $token, array $userData, string $language = 'ar'): array
    {
        try {
            $subject = $language === 'ar' ? 'إعادة تعيين كلمة المرور' : 'Password Reset';

            Mail::send([], [], function ($mail) use ($email, $subject, $resetUrl, $language) {
                $mail->to($email)->subject($subject);

                $body = $language === 'ar' ?
                    "لقد طلبت إعادة تعيين كلمة المرور. يرجى النقر على الرابط التالي لإعادة تعيين كلمة المرور:\n{$resetUrl}\n\nإذا لم تطلب هذا، يرجى تجاهل هذه الرسالة." :
                    "You have requested a password reset. Please click the following link to reset your password:\n{$resetUrl}\n\nIf you did not request this, please ignore this message.";

                $mail->setBody($body);
            });

            return [
                'success' => true,
                'message' => $language === 'ar' ? 'تم إرسال رسالة إعادة تعيين كلمة المرور بنجاح' : 'Password reset email sent successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to send password reset email: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $language === 'ar' ? 'فشل في إرسال رسالة إعادة تعيين كلمة المرور' : 'Failed to send password reset email',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send notification email
     */
    public function sendNotificationEmail(string $email, string $subject, string $message, ?array $additionalData = null): array
    {
        try {
            Mail::raw($message, function ($mail) use ($email, $subject) {
                $mail->to($email)->subject($subject);
            });

            return [
                'success' => true,
                'message' => 'Notification email sent successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to send notification email: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to send notification email',
                'error' => $e->getMessage()
            ];
        }
    }
}