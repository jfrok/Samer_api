<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Address;
use App\Models\Order;

class TestSoftDeletes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-soft-deletes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test soft delete functionality for users, addresses, and orders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Soft Delete Functionality');
        $this->info('================================');

        // Test User soft delete
        $this->info('1. Testing User Soft Deletes:');
        $user = User::first();
        if ($user) {
            $this->info("   Found user: {$user->email}");

            // Soft delete the user
            $user->delete();
            $this->info('   User soft deleted');

            // Check if user still exists in normal query
            $userExists = User::find($user->id);
            $this->info('   User exists in normal query: ' . ($userExists ? 'Yes' : 'No'));

            // Check if user exists in trashed query
            $trashedUser = User::onlyTrashed()->find($user->id);
            $this->info('   User exists in trashed query: ' . ($trashedUser ? 'Yes' : 'No'));

            // Restore the user
            $user->restore();
            $this->info('   User restored');
        } else {
            $this->warn('   No users found to test');
        }

        // Test Address soft delete
        $this->info('2. Testing Address Soft Deletes:');
        $address = Address::first();
        if ($address) {
            $this->info("   Found address ID: {$address->id}");

            // Soft delete the address
            $address->delete();
            $this->info('   Address soft deleted');

            // Check if address still exists in normal query
            $addressExists = Address::find($address->id);
            $this->info('   Address exists in normal query: ' . ($addressExists ? 'Yes' : 'No'));

            // Check if address exists in trashed query
            $trashedAddress = Address::onlyTrashed()->find($address->id);
            $this->info('   Address exists in trashed query: ' . ($trashedAddress ? 'Yes' : 'No'));

            // Restore the address
            $address->restore();
            $this->info('   Address restored');
        } else {
            $this->warn('   No addresses found to test');
        }

        // Test Order soft delete
        $this->info('3. Testing Order Soft Deletes:');
        $order = Order::first();
        if ($order) {
            $this->info("   Found order: {$order->reference_number}");

            // Soft delete the order
            $order->delete();
            $this->info('   Order soft deleted');

            // Check if order still exists in normal query
            $orderExists = Order::find($order->id);
            $this->info('   Order exists in normal query: ' . ($orderExists ? 'Yes' : 'No'));

            // Check if order exists in trashed query
            $trashedOrder = Order::onlyTrashed()->find($order->id);
            $this->info('   Order exists in trashed query: ' . ($trashedOrder ? 'Yes' : 'No'));

            // Restore the order
            $order->restore();
            $this->info('   Order restored'); 
            $this->warn('   No orders found to test');
        }

        $this->info('================================');
        $this->info('Soft delete testing completed!');
        $this->info('');
        $this->info('Note: The permanent deletion cron job will run daily at 2 AM');
        $this->info('and permanently delete records that have been soft-deleted for 30+ days.');

        return Command::SUCCESS;
    }
}
