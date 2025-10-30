<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Faker\Factory as Faker;
use Illuminate\Support\Str;

class ImprovedProductSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        // Get leaf categories (those without children)
        $categories = Category::whereDoesntHave('children')->get();

        if ($categories->isEmpty()) {
            $this->command->error('No categories found! Run CategorySeeder first.');
            return;
        }

        $productNames = [
            'Electronics' => [
                'MacBook Pro', 'Dell XPS', 'HP Pavilion', 'Lenovo ThinkPad', 'Asus ROG',
                'iPhone 15', 'Samsung Galaxy S24', 'Google Pixel 8', 'OnePlus 12', 'Xiaomi 14',
                'Canon EOS R5', 'Sony A7 IV', 'Nikon Z8', 'Fujifilm X-T5', 'Panasonic GH6',
                'LG OLED TV', 'Samsung QLED', 'Sony Bravia', 'TCL Smart TV', 'Roku Ultra',
                'Sony WH-1000XM5', 'Bose QuietComfort', 'AirPods Pro', 'JBL Flip 6', 'Beats Studio'
            ],
            'Fashion' => [
                'Classic Cotton T-Shirt', 'Slim Fit Jeans', 'Casual Button Down', 'Leather Jacket',
                'Summer Dress', 'Yoga Pants', 'Blazer', 'Hoodie', 'Polo Shirt', 'Cardigan',
                'Running Shoes', 'Leather Boots', 'Canvas Sneakers', 'Loafers', 'High Heels',
                'Crossbody Bag', 'Backpack', 'Wallet', 'Watch', 'Sunglasses'
            ],
            'Home' => [
                'Air Conditioner', 'Refrigerator', 'Washing Machine', 'Microwave Oven', 'Blender',
                'Cooking Pan Set', 'Chef Knife Set', 'Dinner Plate Set', 'Coffee Maker', 'Toaster',
                'Sofa Set', 'Dining Table', 'Office Chair', 'Bookshelf', 'Bed Frame',
                'Table Lamp', 'Wall Clock', 'Photo Frame', 'Throw Pillow', 'Area Rug'
            ],
            'Sports' => [
                'Dumbbell Set', 'Yoga Mat', 'Treadmill', 'Exercise Ball', 'Resistance Bands',
                'Camping Tent', 'Sleeping Bag', 'Hiking Backpack', 'Portable Stove', 'Flashlight',
                'Running Shorts', 'Sports Bra', 'Training Shirt', 'Athletic Socks', 'Track Jacket'
            ]
        ];

        $brands = [
            'Electronics' => ['Apple', 'Samsung', 'Sony', 'LG', 'Dell', 'HP', 'Asus', 'Lenovo', 'Canon', 'Nikon'],
            'Fashion' => ['Nike', 'Adidas', 'Zara', 'H&M', 'Uniqlo', 'Levi\'s', 'Gap', 'Tommy Hilfiger', 'Calvin Klein', 'Ralph Lauren'],
            'Home' => ['IKEA', 'Philips', 'Panasonic', 'Whirlpool', 'KitchenAid', 'Dyson', 'Bosch', 'Samsung', 'LG', 'GE'],
            'Sports' => ['Nike', 'Adidas', 'Under Armour', 'Reebok', 'Puma', 'The North Face', 'Columbia', 'Patagonia', 'Lululemon', 'Decathlon']
        ];

        $colors = ['Black', 'White', 'Gray', 'Navy', 'Red', 'Blue', 'Green', 'Yellow', 'Pink', 'Purple', 'Brown', 'Beige'];
        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
        $shoeSizes = ['6', '7', '8', '9', '10', '11', '12'];

        $totalProducts = 500; // Adjust as needed
        $productsPerCategory = ceil($totalProducts / $categories->count());

        foreach ($categories as $category) {
            // Determine which product names and brands to use based on category
            $categoryType = $this->getCategoryType($category->name);
            $names = $productNames[$categoryType] ?? $productNames['Electronics'];
            $brandList = $brands[$categoryType] ?? $brands['Electronics'];

            for ($i = 0; $i < $productsPerCategory; $i++) {
                $productName = $faker->randomElement($names);
                $brand = $faker->randomElement($brandList);
                $basePrice = $faker->randomFloat(2, 29.99, 1999.99);

                $product = Product::create([
                    'name' => $productName . ' ' . $faker->word(),
                    'description' => $faker->paragraph(3),
                    'category_id' => $category->id,
                    'brand' => $brand,
                    'base_price' => $basePrice,
                    'images' => $this->generateProductImages($category->name),
                    'slug' => Str::slug($productName . ' ' . $brand . ' ' . $faker->word() . ' ' . uniqid()),
                    'is_active' => $faker->boolean(90), // 90% active
                ]);

                // Determine if this product should have size/color variants
                $hasColors = $this->shouldHaveColors($category->name);
                $hasSizes = $this->shouldHaveSizes($category->name);

                $variantCount = rand(3, 8);
                $usedCombinations = [];

                for ($j = 0; $j < $variantCount; $j++) {
                    // Generate unique combinations
                    $color = $hasColors ? $faker->randomElement($colors) : 'Standard';
                    $size = $hasSizes ? $faker->randomElement($this->isShoeCategory($category->name) ? $shoeSizes : $sizes) : 'One Size';

                    $combination = $color . '-' . $size;

                    // Skip if combination already exists
                    if (in_array($combination, $usedCombinations)) {
                        continue;
                    }
                    $usedCombinations[] = $combination;

                    ProductVariant::create([
                        'product_id' => $product->id,
                        'size' => $size,
                        'color' => $color,
                        'price' => $basePrice + $faker->randomFloat(2, -10, 50),
                        'stock' => $faker->numberBetween(0, 200),
                        'sku' => strtoupper($brand[0] . substr($productName, 0, 2) . '-' . $faker->bothify('???-####')),
                    ]);
                }
            }
        }

        $this->command->info('Products and variants seeded successfully!');
    }

    private function getCategoryType($categoryName)
    {
        if (str_contains(strtolower($categoryName), 'electronic') ||
            str_contains(strtolower($categoryName), 'computer') ||
            str_contains(strtolower($categoryName), 'phone') ||
            str_contains(strtolower($categoryName), 'camera') ||
            str_contains(strtolower($categoryName), 'tv') ||
            str_contains(strtolower($categoryName), 'audio')) {
            return 'Electronics';
        }

        if (str_contains(strtolower($categoryName), 'clothing') ||
            str_contains(strtolower($categoryName), 'shoes') ||
            str_contains(strtolower($categoryName), 'accessories') ||
            str_contains(strtolower($categoryName), 'fashion')) {
            return 'Fashion';
        }

        if (str_contains(strtolower($categoryName), 'home') ||
            str_contains(strtolower($categoryName), 'appliance') ||
            str_contains(strtolower($categoryName), 'kitchen') ||
            str_contains(strtolower($categoryName), 'furniture')) {
            return 'Home';
        }

        if (str_contains(strtolower($categoryName), 'sport') ||
            str_contains(strtolower($categoryName), 'fitness') ||
            str_contains(strtolower($categoryName), 'outdoor')) {
            return 'Sports';
        }

        return 'Electronics';
    }

    private function shouldHaveColors($categoryName)
    {
        $name = strtolower($categoryName);
        return str_contains($name, 'clothing') ||
               str_contains($name, 'shoes') ||
               str_contains($name, 'accessories') ||
               str_contains($name, 'phone') ||
               str_contains($name, 'laptop');
    }

    private function shouldHaveSizes($categoryName)
    {
        $name = strtolower($categoryName);
        return str_contains($name, 'clothing') || str_contains($name, 'shoes');
    }

    private function isShoeCategory($categoryName)
    {
        return str_contains(strtolower($categoryName), 'shoes');
    }

    protected function generateProductImages($categoryName)
    {
        $imageBasePath = '/assets/products/';
        $localImages = [
            'product-1.jpg',
            'product-2.jpg',
            'product-3.jpg',
            'product-4.jpg',
            'product-5.jpg'
        ];

        // Map categories to better external images
        $categoryImages = [
            'Electronics' => [
                'https://plus.unsplash.com/premium_photo-1681475906949-1c16d89ca1bb?w=400&h=400&fit=crop',
                'https://images.unsplash.com/photo-1498049794561-7780e7231661?w=400&h=400&fit=crop',
                'https://images.unsplash.com/photo-1520110120862-70b04c1d9dff?w=400&h=400&fit=crop'
            ],
            'Fashion' => [
                'https://images.unsplash.com/photo-1445205170230-053b83016050?w=400&h=400&fit=crop',
                'https://images.unsplash.com/photo-1434389677669-e08b4cac3105?w=400&h=400&fit=crop',
                'https://images.unsplash.com/photo-1515372039744-b8f02a3ae446?w=400&h=400&fit=crop'
            ],
            'Home & Garden' => [
                'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=400&h=400&fit=crop',
                'https://images.unsplash.com/photo-1513475382585-d06e58bcb0e0?w=400&h=400&fit=crop',
                'https://images.unsplash.com/photo-1556228578-0d85b1a4d571?w=400&h=400&fit=crop'
            ],
            'Sports & Outdoors' => [
                'https://images.unsplash.com/photo-1544966503-7cc5ac882d5f?w=400&h=400&fit=crop',
                'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=400&h=400&fit=crop',
                'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=400&h=400&fit=crop'
            ],
            'Beauty & Personal Care' => [
                'https://images.unsplash.com/photo-1596462502278-27bfdc403348?w=400&h=400&fit=crop',
                'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?w=400&h=400&fit=crop',
                'https://images.unsplash.com/photo-1571781926291-c477ebfd024b?w=400&h=400&fit=crop'
            ]
        ];

        // Use category-specific images if available, otherwise use generic product images
        $images = $categoryImages[$categoryName] ?? [
            'https://images.unsplash.com/photo-1560472355-536de3962603?w=400&h=400&fit=crop',
            'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400&h=400&fit=crop',
            'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400&h=400&fit=crop'
        ];

        // Mix local and external images
        $result = [];
        $result[] = $imageBasePath . $localImages[array_rand($localImages)]; // Always include one local image
        $result[] = $images[array_rand($images)]; // One category-specific external image
        $result[] = $images[array_rand($images)]; // Another external image

        return $result;
    }
}
