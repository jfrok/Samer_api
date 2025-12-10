<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\City;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            ['name' => 'Baghdad',        'label' => 'بغداد',          'code' => 'BGH', 'shipping_price' => 5000, 'country' => 'IQ'],
            ['name' => 'Basra',          'label' => 'البصرة',         'code' => 'BSR', 'shipping_price' => 6000, 'country' => 'IQ'],
            ['name' => 'Erbil',          'label' => 'أربيل',          'code' => 'ERB', 'shipping_price' => 7000, 'country' => 'IQ'],
            ['name' => 'Sulaymaniyah',   'label' => 'السليمانية',     'code' => 'SLM', 'shipping_price' => 7000, 'country' => 'IQ'],
            ['name' => 'Duhok',          'label' => 'دهوك',           'code' => 'DHK', 'shipping_price' => 7000, 'country' => 'IQ'],
            ['name' => 'Kirkuk',         'label' => 'كركوك',          'code' => 'KRK', 'shipping_price' => 6500, 'country' => 'IQ'],
            ['name' => 'Mosul',          'label' => 'الموصل',         'code' => 'MSL', 'shipping_price' => 6500, 'country' => 'IQ'],
            ['name' => 'Najaf',          'label' => 'النجف',          'code' => 'NJF', 'shipping_price' => 5500, 'country' => 'IQ'],
            ['name' => 'Karbala',        'label' => 'كربلاء',         'code' => 'KRB', 'shipping_price' => 5500, 'country' => 'IQ'],
            ['name' => 'Babil',          'label' => 'بابل',           'code' => 'BAB', 'shipping_price' => 6000, 'country' => 'IQ'],
            ['name' => 'Wasit',          'label' => 'واسط',           'code' => 'WAS', 'shipping_price' => 6000, 'country' => 'IQ'],
            ['name' => 'Maysan',         'label' => 'ميسان',          'code' => 'MYS', 'shipping_price' => 6000, 'country' => 'IQ'],
            ['name' => 'Dhi Qar',        'label' => 'ذي قار',         'code' => 'DHQ', 'shipping_price' => 6000, 'country' => 'IQ'],
            ['name' => 'Al-Qadisiyyah',  'label' => 'القادسية',       'code' => 'QAD', 'shipping_price' => 6000, 'country' => 'IQ'],
            ['name' => 'Salah ad-Din',   'label' => 'صلاح الدين',     'code' => 'SLD', 'shipping_price' => 6500, 'country' => 'IQ'],
            ['name' => 'Diyala',         'label' => 'ديالى',          'code' => 'DYL', 'shipping_price' => 6500, 'country' => 'IQ'],
            ['name' => 'Anbar',          'label' => 'الأنبار',        'code' => 'ANB', 'shipping_price' => 7000, 'country' => 'IQ'],
            ['name' => 'Halabja',        'label' => 'حلبجة',          'code' => 'HLB', 'shipping_price' => 7000, 'country' => 'IQ'],
        ];

        foreach ($cities as $c) {
            City::updateOrCreate(
                ['name' => $c['name'], 'country' => $c['country']],
                [
                    'label' => $c['label'] ?? $c['name'],
                    'code' => $c['code'],
                    'shipping_price' => $c['shipping_price'],
                    'is_active' => true,
                ]
            );
        }
    }
}
