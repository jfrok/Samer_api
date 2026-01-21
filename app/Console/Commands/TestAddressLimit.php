<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Address;
use Illuminate\Support\Facades\Auth;

class TestAddressLimit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-address-limit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the 3-address limit functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing address limit functionality...');

        $user = User::first();
        if (!$user) {
            $this->error('No users found in database');
            return;
        }

        $this->info("Testing with user: {$user->email}");

        // Authenticate the user for the test
        Auth::login($user);

        $currentCount = $user->addresses()->count();
        $this->info("Current address count: {$currentCount}");

        // Test creating a new address
        $addressData = [
            'name' => 'Test Address ' . ($currentCount + 1),
            'type' => 'shipping',
            'street' => '123 Test Street',
            'city' => 'Test City',
            'state' => 'Test State',
            'country' => 'Test Country',
            'is_default' => false
        ];

        try {
            $address = new Address($addressData);
            $address->user_id = $user->id;
            $address->save();

            $this->info('Address created successfully!');
            $this->info("New address count: {$user->addresses()->count()}");
        } catch (\Exception $e) {
            $this->error('Address creation failed: ' . $e->getMessage());
        }

        // Test the canDelete functionality
        $address = $user->addresses()->first();
        if ($address) {
            $this->info("Testing canDelete for address ID: {$address->id}");

            // Check if address is used in orders
            $isUsedInOrders = \DB::table('orders')
                ->where('shipping_address_id', $address->id)
                ->exists();

            $this->info("Address used in orders: " . ($isUsedInOrders ? 'Yes' : 'No'));
        }
    }
}
