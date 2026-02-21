<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Address;
use Faker\Factory as Faker;

class AddressSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        // Get all customers
        $users = User::where('role', 'customer')->get();

        foreach ($users as $user) {
            // Each user gets 1-3 addresses
            $addressCount = rand(1, 3);

            for ($i = 0; $i < $addressCount; $i++) {
                Address::create([
                    'user_id' => $user->id,
                    'type' => $faker->randomElement(['shipping', 'billing']),
                    'name' => $faker->randomElement(['Home', 'Work', 'Office', 'Apartment']),
                    'street' => $faker->streetAddress,
                    'closest_point' => $faker->secondaryAddress,
                    'city' => $faker->city,
                    'state' => $faker->state,
                    'country' => $faker->country,
                    'is_default' => $i === 0, // First address is default
                ]);
            }
        }

        $this->command->info('Addresses seeded successfully!');
    }
}
