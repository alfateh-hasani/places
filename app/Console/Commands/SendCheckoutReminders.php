<?php

namespace App\Console\Commands;

use App\Jobs\SendCheckoutReminderNotification;
use App\Models\Booking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendCheckoutReminders extends Command
{
    protected $signature = 'notifications:send-checkout-reminders';
    protected $description = 'Send checkout reminder notifications for bookings ending today';

    public function handle()
    {
        $this->info('Starting checkout reminder notifications...');

        // البحث عن الحجوزات التي تنتهي اليوم
        $bookings = Booking::where('status', 'approved')
            ->whereDate('check_out', today())
            ->where('checkout_reminder_sent', false)
            ->with(['customer', 'apartment'])
            ->get();

        $this->info("Found {$bookings->count()} bookings ending today");

        foreach ($bookings as $booking) {
            try {
                // إرسال إشعار الخروج
                SendCheckoutReminderNotification::dispatch($booking);

                // تحديث حالة الإشعار
                $booking->update(['checkout_reminder_sent' => true]);

                $this->info("Checkout reminder sent for booking #{$booking->number_of_booking}");

            } catch (\Exception $e) {
                $this->error("Failed to send checkout reminder for booking #{$booking->number_of_booking}: {$e->getMessage()}");
                Log::error('Checkout reminder failed', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->info('Checkout reminder notifications completed');
    }
}
