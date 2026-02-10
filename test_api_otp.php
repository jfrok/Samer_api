<?php

echo "Testing OTP API endpoints...\n";

// Test send-otp endpoint
echo "1. Testing send-otp endpoint...\n";

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => json_encode([
            'email' => 'test-api-otp-' . time() . '@example.com',
            'language' => 'ar'
        ])
    ]
]);

$response = file_get_contents('http://127.0.0.1:8000/api/send-otp', false, $context);

if ($response === false) {
    echo "Failed to connect to server\n";
    exit(1);
}

echo "Send OTP Response: " . $response . "\n";

$data = json_decode($response, true);
if (isset($data['otp_code'])) {
    $otpCode = $data['otp_code'];

    // Test verify-otp-register endpoint
    echo "2. Testing verify-otp-register endpoint...\n";

    $context2 = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode([
                'email' => $data['email'],
                'otp_code' => $otpCode,
                'name' => 'Test User',
                'password' => 'password123',
                'password_confirmation' => 'password123'
            ])
        ]
    ]);

    $response2 = file_get_contents('http://127.0.0.1:8000/api/verify-otp-register', false, $context2);

    if ($response2 === false) {
        echo "Failed to connect to server for verification\n";
        exit(1);
    }

    echo "Verify OTP Register Response: " . $response2 . "\n";

    $data2 = json_decode($response2, true);
    if (isset($data2['user']) && isset($data2['token'])) {
        echo "OTP system API endpoints working correctly!\n";
        echo "User created successfully with token\n";
    } else {
        echo "Verify OTP endpoint failed - no user/token returned\n";
    }
} else {
    echo "Send OTP endpoint didn't return otp_code\n";
}
