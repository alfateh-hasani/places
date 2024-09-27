<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeatureSeeder extends Seeder
{
//php artisan db:seed --class=FeatureSeeder

    public function run(): void
    {
        DB::table('features')->insert([
            [
                'name_ar' => 'موقف سيارات',
                'name_en' => 'Parking',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_ar' => 'مسبح',
                'name_en' => 'Swimming Pool',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_ar' => 'حديقة',
                'name_en' => 'Garden',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_ar' => 'نادي رياضي',
                'name_en' => 'Gym',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_ar' => 'أمن 24 ساعة',
                'name_en' => '24-hour Security',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        ]);
    }
}
