<?php
/**
 * Profile API Test Script
 *
 * Usage: php test_profile_api.php
 *
 * This script tests the profile API endpoints
 */

$baseUrl = 'http://127.0.0.1:8000/api';
$email = 'test@example.com'; // Change to your test user
$password = 'password123'; // Change to your test password

echo "🧪 Profile API Testing\n";
echo "=====================\n\n";

// Step 1: Login to get token
echo "1️⃣ Logging in...\n";
$loginData = json_encode([
    'email' => $email,
    'password' => $password
]);

$ch = curl_init("$baseUrl/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $loginData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ Login failed (HTTP $httpCode)\n";
    echo "Response: $response\n";
    exit(1);
}

$loginResponse = json_decode($response, true);
$token = $loginResponse['token'] ?? null;

if (!$token) {
    echo "❌ No token received\n";
    exit(1);
}

echo "✅ Login successful\n";
echo "Token: " . substr($token, 0, 20) . "...\n\n";

// Step 2: Get Profile
echo "2️⃣ Getting profile...\n";
$ch = curl_init("$baseUrl/profile");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Bearer ' . $token
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ Get profile failed (HTTP $httpCode)\n";
    echo "Response: $response\n";
    exit(1);
}

$profile = json_decode($response, true);
echo "✅ Profile retrieved\n";
echo "Name: " . ($profile['user']['name'] ?? 'N/A') . "\n";
echo "Email: " . ($profile['user']['email'] ?? 'N/A') . "\n\n";

// Step 3: Get Activity Summary
echo "3️⃣ Getting activity summary...\n";
$ch = curl_init("$baseUrl/profile/activity");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Bearer ' . $token
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ Get activity failed (HTTP $httpCode)\n";
    echo "Response: $response\n";
} else {
    $activity = json_decode($response, true);
    echo "✅ Activity retrieved\n";
    echo "Total Orders: " . ($activity['summary']['total_orders'] ?? 0) . "\n";
    echo "Total Reviews: " . ($activity['summary']['total_reviews'] ?? 0) . "\n\n";
}

// Step 4: Update Profile (Name only - safe test)
echo "4️⃣ Updating profile (name)...\n";
$updateData = json_encode([
    'name' => 'Test User Updated'
]);

$ch = curl_init("$baseUrl/profile");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, $updateData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Bearer ' . $token
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ Update profile failed (HTTP $httpCode)\n";
    echo "Response: $response\n";
} else {
    echo "✅ Profile updated successfully\n\n";
}

// Step 5: Test Rate Limiting (Optional - will trigger rate limit)
echo "5️⃣ Testing rate limiting (will fail after 5 attempts)...\n";
for ($i = 1; $i <= 7; $i++) {
    $updateData = json_encode(['name' => "Test User $i"]);

    $ch = curl_init("$baseUrl/profile");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $updateData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . $token
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 429) {
        echo "   ✅ Rate limit triggered at attempt $i (expected)\n";
        break;
    } else {
        echo "   Attempt $i: HTTP $httpCode\n";
    }

    usleep(100000); // 0.1 second delay
}

echo "\n";
echo "======================\n";
echo "✅ All tests completed!\n";
echo "======================\n";
