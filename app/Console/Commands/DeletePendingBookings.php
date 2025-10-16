<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DeletePendingBookings extends Command
{
    protected $signature = 'delete:pending-bookings';
    protected $description = 'Delete pending bookings';

    public function handle()
    {
        $pendingBookings = Booking::where('status', 'pending')->where('created_at', '<', now()->subMinutes(20))->get();
        foreach ($pendingBookings as $booking) {
            $booking->delete();
        }
    }
}