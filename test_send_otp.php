<?php

echo "Testing send-otp endpoint...\n";

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => json_encode([
            'email' => 'test-simple-' . time() . '@example.com',
            'language' => 'ar'
        ])
    ]
]);

$response = file_get_contents('http://127.0.0.1:8000/api/send-otp', false, $context);

if ($response === false) {
    echo "Failed to connect to server\n";
    exit(1);
}

echo "Response: " . $response . "\n";

$data = json_decode($response, true);
if (isset($data['success']) && $data['success'] === true) {
    echo "Send OTP endpoint working!\n";
} else {
    echo "Send OTP endpoint failed\n";
}
