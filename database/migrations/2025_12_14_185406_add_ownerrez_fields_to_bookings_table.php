<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('ownerrez_booking_id')->nullable()->unique()->after('uuid');
            $table->string('channel_name')->nullable()->after('booking_source');
            $table->string('external_reference')->nullable()->after('channel_name');
        });

        // تحديث enum للحقل الموجود booking_source لإضافة قيم جديدة
        DB::statement("ALTER TABLE bookings MODIFY COLUMN booking_source ENUM('web', 'android', 'ios', 'ownerrez', 'airbnb', 'booking_com', 'guesty', 'other') DEFAULT 'web'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['ownerrez_booking_id', 'channel_name', 'external_reference']);
        });

        // إرجاع enum إلى القيم الأصلية
        DB::statement("ALTER TABLE bookings MODIFY COLUMN booking_source ENUM('web', 'android', 'ios') DEFAULT 'web'");
    }
};
