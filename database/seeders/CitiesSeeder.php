<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CitiesSeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            ['name_ar' => 'الرياض', 'name_en' => 'Riyadh'],
            ['name_ar' => 'جدة', 'name_en' => 'Jeddah'],
            ['name_ar' => 'مكة المكرمة', 'name_en' => 'Makkah'],
            ['name_ar' => 'المدينة المنورة', 'name_en' => 'Al Madinah'],
            ['name_ar' => 'الدمام', 'name_en' => 'Dammam'],
            ['name_ar' => 'الخبر', 'name_en' => 'Al Khobar'],
            ['name_ar' => 'الطائف', 'name_en' => 'Taif'],
            ['name_ar' => 'بريدة', 'name_en' => 'Buraydah'],
            ['name_ar' => 'تبوك', 'name_en' => 'Tabuk'],
            ['name_ar' => 'خميس مشيط', 'name_en' => 'Khamis Mushait'],
            ['name_ar' => 'حفر الباطن', 'name_en' => 'Hafar Al-Batin'],
            ['name_ar' => 'الجبيل', 'name_en' => 'Al Jubail'],
            ['name_ar' => 'الخرج', 'name_en' => 'Al Kharj'],
            ['name_ar' => 'القطيف', 'name_en' => 'Al Qatif'],
            ['name_ar' => 'نجران', 'name_en' => 'Najran'],
            ['name_ar' => 'الأحساء', 'name_en' => 'Al Ahsa'],
            ['name_ar' => 'الباحة', 'name_en' => 'Al Bahah'],
            ['name_ar' => 'الزلفي', 'name_en' => 'Az Zulfi'],
            ['name_ar' => 'القريات', 'name_en' => 'Al Qurayyat'],
            ['name_ar' => 'الخفجي', 'name_en' => 'Al Khafji'],
            ['name_ar' => 'الظهران', 'name_en' => 'Ad Dhran'],
        ];

        foreach ($cities as $city) {
            \App\Models\City::create($city);
        }
    }
}
