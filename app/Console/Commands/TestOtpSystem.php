<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestOtpSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-otp-system';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the OTP system functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing OTP System Functionality');
        $this->info('================================');

        $testEmail = 'test@example.com';

        // Test 1: Generate OTP
        $this->info('1. Generating OTP for: ' . $testEmail);
        $otp = \App\Models\OtpCode::generate($testEmail, 'registration');
        $this->info('   Generated OTP: ' . $otp->code);
        $this->info('   Expires at: ' . $otp->expires_at->format('Y-m-d H:i:s'));

        // Test 2: Check if valid OTP exists
        $hasValid = \App\Models\OtpCode::hasValidOtp($testEmail, 'registration');
        $this->info('2. Has valid OTP: ' . ($hasValid ? 'Yes' : 'No'));

        // Test 3: Verify correct OTP
        $isVerified = \App\Models\OtpCode::verify($testEmail, $otp->code, 'registration');
        $this->info('3. Verification with correct code: ' . ($isVerified ? 'Success' : 'Failed'));

        // Test 4: Try to verify again (should fail)
        $isVerifiedAgain = \App\Models\OtpCode::verify($testEmail, $otp->code, 'registration');
        $this->info('4. Verification with same code again: ' . ($isVerifiedAgain ? 'Success (unexpected)' : 'Failed (expected)'));

        // Test 5: Try wrong code
        $wrongCode = '000000';
        $isWrongVerified = \App\Models\OtpCode::verify($testEmail, $wrongCode, 'registration');
        $this->info('5. Verification with wrong code: ' . ($isWrongVerified ? 'Success (unexpected)' : 'Failed (expected)'));

        // Test 6: Generate another OTP (should clean up expired ones)
        $this->info('6. Generating new OTP (should clean up old ones)');
        $newOtp = \App\Models\OtpCode::generate($testEmail, 'registration');
        $this->info('   New OTP: ' . $newOtp->code);

        // Test 7: Check cleanup
        $expiredCount = \App\Models\OtpCode::cleanupExpired();
        $this->info('7. Cleanup expired codes: ' . $expiredCount . ' deleted');

        $this->info('================================');
        $this->info('OTP System Test Completed!');
        $this->info('');
        $this->info('API Endpoints:');
        $this->info('- POST /api/send-otp (send OTP to email)');
        $this->info('- POST /api/verify-otp-register (verify OTP and register)');
        $this->info('');
        $this->info('Note: OTP codes expire after 10 minutes and are cleaned up hourly.');

        return Command::SUCCESS;
    }
}
