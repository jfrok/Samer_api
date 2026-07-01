<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class OtpCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'code',
        'purpose',
        'expires_at',
        'used',
        'verified_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'used' => 'boolean',
    ];

    /**
     * Generate a new OTP code
     */
    public static function generate(string $email, string $purpose = 'registration', int $length = 6): self
    {
        // Clean up expired codes for this email/purpose
        self::where('email', $email)
            ->where('purpose', $purpose)
            ->where('expires_at', '<', now())
            ->delete();

        // Generate 6-digit code
        $code = str_pad(mt_rand(0, 999999), $length, '0', STR_PAD_LEFT);

        return self::create([
            'email' => $email,
            'code' => $code,
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes(10), // 10 minutes expiry
            'used' => false,
        ]);
    }

    /**
     * Verify an OTP code and mark verified_at (Step 2 - does NOT consume/use the OTP)
     */
    public static function verify(string $email, string $code, string $purpose = 'registration'): bool
    {
        $otp = self::where('email', $email)
            ->where('code', $code)
            ->where('purpose', $purpose)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        if ($otp) {
            $otp->update(['verified_at' => now()]);
            return true;
        }

        return false;
    }

    /**
     * Consume an OTP that was already verified (Step 3 - marks used = true)
     */
    public static function consume(string $email, string $code, string $purpose = 'registration'): bool
    {
        $otp = self::where('email', $email)
            ->where('code', $code)
            ->where('purpose', $purpose)
            ->where('used', false)
            ->whereNotNull('verified_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($otp) {
            $otp->update(['used' => true]);
            return true;
        }

        return false;
    }

    /**
     * Consume a verified OTP by email only (Step 3 - no code needed, just checks verified_at is set)
     */
    public static function consumeVerified(string $email, string $purpose = 'registration'): bool
    {
        $otp = self::where('email', $email)
            ->where('purpose', $purpose)
            ->where('used', false)
            ->whereNotNull('verified_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($otp) {
            $otp->update(['used' => true]);
            return true;
        }

        return false;
    }

    /**
     * Check if email has a valid (non-expired, unused) OTP
     */
    public static function hasValidOtp(string $email, string $purpose = 'registration'): bool
    {
        return self::where('email', $email)
            ->where('purpose', $purpose)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->exists();
    }

    /**
     * Clean up expired OTP codes
     */
    public static function cleanupExpired(): int
    {
        return self::where('expires_at', '<', now())->delete();
    }
}
