<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Suspicious patterns to monitor (not block)
     */
    private array $suspiciousPatterns = [
        'union.*select',
        'drop.*table',
        'insert.*into',
        'delete.*from',
        '<script',
        'javascript:',
        'onerror=',
        'onload=',
        '\.\./\.\.',  // Path traversal
        '/etc/passwd',
        'cmd\.exe',
        'base64_decode',
        'eval\(',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Monitor for suspicious patterns - log but don't block
        // Eloquent's parameterized queries already prevent actual injection
        $this->monitorSuspiciousInput($request);

        $response = $next($request);

        // Prevent MIME-type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Prevent clickjacking attacks
        $response->headers->set('X-Frame-Options', 'DENY');

        // Enable XSS protection (legacy browsers)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Control referrer information
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Restrict feature permissions
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        // Enforce HTTPS in production (HSTS)
        if (config('app.env') === 'production') {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // Content-Type for API responses
        if ($request->is('api/*')) {
            $response->headers->set('Content-Type', 'application/json', true);
        }

        return $response;
    }

    /**
     * Monitor input for suspicious patterns without blocking
     *
     * @param Request $request
     * @return void
     */
    private function monitorSuspiciousInput(Request $request): void
    {
        $inputs = array_merge(
            $request->query->all(),
            $request->request->all()
        );

        foreach ($inputs as $key => $value) {
            if (!is_string($value)) {
                continue;
            }

            $lowerValue = strtolower($value);

            foreach ($this->suspiciousPatterns as $pattern) {
                if (preg_match('/' . $pattern . '/i', $lowerValue)) {
                    // Instead of blocking on keywords, just log and let Eloquent handle it
                    // Eloquent's parameterized queries already prevent actual injection
                    Log::critical('Suspicious input pattern detected', [
                        'ip' => $request->ip(),
                        'url' => $request->fullUrl(),
                        'method' => $request->method(),
                        'user_agent' => $request->userAgent(),
                        'parameter' => $key,
                        'pattern_matched' => $pattern,
                        'value_sample' => substr($value, 0, 100), // First 100 chars only
                        'user_id' => $request->user()?->id,
                    ]);

                    // Don't abort — just monitor
                    // Let the request continue and trust Eloquent's security
                    break; // Log once per parameter
                }
            }
        }
    }
}
