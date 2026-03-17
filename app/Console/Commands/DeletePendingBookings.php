<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Command;

class DeletePendingBookings extends Command
{
    protected $signature = 'delete:pending-bookings';

    protected $description = 'Delete pending bookings';

    public function handle(): void
    {
        Booking::query()
            ->where('status', 'pending')
            ->where('created_at', '<', now()->subMinutes(5))
            ->delete();
    }
}
