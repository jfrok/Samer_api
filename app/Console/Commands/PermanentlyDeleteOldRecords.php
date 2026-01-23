<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Address;
use App\Models\Order;
use Carbon\Carbon;

class PermanentlyDeleteOldRecords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:permanently-delete-old-records {--days=30 : Number of days after which to permanently delete records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permanently delete soft-deleted records that are older than the specified number of days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);

        $this->info("Permanently deleting records soft-deleted before: {$cutoffDate->format('Y-m-d H:i:s')}");
        $this->info("Retention period: {$days} days");

        // Delete old users
        $oldUsers = User::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate)
            ->get();

        $userCount = $oldUsers->count();
        if ($userCount > 0) {
            $this->info("Found {$userCount} users to permanently delete");

            foreach ($oldUsers as $user) {
                // Clean up related data before permanent deletion
                $user->tokens()->delete(); // Delete API tokens
                $user->forceDelete();
            }

            $this->info("Permanently deleted {$userCount} users");
        } else {
            $this->info("No old users to delete");
        }

        // Delete old addresses
        $oldAddresses = Address::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate)
            ->get();

        $addressCount = $oldAddresses->count();
        if ($addressCount > 0) {
            $this->info("Found {$addressCount} addresses to permanently delete");

            foreach ($oldAddresses as $address) {
                $address->forceDelete();
            }

            $this->info("Permanently deleted {$addressCount} addresses");
        } else {
            $this->info("No old addresses to delete");
        }

        // Delete old orders
        $oldOrders = Order::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate)
            ->get();

        $orderCount = $oldOrders->count();
        if ($orderCount > 0) {
            $this->info("Found {$orderCount} orders to permanently delete");

            foreach ($oldOrders as $order) {
                // Clean up related order items before permanent deletion
                $order->items()->delete();
                $order->forceDelete();
            }

            $this->info("Permanently deleted {$orderCount} orders");
        } else {
            $this->info("No old orders to delete");
        }

        $totalDeleted = $userCount + $addressCount + $orderCount;
        $this->info("Cleanup completed. Total records permanently deleted: {$totalDeleted}");

        return Command::SUCCESS;
    }
}
