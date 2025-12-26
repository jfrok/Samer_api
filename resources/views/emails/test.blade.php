<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Test Email from {{ config('app.name') }}</title>

    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #2d3748;
            background-color: #f7fafc;
            margin: 0;
            padding: 0;
            width: 100% !important;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #f5411c 0%, #ff6b47 100%);
            padding: 40px 30px;
            text-align: center;
        }

        .logo {
            color: #ffffff;
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header-subtitle {
            color: #e6fffa;
            font-size: 16px;
            font-weight: 300;
        }

        /* Content */
        .content {
            padding: 40px 30px;
        }

        .test-badge {
            background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
            border: 2px solid #fc8181;
            border-radius: 12px;
            padding: 25px;
            margin: 25px 0;
            text-align: center;
        }

        .test-badge h3 {
            color: #822727;
            font-size: 20px;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .test-badge p {
            color: #c53030;
            font-size: 16px;
        }

        .config-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .config-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #f5411c;
            transition: all 0.3s ease;
        }

        .config-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(245, 65, 28, 0.1);
        }

        .config-card h4 {
            color: #2d3748;
            font-size: 16px;
            margin-bottom: 8px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        .config-card .icon {
            margin-right: 8px;
            font-size: 18px;
        }

        .config-card p {
            color: #38b2ac;
            font-size: 14px;
            margin: 0;
            font-weight: 500;
        }

        .success-indicator {
            background: #f0fff4;
            border: 2px solid #9ae6b4;
            border-radius: 12px;
            padding: 25px;
            margin: 25px 0;
            text-align: center;
        }

        .success-indicator .check-icon {
            font-size: 48px;
            color: #38a169;
            margin-bottom: 15px;
            display: block;
        }

        .success-indicator h3 {
            color: #22543d;
            font-size: 22px;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .success-indicator p {
            color: #2f855a;
            font-size: 16px;
        }

        .timestamp {
            background: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 14px;
            text-align: center;
            margin: 25px 0;
        }

        .footer {
            background: #2d3748;
            color: #a0aec0;
            padding: 30px;
            text-align: center;
            font-size: 14px;
        }

        .footer-brand {
            color: #ffffff;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        /* Mobile responsiveness */
        @media screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
                margin: 0 !important;
            }

            .header {
                padding: 30px 20px;
            }

            .content {
                padding: 30px 20px;
            }

            .logo {
                font-size: 24px;
            }

            .config-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .test-badge,
            .success-indicator {
                margin: 20px 0;
                padding: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="logo">🧪 {{ config('app.name') }}</div>
            <div class="header-subtitle">Email Testing System</div>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="success-indicator">
                <span class="check-icon">✅</span>
                <h3>Mailgun Integration Successful!</h3>
                <p>Your email system is working perfectly and ready for production use.</p>
            </div>

            <div class="test-badge">
                <h3>🎯 Test Email Delivery</h3>
                <p>This is a test email sent from your Laravel application using Mailgun's professional email service.</p>
            </div>

            @if(isset($data['message']) && $data['message'])
            <div style="background: #ebf4ff; border: 2px solid #90cdf4; border-radius: 12px; padding: 25px; margin: 25px 0;">
                <h4 style="color: #2b6cb0; font-size: 18px; margin-bottom: 10px;">📝 Custom Message</h4>
                <p style="color: #2c5282; font-size: 16px; margin: 0; font-style: italic;">
                    "{{ $data['message'] }}"
                </p>
            </div>
            @endif

            <!-- Configuration Details -->
            <h3 style="color: #2d3748; font-size: 20px; margin: 30px 0 20px 0;">⚙️ Email Configuration</h3>

            <div class="config-grid">
                <div class="config-card">
                    <h4><span class="icon">📧</span>Mailer</h4>
                    <p>{{ config('mail.default') }}</p>
                </div>
                <div class="config-card">
                    <h4><span class="icon">📤</span>From Address</h4>
                    <p>{{ config('mail.from.address') }}</p>
                </div>
                <div class="config-card">
                    <h4><span class="icon">🏷️</span>From Name</h4>
                    <p>{{ config('mail.from.name') }}</p>
                </div>
                <div class="config-card">
                    <h4><span class="icon">🌐</span>Environment</h4>
                    <p>{{ strtoupper(config('app.env')) }}</p>
                </div>
                <div class="config-card">
                    <h4><span class="icon">🔗</span>Domain</h4>
                    <p>{{ config('services.mailgun.domain') ?: 'Not configured' }}</p>
                </div>
                <div class="config-card">
                    <h4><span class="icon">🎯</span>Endpoint</h4>
                    <p>{{ config('services.mailgun.endpoint') ?: 'api.mailgun.net' }}</p>
                </div>
            </div>

            <!-- Timestamp -->
            <div style="margin: 30px 0;">
                <h4 style="color: #2d3748; font-size: 18px; margin-bottom: 15px;">🕒 Delivery Information</h4>
                <div class="timestamp">
                    Sent at: {{ now()->format('F j, Y \a\t g:i:s A') }} ({{ now()->timezoneName }})
                </div>
            </div>

            <!-- Features Test -->
            <div style="background: #f0f4f8; border-radius: 12px; padding: 25px; margin: 25px 0;">
                <h4 style="color: #2d3748; font-size: 18px; margin-bottom: 20px;">✨ Features Verified</h4>

                <div style="display: grid; gap: 15px;">
                    <div style="display: flex; align-items: center; color: #2f855a;">
                        <span style="margin-right: 10px; font-size: 18px;">✅</span>
                        <span>Professional HTML Email Templates</span>
                    </div>
                    <div style="display: flex; align-items: center; color: #2f855a;">
                        <span style="margin-right: 10px; font-size: 18px;">✅</span>
                        <span>Mobile-Responsive Design</span>
                    </div>
                    <div style="display: flex; align-items: center; color: #2f855a;">
                        <span style="margin-right: 10px; font-size: 18px;">✅</span>
                        <span>Mailgun Integration Active</span>
                    </div>
                    <div style="display: flex; align-items: center; color: #2f855a;">
                        <span style="margin-right: 10px; font-size: 18px;">✅</span>
                        <span>Security Headers Included</span>
                    </div>
                    <div style="display: flex; align-items: center; color: #2f855a;">
                        <span style="margin-right: 10px; font-size: 18px;">✅</span>
                        <span>Cross-Client Compatibility</span>
                    </div>
                </div>
            </div>

            <!-- Next Steps -->
            <div style="background: linear-gradient(135deg, #e6f3ff 0%, #cce7ff 100%); border: 2px solid #90cdf4; border-radius: 12px; padding: 25px; margin: 30px 0; text-align: center;">
                <h4 style="color: #2b6cb0; font-size: 18px; margin-bottom: 15px;">🚀 Ready for Production</h4>
                <p style="color: #2c5282; margin: 0;">
                    Your email system is now fully configured and ready to send welcome emails, password resets, notifications, and more!
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-brand">{{ config('app.name') }}</div>
            <p style="margin: 15px 0;">Powered by Mailgun Email Service</p>
            <p style="font-size: 12px; color: #718096; margin-top: 20px;">
                This is an automated test email from your email system.<br>
                © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </p>
        </div>
    </div>
</html>
