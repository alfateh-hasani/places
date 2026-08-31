<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Support manual "direct booking" from the dashboard paid by bank transfer.
 *
 * Raw ALTER ... MODIFY is used for the enums because the live transactions/bookings
 * schema was altered outside tracked migrations (e.g. payment_gateway already carries
 * 'geidea', which no migration adds), so column definitions are pinned to the live
 * state observed via SHOW COLUMNS rather than re-derived from earlier migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE transactions MODIFY payment_gateway ENUM('tap','tabby','tamara','qitaf','geidea','bank_transfer') NOT NULL DEFAULT 'tap'");
        DB::statement("ALTER TABLE transactions MODIFY platform ENUM('web','api','dashboard') NOT NULL DEFAULT 'web'");

        if (! Schema::hasColumn('transactions', 'transfer_number')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->string('transfer_number')->nullable()->after('order_id');
            });
        }

        DB::statement("ALTER TABLE bookings MODIFY booking_source ENUM('web','android','ios','ownerrez','airbnb','booking_com','guesty','other','dashboard') NULL DEFAULT 'web'");
        DB::statement("ALTER TABLE bookings MODIFY payment_method_code ENUM('tap','tabby','geidea','airbnb','bank_transfer') NULL DEFAULT NULL");
    }

    public function down(): void
    {
        if (Schema::hasColumn('transactions', 'transfer_number')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn('transfer_number');
            });
        }

        DB::statement("ALTER TABLE transactions MODIFY payment_gateway ENUM('tap','tabby','tamara','qitaf','geidea') NOT NULL DEFAULT 'tap'");
        DB::statement("ALTER TABLE transactions MODIFY platform ENUM('web','api') NOT NULL DEFAULT 'web'");
        DB::statement("ALTER TABLE bookings MODIFY booking_source ENUM('web','android','ios','ownerrez','airbnb','booking_com','guesty','other') NULL DEFAULT 'web'");
        DB::statement("ALTER TABLE bookings MODIFY payment_method_code ENUM('tap','tabby','geidea','airbnb') NULL DEFAULT NULL");
    }
};
