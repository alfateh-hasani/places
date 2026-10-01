<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registry of OwnerRez bookings we could not delete via the API (v2 has no delete endpoint),
 * so we "parked" them by PATCH-moving them to a dead 1-day slot in the far past. The row is
 * kept so the tech team can delete the booking in the OwnerRez UI; the daily purge command
 * drops the row once OwnerRez returns 404 for it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ownerrez_parked_bookings', function (Blueprint $table) {
            $table->id();
            // OwnerRez ids are numeric but stored as strings elsewhere in this app (bookings.ownerrez_booking_id).
            $table->string('ownerrez_booking_id')->unique(); // idempotency key
            $table->string('ownerrez_property_id')->nullable();
            $table->string('source_type')->nullable();  // 'booking' | 'unit_transfer' | ...
            $table->unsignedBigInteger('source_id')->nullable(); // local row id that owned the block
            $table->string('reason', 50)->nullable();    // 'unit_transfer' | 'cancelled' | ...
            $table->date('parked_arrival')->nullable();  // the past slot we moved it to
            $table->date('parked_departure')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('ownerrez_property_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ownerrez_parked_bookings');
    }
};
