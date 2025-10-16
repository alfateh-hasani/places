<?php

namespace App\Jobs;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCheckoutReminderNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $booking;

    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }

    public function handle()
    {
        try {
            // إنشاء إشعار مباشرة في جدول notifications
            $notification = \App\Models\Notification::create([
                'type' => 'customer',
                'customer_id' => $this->booking->customer_id,
                'title_ar' => 'يا زين الإقامة ويا زين الرجعة! وقت الخروج الحين 😔💚',
                'title_en' => 'What a wonderful stay and what a wonderful return! It\'s checkout time now 😔💚',
                'description_ar' => 'نأمل أن نراك قريبًا... فالتجربة الأجمل لم تنتهِ بعد. شاركنا رأيك في Google Map 📍وأقترح التطبيق للأصدقاء , والي اللقاء قريباً',
                'description_en' => 'We hope to see you soon... The most beautiful experience is not over yet. Share your opinion on Google Map 📍 and suggest the app to friends, and see you soon',
                'process_type' => 'notification',
                'process_status' => 'unread',
            ]);

            Log::info('Checkout reminder notification sent successfully', [
                'booking_id' => $this->booking->id,
                'customer_id' => $this->booking->customer_id,
                'notification_id' => $notification->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send checkout reminder notification', [
                'booking_id' => $this->booking->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
