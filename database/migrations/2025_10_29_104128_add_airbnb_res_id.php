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
        //
        Schema::table('bookings', function (Blueprint $t) {
            if (!Schema::hasColumn('bookings','external_uid')) {
                $t->string('external_uid',255)->nullable()->after('number_of_booking');
            }
            if (!Schema::hasColumn('bookings','external_sequence')) {
                $t->unsignedInteger('external_sequence')->nullable()->after('external_uid');
            }
            if (!Schema::hasColumn('bookings','external_reservation_id')) {
                $t->string('external_reservation_id',64)->nullable()->after('external_sequence');
            }
            // فهرس تداخل
            //$t->index(['apartment_id','check_in','check_out'],'idx_bookings_apartment_dates');
            // منع التكرار لحجوزات Airbnb فقط (بسبب غياب partial index في MySQL نضيف is_airbnb_booking)
            $t->unique(['apartment_id','external_reservation_id','is_airbnb_booking'],'uniq_apartment_resid_airbnb');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('bookings', function (Blueprint $t) {
            $t->dropUnique('uniq_apartment_resid_airbnb');
            $t->dropIndex('idx_bookings_apartment_dates');
            $t->dropColumn(['external_reservation_id','external_sequence','external_uid']);
        });

    }
};
