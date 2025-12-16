<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ __('emails.welcome.subject', ['app_name' => __('emails.app_name')]) }}</title>

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
            position: relative;
            overflow: hidden;
        }

        .header:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="white" opacity="0.1"/><circle cx="80" cy="30" r="1.5" fill="white" opacity="0.1"/></svg>');
            background-size: 100px 100px;
        }

        .logo {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
            position: relative;
            z-index: 1;
        }

        .header-subtitle {
            font-size: 16px;
            font-weight: 300;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        /* Content */
        .content {
            padding: 40px 30px;
        }

        .greeting {
            font-size: 28px;
            color: #2d3748;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .welcome-emoji {
            font-size: 48px;
            margin-bottom: 20px;
            display: block;
            text-align: center;
        }

        .intro-text {
            font-size: 16px;
            line-height: 1.6;
            color: #4a5568;
            margin-bottom: 30px;
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

        .features-title {
            font-size: 22px;
            color: #2d3748;
            margin: 40px 0 25px 0;
            font-weight: 700;
            text-align: center;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin: 30px 0;
        }

        .feature-card {
            background: #f8f9fa;
            padding: 25px 20px;
            border-radius: 12px;
            text-align: center;
            border: 2px solid transparent;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .feature-card:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #f5411c, #ff6b47);
        }

        .feature-card:hover {
            border-color: #f5411c;
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(245, 65, 28, 0.1);
        }

        .feature-icon {
            font-size: 32px;
            margin-bottom: 15px;
            display: block;
        }

        .feature-title {
            color: #2d3748;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .feature-description {
            color: #718096;
            font-size: 14px;
            line-height: 1.5;
        }

        .next-steps {
            background: linear-gradient(135deg, #f0fff4 0%, #c6f6d5 100%);
            border: 2px solid #9ae6b4;
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
        }

        .next-steps h4 {
            color: #22543d;
            font-size: 20px;
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
            color: #2f855a;
            margin: 12px 0;
            gap: 15px;
            font-size: 16px;
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
        [dir="rtl"] .step-item {
            flex-direction: row-reverse;
        }

        [dir="rtl"] .feature-card:before {
            background: linear-gradient(270deg, #f5411c, #ff6b47);
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
                font-size: 28px;
            }

            .greeting {
                font-size: 24px;
            }

            .features-grid {
                grid-template-columns: 1fr;
                gap: 20px;
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
            <div class="header-subtitle">{{ __('emails.welcome.subtitle') }}</div>
        </div>

        <!-- Content -->
        <div class="content">
            <span class="welcome-emoji">🎉</span>

            <div class="greeting">{{ __('emails.welcome.greeting', ['name' => $user['name']]) }}</div>

            <div class="intro-text">
                {{ __('emails.welcome.thank_you', ['app_name' => __('emails.app_name')]) }}
                {{ __('emails.welcome.account_ready') }}
            </div>

            @if($verificationUrl ?? false)
            <div style="text-align: center;">
                <a href="{{ $verificationUrl }}" class="button">{{ __('emails.welcome.verify_account') }}</a>
            </div>
            @else
            <div style="text-align: center;">
                <a href="#" class="button">{{ __('emails.welcome.get_started') }}</a>
            </div>
            @endif

            <!-- Features Section -->
            <h3 class="features-title">{{ __('emails.welcome.features_title', ['app_name' => __('emails.app_name')]) }}</h3>

            <div class="features-grid">
                <div class="feature-card">
                    <span class="feature-icon">🛍️</span>
                    <div class="feature-title">{{ __('emails.welcome.feature_shop.title') }}</div>
                    <div class="feature-description">{{ __('emails.welcome.feature_shop.description') }}</div>
                </div>

                <div class="feature-card">
                    <span class="feature-icon">🔐</span>
                    <div class="feature-title">{{ __('emails.welcome.feature_secure.title') }}</div>
                    <div class="feature-description">{{ __('emails.welcome.feature_secure.description') }}</div>
                </div>

                <div class="feature-card">
                    <span class="feature-icon">💬</span>
                    <div class="feature-title">{{ __('emails.welcome.feature_support.title') }}</div>
                    <div class="feature-description">{{ __('emails.welcome.feature_support.description') }}</div>
                </div>

                <div class="feature-card">
                    <span class="feature-icon">🚚</span>
                    <div class="feature-title">{{ __('emails.welcome.feature_shipping.title') }}</div>
                    <div class="feature-description">{{ __('emails.welcome.feature_shipping.description') }}</div>
                </div>
            </div>

            <!-- Next Steps -->
            <div class="next-steps">
                <h4>{{ __('emails.welcome.next_steps') }}</h4>
                <ul class="step-list">
                    <li class="step-item">
                        <span class="step-number">1</span>
                        <span>{{ __('emails.welcome.step_verify') }}</span>
                    </li>
                    <li class="step-item">
                        <span class="step-number">2</span>
                        <span>{{ __('emails.welcome.step_profile') }}</span>
                    </li>
                    <li class="step-item">
                        <span class="step-number">3</span>
                        <span>{{ __('emails.welcome.step_browse') }}</span>
                    </li>
                    <li class="step-item">
                        <span class="step-number">4</span>
                        <span>{{ __('emails.welcome.step_enjoy') }}</span>
                    </li>
                </ul>
            </div>

            <!-- Help Section -->
            <div style="background: linear-gradient(135deg, #e6f3ff 0%, #cce7ff 100%); border: 2px solid #90cdf4; border-radius: 12px; padding: 25px; margin: 30px 0; text-align: center;">
                <h4 style="color: #2b6cb0; font-size: 18px; margin-bottom: 15px;">{{ __('emails.welcome.need_help') }}</h4>
                <p style="color: #2c5282; margin: 0 0 15px 0;">
                    {{ __('emails.welcome.help_description') }}
                </p>
                <a href="mailto:{{ __('emails.layout.email') }}" style="color: #2b6cb0; text-decoration: none; font-weight: 600;">
                    {{ __('emails.welcome.contact_support') }}
                </a>
            </div>

            <!-- Social Follow -->
            <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px; margin: 25px 0;">
                <p style="color: #718096; margin-bottom: 15px;">{{ __('emails.welcome.social_follow') }}</p>
                <div style="display: flex; justify-content: center; gap: 15px;">
                    <a href="#" style="color: #f5411c; text-decoration: none; font-size: 20px;">📘</a>
                    <a href="#" style="color: #f5411c; text-decoration: none; font-size: 20px;">📷</a>
                    <a href="#" style="color: #f5411c; text-decoration: none; font-size: 20px;">🐦</a>
                </div>
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
