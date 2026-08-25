<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE bookings MODIFY refund_status ENUM('pending','processing','approved','rejected','failed') NULL");

        Schema::table('bookings', function (Blueprint $table): void {
            $table->string('refund_reference')->nullable()->after('refund_date');
            $table->text('refund_error')->nullable()->after('refund_reference');
            $table->unsignedTinyInteger('refund_attempts')->default(0)->after('refund_error');
            $table->timestamp('last_refund_attempt_at')->nullable()->after('refund_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn(['refund_reference', 'refund_error', 'refund_attempts', 'last_refund_attempt_at']);
        });

        DB::statement("ALTER TABLE bookings MODIFY refund_status ENUM('pending','approved','rejected') NULL");
    }
};
