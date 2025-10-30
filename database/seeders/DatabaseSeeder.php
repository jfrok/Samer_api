<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            ImprovedProductSeeder::class,
            DiscountSeeder::class,
            AddressSeeder::class,
        ]);

        $this->command->info('All seeders completed successfully!');
    }
}
