<?php

return [
    // Common email elements
    'app_name' => 'Samer Shop',
    'powered_by' => 'Powered by Mailgun Email Service',
    'all_rights_reserved' => 'All rights reserved',
    'contact_us' => 'Contact us if you have any questions',
    'unsubscribe' => 'Unsubscribe from these emails',
    'privacy_policy' => 'Privacy Policy',
    'terms_of_service' => 'Terms of Service',

    // Test Email
    'test' => [
        'subject' => 'Test Email from :app_name',
        'title' => 'Email System Test',
        'subtitle' => 'Professional Email Testing Platform',
        'greeting' => 'Hello!',
        'message' => 'This is a test email to verify that your professional email system is working correctly.',
        'test_successful' => 'Email System Test Successful',
        'test_description' => 'Your Mailgun integration is working perfectly with professional HTML templates.',
        'configuration' => 'Email Configuration',
        'mailer' => 'Email Service',
        'domain' => 'Domain',
        'encryption' => 'Security',
        'status' => 'Status',
        'active' => 'Active',
        'features_included' => 'Features Included',
        'professional_templates' => 'Professional HTML Email Templates',
        'mobile_responsive' => 'Mobile-Responsive Design',
        'mailgun_integration' => 'Mailgun Integration Active',
        'security_headers' => 'Security Headers Included',
        'cross_client' => 'Cross-Client Compatibility',
        'ready_for_production' => 'Ready for Production',
        'ready_description' => 'Your email system is now fully configured and ready to send welcome emails, password resets, notifications, and more!',
        'automated_message' => 'This is an automated test email from your email system.',
    ],

    // Welcome Email
    'welcome' => [
        'subject' => 'Welcome to :app_name',
        'title' => 'Welcome to :app_name',
        'subtitle' => 'Your Premium Shopping Experience Begins Here',
        'greeting' => 'Welcome, :name!',
        'thank_you' => 'Thank you for joining :app_name. We\'re thrilled to have you as part of our community.',
        'account_ready' => 'Your account is ready, and you can now explore our features.',
        'get_started' => 'Get Started',
        'verify_account' => 'Verify Your Account',
        'features_title' => 'What you can do with :app_name',
        'feature_shop' => [
            'title' => 'Premium Shopping',
            'description' => 'Browse our curated collection of premium products with exclusive deals.'
        ],
        'feature_secure' => [
            'title' => 'Secure Payments',
            'description' => 'Shop with confidence using our encrypted and secure payment system.'
        ],
        'feature_support' => [
            'title' => '24/7 Support',
            'description' => 'Get help anytime with our dedicated customer support team.'
        ],
        'feature_shipping' => [
            'title' => 'Fast Shipping',
            'description' => 'Enjoy quick and reliable shipping to your doorstep.'
        ],
        'next_steps' => 'Next Steps',
        'step_verify' => 'Verify your email address',
        'step_profile' => 'Complete your profile',
        'step_browse' => 'Start browsing products',
        'step_enjoy' => 'Enjoy your shopping experience',
        'need_help' => 'Need Help?',
        'help_description' => 'Our support team is here to help you get started.',
        'contact_support' => 'Contact Support',
        'social_follow' => 'Follow us on social media for the latest updates and exclusive offers.',
    ],

    // Password Reset Email
    'reset' => [
        'subject' => 'Reset Your :app_name Password',
        'title' => 'Password Reset Request',
        'subtitle' => 'Secure Account Recovery',
        'greeting' => 'Hello :name,',
        'request_received' => 'We received a request to reset your password for your :app_name account.',
        'reset_button' => 'Reset Password',
        'expire_notice' => 'This password reset link will expire in :minutes minutes for your security.',
        'no_action' => 'If you did not request this password reset, please ignore this email. Your password will remain unchanged.',
        'security_notice' => 'Security Notice',
        'security_tips' => [
            'Never share your password reset link with anyone',
            'This link will expire automatically for your security',
            'If you suspect unauthorized access, contact support immediately',
            'Always use a strong, unique password for your account'
        ],
        'additional_security' => 'Additional Security Information',
        'ip_address' => 'Request from IP',
        'timestamp' => 'Time',
        'browser_info' => 'Browser',
        'contact_support' => 'Contact Support',
        'if_not_you' => 'If this wasn\'t you, please contact our support team immediately.',
        'reset_steps' => 'Password Reset Steps',
        'step_click' => 'Click the "Reset Password" button above',
        'step_enter' => 'Enter your new password',
        'step_confirm' => 'Confirm your new password',
        'step_save' => 'Save your changes',
        'password_tips' => 'Password Security Tips',
        'tip_length' => 'Use at least 8 characters',
        'tip_mix' => 'Include uppercase, lowercase, numbers, and symbols',
        'tip_unique' => 'Don\'t reuse passwords from other accounts',
        'tip_avoid' => 'Avoid personal information like names or birthdays',
    ],

    // Email Layout
    'layout' => [
        'view_web' => 'View this email in your browser',
        'footer_text' => 'You are receiving this email because you have an account with :app_name.',
        'copyright' => '© :year :app_name. All rights reserved.',
        'address' => '123 Business Street, City, Country',
        'phone' => '+1 (555) 123-4567',
        'email' => 'support@samershop.com',
    ]
];
