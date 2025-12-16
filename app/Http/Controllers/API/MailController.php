<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\EmailNotificationService;

class MailController extends Controller
{
    protected $emailService;

    public function __construct(EmailNotificationService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Send a test email using Mailgun
     */
    public function sendTestEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'message' => 'nullable|string|max:500',
            'language' => 'nullable|string|in:en,ar'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $email = $request->input('email');
        $message = $request->input('message');
        $language = $request->input('language', 'ar');

        $result = $this->emailService->sendTestEmail($email, $message, $language);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Get mail configuration info with validation
     */
    public function getMailConfig()
    {
        $configValidation = $this->emailService->validateConfiguration();

        return response()->json([
            'mail_configuration' => $configValidation,
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'system_info' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'server_time' => now()->format('F j, Y \a\t g:i:s A T'),
                'timezone' => config('app.timezone')
            ]
        ]);
    }

    /**
     * Send welcome email to new user
     */
    public function sendWelcomeEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'name' => 'required|string|max:255',
            'verification_url' => 'nullable|url',
            'language' => 'nullable|string|in:en,ar'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $email = $request->input('email');
        $name = $request->input('name');
        $verificationUrl = $request->input('verification_url');
        $language = $request->input('language', 'ar');

        $result = $this->emailService->sendWelcomeEmail($email, $name, $verificationUrl, $language);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Send password reset email manually
     */
    public function sendPasswordResetEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'reset_url' => 'required|url',
            'token' => 'required|string|min:10',
            'user_name' => 'nullable|string|max:255',
            'language' => 'nullable|string|in:en,ar'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $email = $request->input('email');
        $resetUrl = $request->input('reset_url');
        $token = $request->input('token');
        $userName = $request->input('user_name');
        $language = $request->input('language', 'ar');

        // Find user by email if name not provided
        if (!$userName) {
            $user = \App\Models\User::where('email', $email)->first();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found with this email address'
                ], 404);
            }

            $userName = $user->name;
        }

        $userData = [
            'name' => $userName,
            'email' => $email
        ];

        $result = $this->emailService->sendPasswordResetEmail($email, $resetUrl, $token, $userData, $language);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Send a generic notification email
     */
    public function sendNotification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'additional_data' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $email = $request->input('email');
        $subject = $request->input('subject');
        $message = $request->input('message');
        $additionalData = $request->input('additional_data', []);

        $result = $this->emailService->sendNotificationEmail($email, $subject, $message, $additionalData);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Get email system health and statistics
     */
    public function getSystemHealth()
    {
        try {
            $configValidation = $this->emailService->validateConfiguration();

            // Additional health checks
            $healthChecks = [
                'mailgun_connectivity' => $this->checkMailgunConnectivity(),
                'email_queue_status' => $this->checkEmailQueueStatus(),
                'template_availability' => $this->checkEmailTemplates(),
                'configuration_security' => $this->checkConfigurationSecurity()
            ];

            return response()->json([
                'system_health' => [
                    'overall_status' => $this->calculateOverallHealth($healthChecks, $configValidation),
                    'configuration' => $configValidation,
                    'health_checks' => $healthChecks,
                    'last_checked' => now()->format('Y-m-d H:i:s'),
                    'uptime_info' => [
                        'php_uptime' => $this->getPHPUptime(),
                        'system_load' => sys_getloadavg() ?? 'Not available'
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get system health: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Private helper methods
     */
    private function checkMailgunConnectivity(): array
    {
        try {
            $domain = config('services.mailgun.domain');
            $secret = config('services.mailgun.secret');

            if (empty($domain) || empty($secret)) {
                return [
                    'status' => 'error',
                    'message' => 'Mailgun credentials not configured'
                ];
            }

            return [
                'status' => 'ok',
                'message' => 'Mailgun credentials configured',
                'domain' => $domain,
                'using_sandbox' => strpos($domain, 'sandbox') !== false
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Mailgun connectivity check failed: ' . $e->getMessage()
            ];
        }
    }

    private function checkEmailQueueStatus(): array
    {
        try {
            $queueConnection = config('queue.default');

            return [
                'status' => 'ok',
                'connection' => $queueConnection,
                'message' => $queueConnection === 'sync' ? 'Synchronous sending (no queue)' : 'Queue configured'
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Queue check failed: ' . $e->getMessage()
            ];
        }
    }

    private function checkEmailTemplates(): array
    {
        $templates = [
            'test' => 'emails.test',
            'welcome' => 'emails.welcome',
            'password-reset' => 'emails.password-reset',
            'email-layout' => 'layouts.email'
        ];

        $available = [];
        $missing = [];

        foreach ($templates as $name => $path) {
            if (view()->exists($path)) {
                $available[] = $name;
            } else {
                $missing[] = $name;
            }
        }

        return [
            'status' => empty($missing) ? 'ok' : 'warning',
            'available_templates' => $available,
            'missing_templates' => $missing,
            'total_templates' => count($available)
        ];
    }

    private function checkConfigurationSecurity(): array
    {
        $issues = [];
        $score = 100;

        // Check for placeholder values
        if (strpos(config('services.mailgun.domain', ''), 'your-mailgun-domain') !== false) {
            $issues[] = 'Using placeholder Mailgun domain';
            $score -= 30;
        }

        if (strpos(config('services.mailgun.secret', ''), 'your-mailgun-api-key') !== false) {
            $issues[] = 'Using placeholder Mailgun API key';
            $score -= 30;
        }

        // Check production settings
        if (config('app.env') === 'production') {
            if (config('app.debug')) {
                $issues[] = 'Debug mode enabled in production';
                $score -= 20;
            }

            if (strpos(config('services.mailgun.domain', ''), 'sandbox') !== false) {
                $issues[] = 'Using sandbox domain in production';
                $score -= 15;
            }
        }

        return [
            'status' => $score >= 80 ? 'ok' : ($score >= 60 ? 'warning' : 'error'),
            'security_score' => $score,
            'issues' => $issues,
            'recommendations' => empty($issues) ? ['Security configuration looks good!'] : $issues
        ];
    }

    private function calculateOverallHealth(array $healthChecks, array $configValidation): string
    {
        $scores = [];

        foreach ($healthChecks as $check) {
            if ($check['status'] === 'ok') {
                $scores[] = 100;
            } elseif ($check['status'] === 'warning') {
                $scores[] = 70;
            } else {
                $scores[] = 30;
            }
        }

        $scores[] = $configValidation['configuration_score'];

        $average = array_sum($scores) / count($scores);

        if ($average >= 85) return 'excellent';
        if ($average >= 70) return 'good';
        if ($average >= 50) return 'fair';
        return 'poor';
    }

    private function getPHPUptime(): string
    {
        $uptime = time() - $_SERVER['REQUEST_TIME_FLOAT'];
        return gmdate('H:i:s', $uptime) . ' (current request)';
