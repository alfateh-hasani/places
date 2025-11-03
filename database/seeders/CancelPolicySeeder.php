<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CancelPolicySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \DB::table('settings')->insert([
            'key' => 'cancel_before_hours',
            'name' => 'عدد الساعات المطلوبة قبل موعد الحجز للسماح بالإلغاء',
            'description' => 'عدد الساعات التي يجب أن تكون متبقية قبل موعد الدخول للسماح للعميل بإلغاء الحجز',
            'value' => '24',
            'field' => '{"name":"value","label":"القيمة (بالساعات)","type":"number"}',
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
