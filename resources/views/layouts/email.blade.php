<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>@yield('title', config('app.name'))</title>

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
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        /* Header */
        .header {
            background: @yield('header-bg', 'linear-gradient(135deg, #f5411c 0%, #ff6b47 100%)');
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header:before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 20px 20px;
            animation: float 20s infinite linear;
        }

        @keyframes float {
            0% { transform: translateX(0px) translateY(0px); }
            100% { transform: translateX(-20px) translateY(-20px); }
        }

        .logo {
            color: #ffffff;
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
            position: relative;
            z-index: 1;
        }

        .header-subtitle {
            color: #e6f3ff;
            font-size: 16px;
            font-weight: 300;
            position: relative;
            z-index: 1;
        }

        /* Content */
        .content {
            padding: 40px 30px;
        }

        /* Common components */
        .button {
            display: inline-block;
            padding: 16px 32px;
            background: @yield('button-bg', 'linear-gradient(135deg, #f5411c 0%, #ff6b47 100%)');
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

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .info-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.1);
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

        .security-badge {
            background: #e6fffa;
            border-left: 4px solid #38b2ac;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }

        .security-badge h3 {
            color: #234e52;
            font-size: 18px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }

        /* Footer */
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

        .footer-links {
            margin: 20px 0;
        }

        .footer-links a {
            color: #63b3ed;
            text-decoration: none;
            margin: 0 15px;
            font-size: 14px;
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
            .alert {
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

        /* Security indicators */
        .security-level-high {
            background: linear-gradient(135deg, #f0fff4 0%, #c6f6d5 100%);
            border: 2px solid #9ae6b4;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .security-level-high h4 {
            color: #22543d;
            margin-bottom: 10px;
        }

        /* Custom styles for specific email types */
        @yield('custom-styles')
    </style>
</head>

<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="logo">@yield('header-icon', '📧') {{ config('app.name') }}</div>
            <div class="header-subtitle">@yield('header-subtitle', 'Professional Email Service')</div>
        </div>

        <!-- Content -->
        <div class="content">
            @yield('content')
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-brand">{{ config('app.name') }}</div>
            <div style="margin: 15px 0; color: #718096;">@yield('footer-tagline', 'Secure. Reliable. Trusted.')</div>

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

            <p style="font-size: 12px; margin-top: 20px; color: #718096; line-height: 1.5;">
                @yield('footer-text', 'This is an automated email from ' . config('app.name') . '.')<br>
                For security reasons, please do not forward this email to anyone.<br><br>
                © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.<br>
                @yield('footer-additional', 'This message was sent to a verified email address.')
            </p>
        </div>
    </div>
</body>
</html>
