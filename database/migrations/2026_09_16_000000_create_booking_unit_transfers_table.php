<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_unit_transfers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();

            // A unit that was ever a transfer endpoint keeps its audit meaning — block deletion.
            $table->foreignId('from_apartment_id')->constrained('apartments')->restrictOnDelete();
            $table->foreignId('to_apartment_id')->constrained('apartments')->restrictOnDelete();

            // OwnerRez ids: the old booking (to be manually cancelled) and the new one (created on confirm).
            $table->string('from_ownerrez_booking_id')->nullable();
            $table->string('to_ownerrez_booking_id')->nullable();

            // Stay dates are unchanged by a unit transfer — snapshot them for the audit trail.
            $table->date('check_in');
            $table->date('check_out');

            // Money snapshot. original = what the customer paid; new = destination price for the
            // same dates; delta is signed (+ costs more → absorbed as discount, − costs less → refund).
            $table->decimal('original_price', 10, 2)->default(0);
            $table->decimal('new_price', 10, 2)->default(0);
            $table->decimal('price_delta', 10, 2)->default(0);
            $table->string('direction')->default('even'); // even | surcharge | refund

            $table->string('status')->default('pending_customer')->index();

            // Cheaper-unit difference refund (staff-driven, gateway-backed) — kept here so it
            // never collides with a cancellation refund on the same transaction.
            $table->string('refund_status')->nullable(); // pending | processing | approved | failed
            $table->decimal('refund_amount', 10, 2)->default(0);
            $table->string('gateway_order_id')->nullable();
            $table->string('gateway_reference')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('error')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();

            // Whether staff confirmed the OLD OwnerRez booking was cancelled by hand (drives the deeplink).
            $table->boolean('old_ownerrez_cancelled')->default(false);

            $table->unsignedBigInteger('initiated_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('applied_at')->nullable();

            $table->timestamps();

            $table->index(['booking_id', 'status']);
            // Fast lookup for the availability hold: "is any open transfer targeting this unit?"
            $table->index(['to_apartment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_unit_transfers');
    }
};
