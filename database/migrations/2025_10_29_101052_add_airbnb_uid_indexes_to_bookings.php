<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $t) {
            if (!Schema::hasColumn('bookings', 'external_uid')) {
                $t->string('external_uid', 255)->nullable()->after('number_of_booking');
            }
            if (!Schema::hasColumn('bookings', 'external_sequence')) {
                $t->unsignedInteger('external_sequence')->nullable()->after('external_uid');
            }
            if (!Schema::hasColumn('bookings', 'is_airbnb_booking')) {
                $t->boolean('is_airbnb_booking')->default(false)->after('payment_method_code');
            }

            // فهرس زمني لتسريع التداخل
            $t->index(['apartment_id', 'check_in', 'check_out'], 'idx_bookings_apartment_dates');

            // منع التكرار لنفس الحدث ضمن نفس الشقة
            $t->unique(['apartment_id', 'external_uid'], 'uniq_bookings_apartment_uid');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $t) {
            $t->dropUnique('uniq_bookings_apartment_uid');
            $t->dropIndex('idx_bookings_apartment_dates');
            $t->dropColumn(['external_uid', 'external_sequence', 'is_airbnb_booking']);
        });

    }
};
