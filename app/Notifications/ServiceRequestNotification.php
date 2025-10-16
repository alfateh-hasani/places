<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\ServiceBooking;
use App\Models\User;

class ServiceRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $serviceBooking;
    protected $customer;

    /**
     * Create a new notification instance.
     */
    public function __construct(ServiceBooking $serviceBooking, $customer)
    {
        $this->serviceBooking = $serviceBooking;
        $this->customer = $customer;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title_ar' => 'طلب خدمة جديد',
            'title_en' => 'New Service Request',
            'description_ar' => "تم طلب خدمة {$this->serviceBooking->service->name_ar} من العميل {$this->customer->name} في العقار {$this->serviceBooking->apartment->name_ar}",
            'description_en' => "Service {$this->serviceBooking->service->name_en} requested by customer {$this->customer->name} for apartment {$this->serviceBooking->apartment->name_en}",
            'type' => 'admin',
            'process_type' => 'service_request',
            'process_status' => 'pending',
            'booking_id' => $this->serviceBooking->booking_id,
            'service_booking_id' => $this->serviceBooking->id,
            'apartment_id' => $this->serviceBooking->apartment_id,
            'customer_id' => $this->serviceBooking->customer_id,
        ];
    }
}
