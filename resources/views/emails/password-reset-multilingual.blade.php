<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ __('emails.reset.subject', ['app_name' => __('emails.app_name')]) }}</title>

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

        .security-badge {
            background: #fff5f5;
            border-left: 4px solid #f5411c;
            padding: 20px;
            margin: 25px 0;
            border-radius: 8px;
        }

        [dir="rtl"] .security-badge {
            border-left: none;
            border-right: 4px solid #f5411c;
        }

        .security-badge h3 {
            color: #822727;
            font-size: 18px;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .security-badge p {
            color: #c53030;
            font-size: 14px;
            margin: 8px 0;
        }

        .button {
            display: inline-block;
            padding: 16px 32px;
            background: linear-gradient(135deg, #f5411c 0%, #ff6b47 100%);
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 8px;
            margin: 25px 0;
            font-weight: 600;
            font-size: 16px;
            text-align: center;
            box-shadow: 0 4px 14px rgba(245, 65, 28, 0.4);
            transition: all 0.3s ease;
            min-width: 200px;
        }

        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 65, 28, 0.6);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .info-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .info-card h4 {
            color: #2d3748;
            font-size: 16px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .info-card p {
            color: #718096;
            font-size: 14px;
            margin: 0;
        }

        .security-tips {
            background: #f0fff4;
            border: 1px solid #9ae6b4;
            border-radius: 8px;
            padding: 25px;
            margin: 25px 0;
        }

        .security-tips h4 {
            color: #22543d;
            font-size: 18px;
            margin-bottom: 15px;
        }

        .security-tips ul {
            list-style: none;
            padding: 0;
        }

        .security-tips li {
            color: #2f855a;
            margin: 10px 0;
            padding-left: 25px;
            position: relative;
        }

        [dir="rtl"] .security-tips li {
            padding-left: 0;
            padding-right: 25px;
        }

        .security-tips li:before {
            content: "🔒";
            position: absolute;
            left: 0;
            top: 0;
        }

        [dir="rtl"] .security-tips li:before {
            left: auto;
            right: 0;
        }

        .steps-section {
            background: linear-gradient(135deg, #e6f3ff 0%, #cce7ff 100%);
            border: 2px solid #90cdf4;
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
        }

        .steps-section h4 {
            color: #2b6cb0;
            font-size: 18px;
            margin-bottom: 20px;
            text-align: center;
        }

        .step-list {
            list-style: none;
            padding: 0;
        }

        .step-item {
            display: flex;
            align-items: center;
            color: #2c5282;
            margin: 12px 0;
            gap: 15px;
            font-size: 16px;
        }

        [dir="rtl"] .step-item {
            flex-direction: row-reverse;
        }

        .step-number {
            background: #f5411c;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: bold;
            flex-shrink: 0;
        }

        .expire-notice {
            background: #fffbeb;
            border: 1px solid #f59e0b;
            border-radius: 8px;
            padding: 15px;
            margin: 25px 0;
            text-align: center;
        }

        .expire-notice p {
            color: #92400e;
            font-size: 14px;
            margin: 0;
            font-weight: 600;
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

            .greeting {
                font-size: 20px;
            }

            .info-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="logo">{{ __('emails.app_name') }}</div>
            <div class="header-subtitle">{{ __('emails.reset.subtitle') }}</div>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">{{ __('emails.reset.greeting', ['name' => $user['name']]) }}</div>

            <div class="message">
                {{ __('emails.reset.request_received', ['app_name' => __('emails.app_name')]) }}
            </div>

            <!-- Security Notice -->
            <div class="security-badge">
                <h3>🔒 {{ __('emails.reset.security_notice') }}</h3>
                <p>{{ __('emails.reset.expire_notice', ['minutes' => 60]) }}</p>
            </div>

            <!-- Reset Button -->
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ $resetUrl }}" class="button">{{ __('emails.reset.reset_button') }}</a>
            </div>

            <!-- Expiry Warning -->
            <div class="expire-notice">
                <p>⏰ {{ __('emails.reset.expire_notice', ['minutes' => 60]) }}</p>
            </div>

            <!-- Password Reset Steps -->
            <div class="steps-section">
                <h4>{{ __('emails.reset.reset_steps') }}</h4>
                <ul class="step-list">
                    <li class="step-item">
                        <span class="step-number">1</span>
                        <span>{{ __('emails.reset.step_click') }}</span>
                    </li>
                    <li class="step-item">
                        <span class="step-number">2</span>
                        <span>{{ __('emails.reset.step_enter') }}</span>
                    </li>
                    <li class="step-item">
                        <span class="step-number">3</span>
                        <span>{{ __('emails.reset.step_confirm') }}</span>
                    </li>
                    <li class="step-item">
                        <span class="step-number">4</span>
                        <span>{{ __('emails.reset.step_save') }}</span>
                    </li>
                </ul>
            </div>

            <!-- Security Tips -->
            <div class="security-tips">
                <h4>{{ __('emails.reset.password_tips') }}</h4>
                <ul>
                    <li>{{ __('emails.reset.tip_length') }}</li>
                    <li>{{ __('emails.reset.tip_mix') }}</li>
                    <li>{{ __('emails.reset.tip_unique') }}</li>
                    <li>{{ __('emails.reset.tip_avoid') }}</li>
                </ul>
            </div>

            <!-- Additional Security Information -->
            <h4 style="color: #2d3748; font-size: 18px; margin: 30px 0 15px 0;">{{ __('emails.reset.additional_security') }}</h4>
            <div class="info-grid">
                <div class="info-card">
                    <h4>{{ __('emails.reset.ip_address') }}</h4>
                    <p>{{ request()->ip() ?? 'Unknown' }}</p>
                </div>
                <div class="info-card">
                    <h4>{{ __('emails.reset.timestamp') }}</h4>
                    <p>{{ now()->format('Y-m-d H:i:s') }}</p>
                </div>
                <div class="info-card">
                    <h4>{{ __('emails.reset.browser_info') }}</h4>
                    <p>{{ request()->userAgent() ? substr(request()->userAgent(), 0, 30) . '...' : 'Unknown' }}</p>
                </div>
            </div>

            <!-- If not you section -->
            <div style="background: #fef5e7; border: 1px solid #f6ad55; border-radius: 8px; padding: 20px; margin: 25px 0; text-align: center;">
                <p style="color: #c05621; margin-bottom: 15px; font-weight: 600;">
                    {{ __('emails.reset.if_not_you') }}
                </p>
                <a href="mailto:{{ __('emails.layout.email') }}" style="color: #c05621; text-decoration: none; font-weight: 600;">
                    {{ __('emails.reset.contact_support') }}
                </a>
            </div>

            <!-- No Action Required -->
            <div style="background: #f8f9fa; border-radius: 8px; padding: 20px; margin: 25px 0;">
                <p style="color: #718096; margin: 0; font-size: 14px; text-align: center;">
                    {{ __('emails.reset.no_action') }}
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-brand">{{ __('emails.app_name') }}</div>
            <p style="margin: 15px 0;">{{ __('emails.powered_by') }}</p>
            <p style="font-size: 12px; color: #718096; margin-top: 20px;">
                {{ __('emails.layout.footer_text', ['app_name' => __('emails.app_name')]) }}<br>
                {{ __('emails.layout.copyright', ['year' => date('Y'), 'app_name' => __('emails.app_name')]) }}
            </p>
        </div>
    </div>
</body>
</html>
