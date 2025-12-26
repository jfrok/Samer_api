<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ __('emails.test.subject', ['app_name' => config('app.name')]) }}</title>

    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: {{ app()->getLocale() == 'ar' ? "'Arial', 'Tahoma', sans-serif" : "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif" }};
            line-height: 1.6;
            color: #2d3748;
            background-color: #f7fafc;
            margin: 0;
            padding: 0;
            width: 100% !important;
            direction: {{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }};
            text-align: {{ app()->getLocale() == 'ar' ? 'right' : 'left' }};
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #f5411c 0%, #ff6b47 100%);
            padding: 40px 30px;
            text-align: center;
            color: #ffffff;
        }

        .logo {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header-subtitle {
            font-size: 16px;
            font-weight: 300;
            opacity: 0.9;
        }

        /* Content */
        .content {
            padding: 40px 30px;
        }

        .greeting {
            font-size: 24px;
            color: #2d3748;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .message {
            font-size: 16px;
            line-height: 1.6;
            color: #4a5568;
            margin-bottom: 30px;
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
        }

        .config-card p {
            color: #718096;
            font-size: 14px;
        }

        .features-list {
            background: linear-gradient(135deg, #f0fff4 0%, #c6f6d5 100%);
            border: 2px solid #9ae6b4;
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
        }

        .features-list h4 {
            color: #22543d;
            font-size: 18px;
            margin-bottom: 15px;
            text-align: center;
        }

        .feature-item {
            display: flex;
            align-items: center;
            color: #2f855a;
            margin: 12px 0;
            gap: 10px;
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

        /* RTL specific adjustments */
        [dir="rtl"] .config-card {
            border-left: none;
            border-right: 4px solid #f5411c;
        }

        [dir="rtl"] .feature-item {
            flex-direction: row-reverse;
        }

        /* Mobile responsiveness */
        @media screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
                margin: 0 !important;
                border-radius: 0;
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
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="logo">{{ __('emails.app_name') }}</div>
            <div class="header-subtitle">{{ __('emails.test.subtitle') }}</div>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">{{ __('emails.test.greeting') }}</div>

            <div class="message">
                {{ $data['message'] ?? __('emails.test.message') }}
            <div class="test-badge">
                <h3>{{ __('emails.test.test_successful') }}</h3>
                <p>{{ __('emails.test.test_description') }}</p>
            </div>

            <!-- Configuration Info -->
            <h3 style="color: #2d3748; margin: 30px 0 20px 0; font-size: 20px;">{{ __('emails.test.configuration') }}</h3>

            <div class="config-grid">
                <div class="config-card">
                    <h4>{{ __('emails.test.mailer') }}</h4>
                    <p>Mailgun</p>
                </div>
                <div class="config-card">
                    <h4>{{ __('emails.test.domain') }}</h4>
                    <p>{{ config('services.mailgun.domain') ?: 'Not configured' }}</p>
                </div>
                <div class="config-card">
                    <h4>{{ __('emails.test.encryption') }}</h4>
                    <p>TLS/SSL</p>
                </div>
                <div class="config-card">
                    <h4>{{ __('emails.test.status') }}</h4>
                    <p style="color: #22543d; font-weight: 600;">{{ __('emails.test.active') }}</p>
                </div>
            </div>

            <!-- Features List -->
            <div class="features-list">
                <h4>{{ __('emails.test.features_included') }}</h4>
                <div class="feature-item">
                    <span style="font-size: 18px;">✅</span>
                    <span>{{ __('emails.test.professional_templates') }}</span>
                </div>
                <div class="feature-item">
                    <span style="font-size: 18px;">✅</span>
                    <span>{{ __('emails.test.mobile_responsive') }}</span>
                </div>
                <div class="feature-item">
                    <span style="font-size: 18px;">✅</span>
                    <span>{{ __('emails.test.mailgun_integration') }}</span>
                </div>
                <div class="feature-item">
                    <span style="font-size: 18px;">✅</span>
                    <span>{{ __('emails.test.security_headers') }}</span>
                </div>
                <div class="feature-item">
                    <span style="font-size: 18px;">✅</span>
                    <span>{{ __('emails.test.cross_client') }}</span>
                </div>
            </div>

            <!-- Ready for Production -->
            <div style="background: linear-gradient(135deg, #e6f3ff 0%, #cce7ff 100%); border: 2px solid #90cdf4; border-radius: 12px; padding: 25px; margin: 30px 0; text-align: center;">
                <h4 style="color: #2b6cb0; font-size: 18px; margin-bottom: 15px;">🚀 {{ __('emails.test.ready_for_production') }}</h4>
                <p style="color: #2c5282; margin: 0;">
                    {{ __('emails.test.ready_description') }}
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-brand">{{ __('emails.app_name') }}</div>
            <p style="margin: 15px 0;">{{ __('emails.powered_by') }}</p>
            <p style="font-size: 12px; color: #718096; margin-top: 20px;">
                {{ __('emails.test.automated_message') }}<br>
                {{ __('emails.layout.copyright', ['year' => date('Y'), 'app_name' => __('emails.app_name')]) }}
            </p>
        </div>
    </div>
</body>
</html>
