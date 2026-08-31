<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Models\User;
use App\Notifications\WebPushNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Notifies staff (in-app record + browser web push) when a new customer/direct
 * booking is created. Imported OwnerRez/Airbnb bookings are filtered out at the
 * dispatch site. Does not touch the mobile FCM flow.
 */
class SendNewBookingStaffNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Roles whose users receive new-booking notifications. Spatie roles here span
     * multiple guards, so recipients are resolved by role name (guard-agnostic).
     *
     * @var array<int, string>
     */
    private const RECIPIENT_ROLES = ['Admin', 'خدمة عملاء', 'المالية', 'البرمجة'];

    public function __construct(private readonly Booking $booking)
    {
        //
    }

    public function handle(): void
    {
        $recipients = User::whereHas('roles', fn ($query) => $query->whereIn('name', self::RECIPIENT_ROLES))->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $booking = $this->booking->loadMissing('apartment');
        $apartment = $booking->apartment?->name_ar ?: ('#'.$booking->apartment_id);
        $customer = $booking->customer_full_name ?: ('#'.$booking->customer_id);
        $checkIn = Carbon::parse($booking->check_in)->format('Y-m-d');
        $checkOut = Carbon::parse($booking->check_out)->format('Y-m-d');

        try {
            Notification::send($recipients, new WebPushNotification(
                title: 'حجز جديد '.$booking->number_of_booking,
                body: $customer.' — '.$apartment.' ('.$checkIn.' → '.$checkOut.')',
                actionUrl: backpack_url('booking/'.$booking->id.'/show'),
                type: 'booking.created',
                data: ['booking_id' => $booking->id],
            ));
        } catch (\Throwable $e) {
            Log::error('Failed to send new-booking staff notification', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
