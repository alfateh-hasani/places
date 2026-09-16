<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeds the "hours before check-in a unit transfer is still allowed" setting so it is
 * editable from the Backpack settings screen (mirrors {@see CancelPolicySeeder}).
 * Idempotent — safe to re-run.
 */
class TransferPolicySeeder extends Seeder
{
    public function run(): void
    {
        \DB::table('settings')->updateOrInsert(
            ['key' => 'transfer_before_hours'],
            [
                'name' => 'عدد الساعات المطلوبة قبل موعد الحجز للسماح بنقل الوحدة',
                'description' => 'عدد الساعات التي يجب أن تكون متبقية قبل موعد الدخول للسماح بنقل الحجز إلى وحدة أخرى',
                'value' => '24',
                'field' => '{"name":"value","label":"القيمة (بالساعات)","type":"number"}',
                'active' => 1,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
