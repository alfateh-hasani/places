<?php

namespace App\Jobs;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendBookingConfirmedNotification implements ShouldQueue
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
                'title_ar' => 'تم تأكيد حجزك بنجاح ✨',
                'title_en' => 'Your booking has been confirmed successfully ✨',
                'description_ar' => 'ننتظر وصولك و نجهّز لك إقامة تليق بذوقك الرفيع 🥰 تابع تفاصيل حجزك من التطبيق📱',
                'description_en' => 'We are waiting for your arrival and preparing a stay that suits your refined taste 🥰 Follow your booking details from the app📱',
                'process_type' => 'notification',
                'process_status' => 'unread',
            ]);

            Log::info('Booking confirmed notification sent successfully', [
                'booking_id' => $this->booking->id,
                'customer_id' => $this->booking->customer_id,
                'notification_id' => $notification->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send booking confirmed notification', [
                'booking_id' => $this->booking->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
