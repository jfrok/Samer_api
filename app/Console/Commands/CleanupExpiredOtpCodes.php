<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanupExpiredOtpCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-expired-otp-codes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired and used OTP codes from the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Cleaning up expired OTP codes...');

        // Clean up expired codes
        $expiredCount = \App\Models\OtpCode::cleanupExpired();

        // Also clean up used codes that are older than 1 hour (keep them briefly for audit)
        $usedCount = \App\Models\OtpCode::where('used', true)
            ->where('updated_at', '<', now()->subHour())
            ->delete();

        $totalDeleted = $expiredCount + $usedCount;

        $this->info("Cleanup completed:");
        $this->info("- Expired codes deleted: {$expiredCount}");
        $this->info("- Old used codes deleted: {$usedCount}");
        $this->info("- Total codes deleted: {$totalDeleted}");

        return Command::SUCCESS;
    }
}
