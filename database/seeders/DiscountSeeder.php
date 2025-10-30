<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Discount;
use Carbon\Carbon;

class DiscountSeeder extends Seeder
{
    public function run()
    {
        $discounts = [
            [
                'code' => 'WELCOME10',
                'type' => 'percentage',
                'value' => 10.00,
                'min_order_amount' => 50.00,
                'max_uses' => 1000,
                'uses_count' => 0,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addMonths(3),
                'is_active' => true,
            ],
            [
                'code' => 'FLASH20',
                'type' => 'percentage',
                'value' => 20.00,
                'min_order_amount' => 100.00,
                'max_uses' => 500,
                'uses_count' => 0,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addDays(7),
                'is_active' => true,
            ],
            [
                'code' => 'SAVE50',
                'type' => 'fixed',
                'value' => 50.00,
                'min_order_amount' => 200.00,
                'max_uses' => 200,
                'uses_count' => 0,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addMonth(),
                'is_active' => true,
            ],
            [
                'code' => 'FREESHIP',
                'type' => 'fixed',
                'value' => 15.00,
                'min_order_amount' => 75.00,
                'max_uses' => null, // Unlimited
                'uses_count' => 0,
                'start_date' => Carbon::now(),
                'end_date' => null, // No expiry
                'is_active' => true,
            ],
            [
                'code' => 'SUMMER25',
                'type' => 'percentage',
                'value' => 25.00,
                'min_order_amount' => 150.00,
                'max_uses' => 300,
                'uses_count' => 0,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addMonths(2),
                'is_active' => true,
            ],
            [
                'code' => 'EXPIRED',
                'type' => 'percentage',
                'value' => 30.00,
                'min_order_amount' => 100.00,
                'max_uses' => 100,
                'uses_count' => 0,
                'start_date' => Carbon::now()->subMonths(2),
                'end_date' => Carbon::now()->subDay(),
                'is_active' => false,
            ],
        ];

        foreach ($discounts as $discount) {
            Discount::create($discount);
        }

        $this->command->info('Discounts seeded successfully!');
    }
}
