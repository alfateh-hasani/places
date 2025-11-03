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
        Schema::table('booking_channel_conflicts', function (Blueprint $t) {
            if (!Schema::hasColumn('booking_channel_conflicts','external_reservation_id')) {
                $t->string('external_reservation_id',64)->nullable()->after('external_uid');
            }
            // مفتاح طبيعي لمنع تكرار نفس التعارض
            $t->unique(['apartment_id','channel','external_reservation_id','ext_check_in','ext_check_out'],'uq_conflict_key2');
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
