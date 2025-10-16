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
            $table->enum('passcode_status', ['pending', 'generated', 'failed', 'retry_scheduled'])->default('pending')->after('payment_status');
            $table->timestamp('passcode_generated_at')->nullable()->after('passcode_status');
            $table->text('passcode_error')->nullable()->after('passcode_generated_at');
            $table->integer('passcode_retry_count')->default(0)->after('passcode_error');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'passcode_status',
                'passcode_generated_at',
                'passcode_error',
                'passcode_retry_count'
            ]);
        });
    }
}; 