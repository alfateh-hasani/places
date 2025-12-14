<?php

namespace App\Jobs\OwnerRez;

use App\Models\Booking;
use App\Services\OwnerRez\OwnerRezSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendBookingToOwnerRezJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public int $bookingId
    ) {}

    public function handle(OwnerRezSyncService $syncService): void
    {
        $booking = Booking::find($this->bookingId);

        if (! $booking) {
            Log::warning('Booking not found for OwnerRez sync', ['booking_id' => $this->bookingId]);

            return;
        }

        // Check if booking should be synced
        if (! config('ownerrez.sync.auto_sync_outbound')) {
            Log::info('OwnerRez outbound sync is disabled', ['booking_id' => $this->bookingId]);

            return;
        }

        // Check if apartment is mapped
        $mapping = $booking->apartment->ownerrezMapping;
        if (! $mapping || ! $mapping->sync_enabled) {
            Log::info('Apartment not mapped to OwnerRez or sync disabled', [
                'booking_id' => $this->bookingId,
                'apartment_id' => $booking->apartment_id,
            ]);

            return;
        }

        // Check if already synced
        if ($booking->ownerrez_booking_id) {
            Log::info('Booking already synced to OwnerRez', [
                'booking_id' => $this->bookingId,
                'ownerrez_booking_id' => $booking->ownerrez_booking_id,
            ]);

            return;
        }

        try {
            Log::info('Sending booking to OwnerRez', ['booking_id' => $this->bookingId]);

            $response = $syncService->sendBookingToOwnerRez($booking);

            Log::info('Successfully sent booking to OwnerRez', [
                'booking_id' => $this->bookingId,
                'ownerrez_booking_id' => $response['id'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send booking to OwnerRez', [
                'booking_id' => $this->bookingId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Send booking to OwnerRez job failed after all retries', [
            'booking_id' => $this->bookingId,
            'error' => $exception->getMessage(),
        ]);
    }
}
