<?php

namespace App\Listeners;

use App\Events\BookingApproved;
use App\Services\Locks\LockAccessService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Safety-net provisioning trigger for approval paths that don't call
 * LockAccessService::provisionForBooking() directly (e.g. a rejected
 * cancellation reactivating a booking, or an OwnerRez-imported booking
 * created straight into "approved"). Provisioning is idempotent, so this
 * is safe to run alongside the direct call made from
 * BookingService::completeBookingAfterPayment().
 */
class ProvisionSmartLockAccess implements ShouldQueue
{
    /**
     * Only run after the triggering transaction has committed.
     */
    public $afterCommit = true;

    public function __construct(private readonly LockAccessService $locks) {}

    public function handle(BookingApproved $event): void
    {
        $booking = $event->booking;

        if ($booking->payment_status !== 'paid' || $booking->is_airbnb_booking) {
            return;
        }

        try {
            $this->locks->provisionForBooking($booking);
        } catch (Throwable $e) {
            Log::error('ProvisionSmartLockAccess listener failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
