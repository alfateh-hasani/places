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
        Schema::table('passcode_retry_attempts', function (Blueprint $table) {
            $table->enum('operation', ['provision', 'revoke'])->default('provision')->after('customer_id');
            $table->index(['booking_id', 'operation']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('passcode_retry_attempts', function (Blueprint $table) {
            $table->dropIndex(['booking_id', 'operation']);
            $table->dropColumn('operation');
        });
    }
};
