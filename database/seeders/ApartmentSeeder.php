<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;

class ApartmentSeeder extends Seeder
{
    //php artisan db:seed --class=ApartmentSeeder
    public function run(): void
    {
        $featureIds = DB::table('features')->pluck('id')->toArray();
         if (empty($featureIds)) {
            $this->command->info('No features found in the database. Please add some features first.');
            return;
        }

        $building_ids = DB::table('buildings')->pluck('id')->toArray();
        $policy_ids = DB::table('policies')->pluck('id')->toArray();
        $smart_lock_ids = DB::table('smart_locks')->pluck('id')->toArray();

        $apartmentNamesAr = ['شقة فاخرة', 'شقة عائلية', 'شقة اقتصادية', 'شقة حديثة', 'شقة واسعة'];
        $apartmentNamesEn = ['Luxury Apartment', 'Family Apartment', 'Economy Apartment', 'Modern Apartment', 'Spacious Apartment'];
        $apartmentDescriptionsAr = [
            'شقة فاخرة تحتوي على 3 غرف نوم وحمامين، مثالية للعائلات.',
            'شقة عائلية واسعة مع مطبخ مفتوح وغرفة معيشة كبيرة.',
            'شقة اقتصادية في موقع مركزي، مثالية للأفراد أو الأزواج.',
            'شقة حديثة بتصميم عصري، تضم أحدث التقنيات المنزلية.',
            'شقة واسعة مع إطلالة رائعة على المدينة، قريبة من وسائل الراحة.'
        ];

        $apartmentDescriptionsEn = [
            'Luxury apartment with 3 bedrooms and 2 bathrooms, perfect for families.',
            'Spacious family apartment with an open kitchen and large living room.',
            'Economy apartment in a central location, ideal for individuals or couples.',
            'Modern apartment with contemporary design, featuring the latest home technologies.',
            'Spacious apartment with a great city view, close to all amenities.'
        ];

        for ($i = 0; $i < 5; $i++) {
            $apartmentId = DB::table('apartments')->insertGetId([
                'name_ar' => $apartmentNamesAr[$i],
                'name_en' => $apartmentNamesEn[$i],
                'building_id' => $building_ids[array_rand($building_ids)],
                'policy_id' => $policy_ids[array_rand($policy_ids)],
                'description_ar' => $apartmentDescriptionsAr[$i],
                'description_en' => $apartmentDescriptionsEn[$i],
                'num_rooms' => rand(1, 5),
                'num_beds' => rand(1, 10),
                'area' => rand(50, 150),
                'is_active' => true,
                'price' => rand(100, 500),
                'smart_lock_id' => $smart_lock_ids[array_rand($smart_lock_ids)],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('apartment_features')->insert([
                'apartment_id' => $apartmentId,
                'feature_id' => $featureIds[array_rand($featureIds)],
            ]);
        }
    }
}
