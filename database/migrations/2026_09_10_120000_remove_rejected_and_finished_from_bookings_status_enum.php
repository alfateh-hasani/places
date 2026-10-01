<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Retire the 'rejected' and 'finished' values from bookings.status.
 *
 * Both actions were removed from the dashboard, so no new booking can take these
 * statuses. Any legacy rows still holding them are reassigned to 'canceled' first
 * (they are inactive either way — a rejected or finished booking no longer occupies
 * the unit), so MySQL accepts the narrowed ENUM.
 *
 * NOTE: this reassignment is non-destructive (it keeps the booking rows). If you would
 * rather DELETE the old rows instead, run that DELETE before `php artisan migrate` —
 * this UPDATE will then simply have nothing (or fewer rows) to touch.
 */
return new class extends Migration {
    public function up(): void
    {
        DB::table('bookings')
            ->whereIn('status', ['rejected', 'finished'])
            ->update(['status' => 'canceled']);

        DB::statement("ALTER TABLE `bookings` MODIFY `status`
            ENUM('pending','approved','canceled','booked','customer_canceled')
            NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `bookings` MODIFY `status`
            ENUM('pending','approved','canceled','rejected','finished','booked','customer_canceled')
            NULL DEFAULT 'pending'");
    }
};
