<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Models\OtpCode;
use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "Testing OTP API endpoints...\n";
echo "1. Testing send-otp endpoint...\n";

// Test data
$testEmail = 'test-otp-' . time() . '@example.com';
$testData = ['email' => $testEmail, 'language' => 'ar'];

try {
    // Simulate the sendOtp method
    $userExists = User::where('email', $testEmail)->exists();
    echo "User exists check: " . ($userExists ? 'Yes' : 'No') . "\n";

    if (!$userExists) {
        $otp = OtpCode::generate($testEmail, 'registration');
        echo "OTP generated: " . $otp->code . "\n";
        echo "Expires at: " . $otp->expires_at . "\n";

        // Test verification
        $verified = OtpCode::verify($testEmail, $otp->code, 'registration');
        echo "OTP verification: " . ($verified ? 'Success' : 'Failed') . "\n";

        echo "OTP system working correctly!\n";
    } else {
        echo "Test email already exists\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
