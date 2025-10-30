<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            [
                'name' => 'Electronics',
                'description' => 'Electronic devices and gadgets',
                'children' => [
                    ['name' => 'Computers & Laptops', 'description' => 'Desktop computers, laptops, and accessories'],
                    ['name' => 'Mobile & Phones', 'description' => 'Smartphones, tablets, and mobile accessories'],
                    ['name' => 'Camera & Imaging', 'description' => 'Digital cameras, lenses, and photography equipment'],
                    ['name' => 'TV & Smart Box', 'description' => 'Televisions, streaming devices, and entertainment'],
                    ['name' => 'Audio & Headphones', 'description' => 'Headphones, speakers, and audio equipment'],
                ]
            ],
            [
                'name' => 'Fashion',
                'description' => 'Clothing and fashion accessories',
                'children' => [
                    ['name' => 'Men\'s Clothing', 'description' => 'T-shirts, shirts, pants, and more'],
                    ['name' => 'Women\'s Clothing', 'description' => 'Dresses, tops, skirts, and more'],
                    ['name' => 'Men\'s Shoes', 'description' => 'Sneakers, boots, formal shoes'],
                    ['name' => 'Women\'s Shoes', 'description' => 'Heels, flats, sneakers, boots'],
                    ['name' => 'Accessories', 'description' => 'Bags, belts, watches, and jewelry'],
                ]
            ],
            [
                'name' => 'Home & Living',
                'description' => 'Home appliances and decor',
                'children' => [
                    ['name' => 'Home Appliances', 'description' => 'Air conditioners, refrigerators, washing machines'],
                    ['name' => 'Kitchen & Dining', 'description' => 'Cookware, utensils, and dining sets'],
                    ['name' => 'Furniture', 'description' => 'Sofas, tables, chairs, and storage'],
                    ['name' => 'Home Decor', 'description' => 'Wall art, lighting, and decorative items'],
                ]
            ],
            [
                'name' => 'Sports & Outdoors',
                'description' => 'Sports equipment and outdoor gear',
                'children' => [
                    ['name' => 'Fitness Equipment', 'description' => 'Weights, yoga mats, and exercise gear'],
                    ['name' => 'Outdoor Recreation', 'description' => 'Camping, hiking, and outdoor activities'],
                    ['name' => 'Sports Wear', 'description' => 'Athletic clothing and shoes'],
                ]
            ],
        ];

        foreach ($categories as $parentData) {
            $parent = Category::create([
                'name' => $parentData['name'],
                'slug' => Str::slug($parentData['name']),
                'description' => $parentData['description'],
                'parent_id' => null,
            ]);

            foreach ($parentData['children'] as $childData) {
                Category::create([
                    'name' => $childData['name'],
                    'slug' => Str::slug($childData['name']),
                    'description' => $childData['description'],
                    'parent_id' => $parent->id,
                ]);
            }
        }

        $this->command->info('Categories seeded successfully!');
    }
}
