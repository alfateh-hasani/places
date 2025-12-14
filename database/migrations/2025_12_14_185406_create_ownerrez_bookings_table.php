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
        Schema::create('ownerrez_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('ownerrez_booking_id')->unique();
            $table->foreignId('local_booking_id')->nullable()->constrained('bookings')->onDelete('set null');
            $table->foreignId('apartment_id')->constrained('apartments')->onDelete('cascade');
            $table->json('raw_data')->nullable();
            $table->enum('sync_status', ['pending', 'synced', 'failed'])->default('pending');
            $table->enum('sync_direction', ['inbound', 'outbound']);
            $table->timestamp('synced_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('ownerrez_booking_id');
            $table->index(['apartment_id', 'sync_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ownerrez_bookings');
    }
};
