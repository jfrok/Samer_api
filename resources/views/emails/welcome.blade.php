<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>Welcome to {{ config('app.name') }}</title>

    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->

    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body, table, td, p, a, li, blockquote {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        table, td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }

        img {
            -ms-interpolation-mode: bicubic;
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
        }

        /* Main styles */
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
            background: linear-gradient(135deg, #f5411c 0%, #ff6b47 50%, #ff8566 100%);
            padding: 40px 30px;
            text-align: center;
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
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="white" opacity="0.1"/><circle cx="80" cy="30" r="1.5" fill="white" opacity="0.1"/><circle cx="40" cy="60" r="1" fill="white" opacity="0.1"/><circle cx="90" cy="80" r="2" fill="white" opacity="0.1"/><circle cx="10" cy="70" r="1.5" fill="white" opacity="0.1"/></svg>');
            background-size: 100px 100px;
            animation: float 20s infinite linear;
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            100% { transform: translateY(-100px); }
        }

        .logo {
            color: #ffffff;
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
            position: relative;
            z-index: 1;
        }

        .header-subtitle {
            color: #e6f3ff;
            font-size: 18px;
            font-weight: 300;
            position: relative;
            z-index: 1;
        }

        /* Content */
        .content {
            padding: 40px 30px;
        }

        .welcome-message {
            text-align: center;
            padding: 30px 0;
        }

        .welcome-title {
            font-size: 28px;
            color: #2d3748;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .welcome-emoji {
            font-size: 48px;
            margin-bottom: 20px;
            display: block;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin: 40px 0;
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
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #f5411c;
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

        .next-steps {
            background: linear-gradient(135deg, #e6fffa 0%, #b2f5ea 100%);
            border: 1px solid #81e6d9;
            border-radius: 12px;
            padding: 30px;
            margin: 30px 0;
            text-align: center;
        }

        .next-steps h3 {
            color: #234e52;
            font-size: 22px;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .step {
            background: white;
            margin: 15px 0;
            padding: 15px 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .step-number {
            background: #4299e1;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .step-text {
            color: #2d3748;
            font-size: 16px;
            flex-grow: 1;
            text-align: left;
        }

        .footer {
            background: #2d3748;
            color: #a0aec0;
            padding: 40px 30px;
            text-align: center;
        }

        .footer-brand {
            color: #ffffff;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .footer-tagline {
            color: #a0aec0;
            font-size: 16px;
            margin-bottom: 30px;
            font-style: italic;
        }

        .footer-links {
            margin: 25px 0;
        }

        .footer-links a {
            color: #63b3ed;
            text-decoration: none;
            margin: 0 15px;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: #90cdf4;
        }

        .social-links {
            margin: 25px 0;
        }

        .social-links a {
            display: inline-block;
            margin: 0 10px;
            width: 40px;
            height: 40px;
            background: #4a5568;
            border-radius: 50%;
            text-align: center;
            line-height: 40px;
            color: #a0aec0;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            background: #63b3ed;
            color: #ffffff;
            transform: translateY(-2px);
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
                font-size: 28px;
            }

            .welcome-title {
                font-size: 24px;
            }

            .button {
                width: 100%;
                display: block;
                text-align: center;
                padding: 18px 20px;
                min-width: auto;
            }

            .feature-grid,
            .info-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .feature-card,
            .next-steps {
                margin: 20px 0;
                padding: 20px;
            }

            .step {
                flex-direction: column;
                text-align: center;
                padding: 20px;
            }

            .step-number {
                margin: 0 0 10px 0;
            }

            .step-text {
                text-align: center;
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .email-container {
                background-color: #1a202c;
            }

            .content {
                color: #e2e8f0;
            }

            .feature-card,
            .info-card {
                background: #2d3748;
                border-color: #4a5568;
            }

            .feature-title,
            .info-card h4,
            .welcome-title {
                color: #f7fafc;
            }
        }
    </style>
</head>

<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="logo">🎉 {{ config('app.name') }}</div>
            <div class="header-subtitle">Welcome to the Future of Shopping</div>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="welcome-message">
                <span class="welcome-emoji">🚀</span>
                <h1 class="welcome-title">Welcome aboard, {{ $user['name'] ?? 'Friend' }}!</h1>
                <p style="font-size: 18px; color: #4a5568; margin-bottom: 30px;">
                    Your journey with {{ config('app.name') }} begins now. We're thrilled to have you join our community of smart shoppers!
                </p>
            </div>

            <!-- Account Information -->
            <div class="info-grid">
                <div class="info-card">
                    <h4>📧 Account Email</h4>
                    <p>{{ $user['email'] }}</p>
                </div>
                <div class="info-card">
                    <h4>📅 Joined Date</h4>
                    <p>{{ now()->format('F j, Y') }}</p>
                </div>
                <div class="info-card">
                    <h4>🆔 Member ID</h4>
                    <p>#{{ str_pad(crc32($user['email']), 8, '0', STR_PAD_LEFT) }}</p>
                </div>
                <div class="info-card">
                    <h4>🎯 Account Status</h4>
                    <p style="color: #38a169; font-weight: 600;">Active & Verified</p>
                </div>
            </div>

            @if($verificationUrl)
            <!-- Email Verification -->
            <div style="background: linear-gradient(135deg, #fef5e7 0%, #fed7aa 100%); border: 2px solid #f6ad55; border-radius: 12px; padding: 30px; margin: 30px 0; text-align: center;">
                <h3 style="color: #c05621; font-size: 20px; margin-bottom: 15px;">🔐 Verify Your Email</h3>
                <p style="color: #9c4221; margin-bottom: 25px;">
                    To secure your account and unlock all features, please verify your email address.
                </p>
                <a href="{{ $verificationUrl }}" class="button" style="background: linear-gradient(135deg, #f6ad55 0%, #ed8936 100%); box-shadow: 0 4px 14px rgba(237, 137, 54, 0.4);">
                    Verify Email Address
                </a>
            </div>
            @endif

            <!-- Features -->
            <div class="feature-grid">
                <div class="feature-card">
                    <span class="feature-icon">🛍️</span>
                    <h3 class="feature-title">Smart Shopping</h3>
                    <p class="feature-description">
                        Discover curated products, exclusive deals, and personalized recommendations just for you.
                    </p>
                </div>

                <div class="feature-card">
                    <span class="feature-icon">⚡</span>
                    <h3 class="feature-title">Fast Delivery</h3>
                    <p class="feature-description">
                        Get your orders delivered quickly with our express shipping options and real-time tracking.
                    </p>
                </div>

                <div class="feature-card">
                    <span class="feature-icon">🔒</span>
                    <h3 class="feature-title">Secure Payments</h3>
                    <p class="feature-description">
                        Shop with confidence using our encrypted payment system and buyer protection guarantee.
                    </p>
                </div>

                <div class="feature-card">
                    <span class="feature-icon">🎁</span>
                    <h3 class="feature-title">Exclusive Rewards</h3>
                    <p class="feature-description">
                        Earn points with every purchase and unlock special discounts, early access, and more.
                    </p>
                </div>
            </div>

            <!-- Next Steps -->
            <div class="next-steps">
                <h3>🎯 Get Started in 3 Simple Steps</h3>

                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-text">Complete your profile and add your delivery preferences</div>
                </div>

                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-text">Browse our featured products and create your first wishlist</div>
                </div>

                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-text">Make your first purchase and start earning reward points</div>
                </div>

                <div style="margin-top: 30px;">
                    <a href="{{ config('app.frontend_url', 'http://localhost:3000') }}" class="button">
                        Start Shopping Now
                    </a>
                </div>
            </div>

            <!-- Security Information -->
            <div style="background: #f0f4f8; padding: 25px; border-radius: 12px; margin: 30px 0; border-left: 4px solid #4299e1;">
                <h4 style="color: #2d3748; font-size: 18px; margin-bottom: 15px; display: flex; align-items: center;">
                    🛡️ Your Security Matters
                </h4>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <li style="margin: 10px 0; color: #4a5568; padding-left: 20px; position: relative;">
                        <span style="position: absolute; left: 0; color: #4299e1;">✓</span>
                        Your account is protected with enterprise-grade security
                    </li>
                    <li style="margin: 10px 0; color: #4a5568; padding-left: 20px; position: relative;">
                        <span style="position: absolute; left: 0; color: #4299e1;">✓</span>
                        We never share your personal information with third parties
                    </li>
                    <li style="margin: 10px 0; color: #4a5568; padding-left: 20px; position: relative;">
                        <span style="position: absolute; left: 0; color: #4299e1;">✓</span>
                        All transactions are encrypted and PCI DSS compliant
                    </li>
                </ul>
            </div>

            <!-- Help Section -->
            <div style="background: linear-gradient(135deg, #f0fff4 0%, #c6f6d5 100%); padding: 25px; border-radius: 12px; margin: 30px 0;">
                <h4 style="color: #22543d; font-size: 18px; margin-bottom: 15px;">💬 Need Help?</h4>
                <p style="color: #2f855a; margin-bottom: 20px;">
                    Our friendly support team is here to help you every step of the way. Don't hesitate to reach out!
                </p>
                <div style="text-align: center;">
                    <a href="mailto:support@{{ request()->getHost() }}" style="color: #2f855a; text-decoration: none; font-weight: 600; margin-right: 20px;">
                        📧 Email Support
                    </a>
                    <a href="#" style="color: #2f855a; text-decoration: none; font-weight: 600; margin-left: 20px;">
                        💭 Live Chat
                    </a>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-brand">{{ config('app.name') }}</div>
            <div class="footer-tagline">Your trusted shopping companion</div>

            <div class="footer-links">
                <a href="#">Shop Now</a>
                <a href="#">About Us</a>
                <a href="#">Contact</a>
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
            </div>

            <div class="social-links">
                <a href="#" title="Facebook">f</a>
                <a href="#" title="Twitter">t</a>
                <a href="#" title="Instagram">i</a>
                <a href="#" title="LinkedIn">in</a>
                <a href="#" title="YouTube">y</a>
            </div>

            <p style="font-size: 12px; margin-top: 25px; color: #718096; line-height: 1.5;">
                This email was sent to {{ $user['email'] }} because you recently created an account.<br>
                You're receiving this email because you signed up for {{ config('app.name') }}.<br><br>
                © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.<br>
                Made with ❤️ for our valued customers
            </p>
        </div>
</body>
</html>
