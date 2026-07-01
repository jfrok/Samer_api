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
                'name' => 'إلكترونيات',
                'slug' => 'electronics',
                'description' => 'أجهزة ومعدات إلكترونية متنوعة',
                'children' => [
                    ['name' => 'كمبيوترات ولابتوبات', 'slug' => 'computers-laptops', 'description' => 'أجهزة كمبيوتر مكتبية ومحمولة وملحقاتها'],
                    ['name' => 'الجوالات والهواتف', 'slug' => 'mobile-phones', 'description' => 'هواتف ذكية وأجهزة لوحية وإكسسوارات'],
                    ['name' => 'الكاميرات والتصوير', 'slug' => 'cameras-imaging', 'description' => 'كاميرات رقمية وعدسات ومعدات التصوير'],
                    ['name' => 'التلفزيونات والشاشات', 'slug' => 'tv-screens', 'description' => 'تلفزيونات ذكية وشاشات عرض'],
                    ['name' => 'الصوتيات وسماعات الرأس', 'slug' => 'audio-headphones', 'description' => 'سماعات رأس ومكبرات صوت ومعدات صوتية'],
                ]
            ],
            [
                'name' => 'الأزياء والملابس',
                'slug' => 'fashion',
                'description' => 'ملابس وإكسسوارات الموضة',
                'children' => [
                    ['name' => 'ملابس رجالية', 'slug' => 'mens-clothing', 'description' => 'تيشيرتات وقمصان وبناطيل رجالية'],
                    ['name' => 'ملابس نسائية', 'slug' => 'womens-clothing', 'description' => 'فساتين وتوبات وتنانير نسائية'],
                    ['name' => 'أحذية رجالية', 'slug' => 'mens-shoes', 'description' => 'أحذية رياضية وكلاسيكية ورسمية للرجال'],
                    ['name' => 'أحذية نسائية', 'slug' => 'womens-shoes', 'description' => 'أحذية بكعب وسهل الارتداء ورياضية للنساء'],
                    ['name' => 'إكسسوارات', 'slug' => 'accessories', 'description' => 'حقائب وأحزمة وساعات ومجوهرات'],
                ]
            ],
            [
                'name' => 'المنزل والمعيشة',
                'slug' => 'home-living',
                'description' => 'أجهزة منزلية وديكور',
                'children' => [
                    ['name' => 'الأجهزة المنزلية', 'slug' => 'home-appliances', 'description' => 'مكيفات وثلاجات وغسالات'],
                    ['name' => 'المطبخ وأدوات الطعام', 'slug' => 'kitchen-dining', 'description' => 'أواني طبخ وأدوات مائدة'],
                    ['name' => 'الأثاث', 'slug' => 'furniture', 'description' => 'كنب وطاولات وكراسي وتخزين'],
                    ['name' => 'ديكور المنزل', 'slug' => 'home-decor', 'description' => 'لوحات جدارية وإضاءة وقطع زينة'],
                ]
            ],
            [
                'name' => 'الرياضة والهواء الطلق',
                'slug' => 'sports-outdoors',
                'description' => 'معدات رياضية وأدوات للأنشطة الخارجية',
                'children' => [
                    ['name' => 'أجهزة اللياقة البدنية', 'slug' => 'fitness-equipment', 'description' => 'أثقال وحصائر يوغا ومعدات رياضية'],
                    ['name' => 'الأنشطة الخارجية', 'slug' => 'outdoor-recreation', 'description' => 'التخييم والمشي لمسافات طويلة'],
                    ['name' => 'الملابس الرياضية', 'slug' => 'sportswear', 'description' => 'ملابس وأحذية رياضية'],
                ]
            ],
        ];

        foreach ($categories as $parentData) {
            $parent = Category::create([
                'name' => $parentData['name'],
                'slug' => $parentData['slug'],
                'description' => $parentData['description'],
                'parent_id' => null,
            ]);

            foreach ($parentData['children'] as $childData) {
                Category::create([
                    'name' => $childData['name'],
                    'slug' => $childData['slug'],
                    'description' => $childData['description'],
                    'parent_id' => $parent->id,
                ]);
            }
        }

        $this->command->info('Categories seeded successfully!');
    }
}
