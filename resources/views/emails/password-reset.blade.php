<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>Password Reset - {{ config('app.name') }}</title>

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
            color: #e2e8f0;
            font-size: 16px;
            font-weight: 300;
        }

        /* Content */
        .content {
            padding: 40px 30px;
        }

        .security-badge {
            background: #fff5f5;
            border-left: 4px solid #f5411c;
            padding: 20px;
            margin: 25px 0;
            border-radius: 8px;
        }

        .security-badge h3 {
            color: #822727;
            font-size: 18px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .security-icon {
            width: 24px;
            height: 24px;
            margin-right: 10px;
            fill: #38b2ac;
        }

        .alert {
            background: #fef5e7;
            border: 1px solid #f6ad55;
            border-left: 4px solid #ed8936;
            color: #744210;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
        }

        .alert-title {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 8px;
            color: #c05621;
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

        .security-checklist {
            background: #f0fff4;
            border: 1px solid #9ae6b4;
            border-radius: 8px;
            padding: 25px;
            margin: 25px 0;
        }

        .security-checklist h4 {
            color: #22543d;
            font-size: 18px;
            margin-bottom: 15px;
        }

        .security-checklist ul {
            list-style: none;
            padding: 0;
        }

        .security-checklist li {
            color: #2f855a;
            margin: 10px 0;
            padding-left: 25px;
            position: relative;
        }

        .security-checklist li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #38a169;
            font-weight: bold;
        }

        .token-display {
            background: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 14px;
            text-align: center;
            margin: 20px 0;
            letter-spacing: 1px;
        }

        .footer {
            background: #2d3748;
            color: #a0aec0;
            padding: 30px;
            text-align: center;
            font-size: 14px;
        }

        .footer-links {
            margin: 20px 0;
        }

        .footer-links a {
            color: #63b3ed;
            text-decoration: none;
            margin: 0 15px;
        }

        .social-links {
            margin: 20px 0;
        }

        .social-links a {
            display: inline-block;
            margin: 0 10px;
            width: 32px;
            height: 32px;
            background: #4a5568;
            border-radius: 50%;
            text-align: center;
            line-height: 32px;
            color: #a0aec0;
            text-decoration: none;
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

            .button {
                width: 100%;
                display: block;
                text-align: center;
                padding: 18px 20px;
            }

            .info-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .security-badge,
            .alert,
            .security-checklist {
                margin: 20px 0;
                padding: 15px;
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

            .info-card {
                background: #2d3748;
                border-color: #4a5568;
            }

            .info-card h4 {
                color: #f7fafc;
            }
        }
    </style>
</head>

<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="logo">🛡️ {{ config('app.name') }}</div>
            <div class="header-subtitle">Secure Password Recovery</div>
        </div>

        <!-- Content -->
        <div class="content">
            <h1 style="color: #2d3748; font-size: 24px; margin-bottom: 20px;">Password Reset Request</h1>

            <p style="font-size: 16px; color: #4a5568; margin-bottom: 25px;">
                Hello <strong>{{ $user['name'] ?? 'User' }}</strong>,
            </p>

            <p style="font-size: 16px; color: #4a5568; margin-bottom: 25px;">
                We received a request to reset the password for your {{ config('app.name') }} account. If you didn't make this request, please ignore this email and your password will remain unchanged.
            </p>

            <!-- Security Alert -->
            <div class="alert">
                <div class="alert-title">🔒 Security Notice</div>
                <p>This is an automated security email. If you didn't request a password reset, someone may be trying to access your account. Please secure your account immediately.</p>
            </div>

            <!-- Reset Button -->
            <div style="text-align: center; margin: 40px 0;">
                <a href="{{ $resetUrl }}" class="button">Reset Your Password</a>
            </div>

            <!-- Alternative Link -->
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 25px 0;">
                <p style="font-size: 14px; color: #718096; margin-bottom: 10px;">
                    <strong>Button not working?</strong> Copy and paste this link into your browser:
                </p>
                <p style="word-break: break-all; font-size: 14px; color: #3182ce;">
                    {{ $resetUrl }}
                </p>
            </div>

            <!-- Security Information -->
            <div class="security-badge">
                <h3>
                    <svg class="security-icon" viewBox="0 0 24 24">
                        <path d="M12,1L3,5V11C3,16.55 6.84,21.74 12,23C17.16,21.74 21,16.55 21,11V5L12,1M12,7C13.4,7 14.8,8.6 14.8,10V11H16V18H8V11H9.2V10C9.2,8.6 10.6,7 12,7M12,8.2C11.2,8.2 10.4,8.7 10.4,10V11H13.6V10C13.6,8.7 12.8,8.2 12,8.2Z" />
                    </svg>
                    Security Information
                </h3>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <li style="margin: 8px 0; color: #2c7a7b;">✓ This link expires in <strong>60 minutes</strong> for your security</li>
                    <li style="margin: 8px 0; color: #2c7a7b;">✓ The link can only be used once</li>
                    <li style="margin: 8px 0; color: #2c7a7b;">✓ Your account remains secure until you take action</li>
                </ul>
            </div>

            <!-- Account Information -->
            <div class="info-grid">
                <div class="info-card">
                    <h4>Account Email</h4>
                    <p>{{ $user['email'] }}</p>
                </div>
                <div class="info-card">
                    <h4>Request Time</h4>
                    <p>{{ now()->format('F j, Y \a\t g:i A') }}</p>
                </div>
                <div class="info-card">
                    <h4>IP Address</h4>
                    <p>{{ request()->ip() ?? 'Not available' }}</p>
                </div>
                <div class="info-card">
                    <h4>Expires At</h4>
                    <p>{{ now()->addMinutes(60)->format('F j, Y \a\t g:i A') }}</p>
                </div>
            </div>

            <!-- Token Reference -->
            <div style="margin: 30px 0;">
                <p style="font-size: 14px; color: #718096; margin-bottom: 10px;">
                    <strong>Reset Token Reference:</strong>
                </p>
                <div class="token-display">
                    {{ substr($token, 0, 8) }}...{{ substr($token, -8) }}
                </div>
                <p style="font-size: 12px; color: #a0aec0; text-align: center; margin-top: 10px;">
                    Keep this reference for your records
                </p>
            </div>

            <!-- Security Checklist -->
            <div class="security-checklist">
                <h4>🛡️ Security Best Practices</h4>
                <ul>
                    <li>Never share your password with anyone</li>
                    <li>Use a strong, unique password for your account</li>
                    <li>Enable two-factor authentication when available</li>
                    <li>Log out from shared or public computers</li>
                    <li>Contact support if you notice suspicious activity</li>
                </ul>
            </div>

            <!-- Help Section -->
            <div style="background: #f0f4f8; padding: 25px; border-radius: 8px; margin: 30px 0;">
                <h4 style="color: #2d3748; font-size: 18px; margin-bottom: 15px;">Need Help?</h4>
                <p style="color: #4a5568; margin: 0;">
                    If you're having trouble accessing your account or didn't request this reset,
                    please contact our support team immediately at
                    <a href="mailto:support@{{ request()->getHost() }}" style="color: #3182ce;">
                        support@{{ request()->getHost() }}
                    </a>
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p style="margin-bottom: 20px;">
                <strong>{{ config('app.name') }}</strong><br>
                Secure. Reliable. Trusted.
            </p>

            <div class="footer-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Support</a>
                <a href="#">Contact Us</a>
            </div>

            <div class="social-links">
                <a href="#" title="Facebook">f</a>
                <a href="#" title="Twitter">t</a>
                <a href="#" title="Instagram">i</a>
                <a href="#" title="LinkedIn">in</a>
            </div>

            <p style="font-size: 12px; margin-top: 20px; color: #718096;">
                This email was sent to {{ $user['email'] }} because a password reset was requested.<br>
                For security reasons, please do not forward this email to anyone.<br><br>
                © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.<br>
                This is an automated message, please do not reply to this email.
            </p>
        </div>
</body>
</html>
