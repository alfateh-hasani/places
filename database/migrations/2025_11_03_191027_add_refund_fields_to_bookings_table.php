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
        Schema::table('bookings', function (Blueprint $table) {
            $table->enum('refund_status', ['pending', 'approved', 'rejected'])->nullable()->after('payment_status');
            $table->decimal('refund_amount', 10, 2)->nullable()->after('refund_status');
            $table->timestamp('refund_date')->nullable()->after('refund_amount');
            $table->string('order_id')->nullable()->after('payment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['refund_status', 'refund_amount', 'refund_date', 'order_id']);
        });
    }
};
