<?php

namespace App\Listeners;

use App\Events\BookingApproved;
use App\Jobs\OwnerRez\SendBookingToOwnerRezJob;
use Illuminate\Support\Facades\Log;

class SyncBookingToOwnerRez
{
    public function handle(BookingApproved $event): void
    {
        $booking = $event->booking;

        // Check if auto sync is enabled
        if (! config('ownerrez.sync.auto_sync_outbound')) {
            Log::info('OwnerRez outbound sync is disabled', ['booking_id' => $booking->id]);

            return;
        }

        // Check if booking is from external source (don't sync back). 'dashboard' bookings
        // are pushed synchronously by DirectBookingService (so failures can roll back the
        // local booking), so they must not be re-pushed here.
        if (in_array($booking->booking_source, ['ownerrez', 'airbnb', 'booking_com', 'guesty', 'dashboard'])) {
            Log::info('Skipping sync - booking from external source', [
                'booking_id' => $booking->id,
                'source' => $booking->booking_source,
            ]);

            return;
        }

        // Check if apartment is mapped
        $mapping = $booking->apartment->ownerrezMapping;
        if (! $mapping || ! $mapping->sync_enabled) {
            Log::info('Apartment not mapped or sync disabled', [
                'booking_id' => $booking->id,
                'apartment_id' => $booking->apartment_id,
            ]);

            return;
        }

        // Dispatch job to send booking to OwnerRez
        SendBookingToOwnerRezJob::dispatch($booking->id);

        Log::info('Dispatched SendBookingToOwnerRezJob', ['booking_id' => $booking->id]);
    }
}
