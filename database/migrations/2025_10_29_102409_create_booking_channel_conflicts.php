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
        Schema::create('booking_channel_conflicts', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('apartment_id')->index();
            $t->string('channel')->index();                 // 'airbnb'
            $t->string('external_uid')->nullable();
            $t->date('ext_check_in');
            $t->date('ext_check_out');                      // end-exclusive
            $t->unsignedBigInteger('conflicting_booking_id')->nullable();
            $t->json('context')->nullable();                // dump: ics_url, sequence, statuses...
            $t->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_channel_conflicts');
    }
};
