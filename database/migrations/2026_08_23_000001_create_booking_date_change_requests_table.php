<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_date_change_requests', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();

            // Snapshot: before → after
            $table->date('original_check_in');
            $table->date('original_check_out');
            $table->date('new_check_in');
            $table->date('new_check_out');

            // Money (all VAT-inclusive, in SAR)
            $table->decimal('original_price', 10, 2)->default(0);
            $table->decimal('new_price', 10, 2)->default(0);
            $table->decimal('price_delta', 10, 2)->default(0); // signed: + surcharge, - refund

            // Lifecycle
            $table->string('status')->default('pending')->index();

            // Difference settlement (surcharge or refund) tracking
            $table->string('gateway_order_id')->nullable();
            $table->string('gateway_reference')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('error')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();

            // Review audit
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('applied_at')->nullable();

            $table->timestamps();

            $table->index(['booking_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_date_change_requests');
    }
};
