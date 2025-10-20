<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Faker\Factory as Faker;
use Illuminate\Support\Str;  // Add this import

class ProductSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        // Create categories
        $categories = ['Men\'s Clothing', 'Women\'s Clothing', 'Men\'s Shoes', 'Women\'s Shoes'];
        foreach ($categories as $catName) {
            Category::create([
                'name' => $catName,
                'slug' => Str::slug($catName),  // Fixed: Use Str::slug
                'description' => $faker->sentence,
            ]);
        }

        $cats = Category::all();

        // Create 2000 products (scale as needed)
        for ($i = 0; $i < 2000; $i++) {
            $product = Product::create([
                'name' => $faker->words(3, true) . ' ' . ($i % 2 ? 'Shirt' : 'Sneakers'),
                'description' => $faker->paragraph,
                'category_id' => $cats->random()->id,
                'brand' => $faker->company,
                'base_price' => $faker->randomFloat(2, 20, 200),
                'images' => [$faker->imageUrl(300, 300), $faker->imageUrl(300, 300)],
                'slug' => $faker->slug,  // This is fine (Faker's method)
                'is_active' => true,
            ]);

            // Add 3-5 variants per product
            for ($j = 0; $j < rand(3, 5); $j++) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'size' => $faker->randomElement(['S', 'M', 'L', 'XL', '42', '43', '44']),
                    'color' => $faker->colorName,
                    'price' => $product->base_price + $faker->randomFloat(2, 0, 20),
                    'stock' => $faker->numberBetween(10, 100),
                    'sku' => 'SKU-' . strtoupper($faker->bothify('??????')),
                ]);
            }
        }
    }
}
