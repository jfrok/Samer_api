#!/usr/bin/env php
<?php
/**
 * Quick test to demonstrate Arabic as default language
 */

echo "🇸🇦 Testing Arabic as Default Language\n";
echo str_repeat("=", 50) . "\n";

$baseUrl = 'http://localhost:8000/api';

// Test without language parameter (should default to Arabic)
$testPayload = [
    'email' => 'test@example.com',
    'name' => 'المستخدم التجريبي'  // Arabic name
];

echo "📧 Sending email WITHOUT language parameter...\n";
echo "Expected: Should default to Arabic (ar)\n\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $baseUrl . '/mail/welcome',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($testPayload)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response) {
    $data = json_decode($response, true);

    if ($httpCode === 200 && isset($data['success']) && $data['success']) {
        echo "✅ Success! Email sent\n";
        if (isset($data['language'])) {
            echo "📝 Default Language Used: " . $data['language'] . "\n";
            if ($data['language'] === 'ar') {
                echo "🎉 CONFIRMED: Arabic is now the standard/default language!\n";
            } else {
                echo "⚠️  Warning: Expected 'ar' but got '{$data['language']}'\n";
            }
        }
    } else {
        echo "❌ Failed to send email\n";
        echo "Response: " . $response . "\n";
    }
} else {
    echo "❌ No response from server\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "🔍 To test manually:\n";
echo "curl -X POST $baseUrl/mail/test \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -d '{\"email\":\"test@example.com\"}'\n\n";
echo "This should now send email in Arabic by default!\n";
