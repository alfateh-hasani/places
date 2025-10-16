<?php

namespace App\Jobs;

use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWelcomeNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $customer;

    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    public function handle()
    {
        try {
            // إنشاء إشعار مباشرة في جدول notifications
            $notification = \App\Models\Notification::create([
                'type' => 'customer',
                'customer_id' => $this->customer->id,
                'title_ar' => 'أهلًا بك في عالم الإقامة الفاخرة',
                'title_en' => 'Welcome to the world of luxury accommodation',
                'description_ar' => 'بداية مميّزة مع Places، بيتك أينما كنت.ابدأ حجزك الآن😍',
                'description_en' => 'A distinguished start with Places, your home wherever you are. Start your booking now😍',
                'process_type' => 'notification',
                'process_status' => 'unread',
            ]);

            Log::info('Welcome notification sent successfully', [
                'customer_id' => $this->customer->id,
                'notification_id' => $notification->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send welcome notification', [
                'customer_id' => $this->customer->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
