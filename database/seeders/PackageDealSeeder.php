<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PackageDeal;
use App\Models\Product;
use Illuminate\Support\Str;

class PackageDealSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some products for packages
        $products = Product::inRandomOrder()->limit(20)->get();

        if ($products->count() < 6) {
            $this->command->warn('Not enough products to create package deals. Please seed products first.');
            return;
        }

        $packages = [
            [
                'name' => 'Gaming Essentials Bundle',
                'description' => 'Everything you need for an ultimate gaming setup',
                'product_count' => 3,
                'discount' => 25,
            ],
            [
                'name' => 'Home Office Pro Package',
                'description' => 'Complete workspace solution for remote workers',
                'product_count' => 4,
                'discount' => 20,
            ],
            [
                'name' => 'Fitness Starter Kit',
                'description' => 'Get started on your fitness journey with this complete set',
                'product_count' => 3,
                'discount' => 30,
            ],
            [
                'name' => 'Smart Home Basics',
                'description' => 'Transform your home with these smart devices',
                'product_count' => 3,
                'discount' => 22,
            ],
            [
                'name' => 'Photography Beginner Bundle',
                'description' => 'Everything a beginner photographer needs',
                'product_count' => 2,
                'discount' => 18,
            ],
            [
                'name' => 'Kitchen Essentials Pack',
                'description' => 'Must-have items for every modern kitchen',
                'product_count' => 4,
                'discount' => 28,
            ],
        ];

        $productIndex = 0;

        foreach ($packages as $packageData) {
            // Get products for this package
            $packageProducts = $products->slice($productIndex, $packageData['product_count']);
            $productIndex += $packageData['product_count'];

            if ($packageProducts->count() < $packageData['product_count']) {
                continue; // Skip if not enough products
            }

            // Calculate original price
            $originalPrice = $packageProducts->sum('base_price');

            // Calculate package price with discount
            $packagePrice = $originalPrice * (1 - $packageData['discount'] / 100);

            // Get images from products
            $images = [];
            foreach ($packageProducts as $product) {
                if (!empty($product->images)) {
                    $productImages = is_string($product->images) ? json_decode($product->images, true) : $product->images;
                    if (is_array($productImages) && count($productImages) > 0) {
                        $images[] = $productImages[0];
                    }
                }
            }

            // Create package
            $package = PackageDeal::create([
                'name' => $packageData['name'],
                'slug' => Str::slug($packageData['name']),
                'description' => $packageData['description'],
                'original_price' => $originalPrice,
                'package_price' => round($packagePrice, 2),
                'discount_percentage' => $packageData['discount'],
                'images' => !empty($images) ? $images : null,
                'is_active' => true,
                'stock' => rand(5, 50),
                'starts_at' => now(),
                'ends_at' => now()->addMonths(2),
            ]);

            // Attach products
            foreach ($packageProducts as $product) {
                $package->products()->attach($product->id, [
                    'quantity' => 1,
                ]);
            }

            $this->command->info("Created package: {$package->name}");
        }

        $this->command->info('Package deals seeded successfully!');
    }
}
