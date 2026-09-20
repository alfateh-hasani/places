<?php

namespace App\Services\BookingUnitTransfer;

use App\Actions\UnitTransfers\ProcessUnitTransferRefund;
use App\Enums\BookingStatus;
use App\Enums\TransferDirection;
use App\Enums\UnitTransferStatus;
use App\Models\Apartment;
use App\Models\Booking;
use App\Models\BookingUnitTransfer;
use App\Services\BookingService;
use App\Services\Locks\LockAccessService;
use App\Services\OwnerRez\OwnerRezBlockParkingService;
use App\Services\OwnerRez\OwnerRezSyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Orchestrates moving a booking to a different apartment (same dates), staff-initiated with
 * customer confirmation. Sibling of {@see \App\Services\DateChangeService}, but the pivot is
 * the apartment instead of the dates.
 *
 * Flow:
 *   1. Staff {@see initiate()} → a PendingCustomer transfer is created; the destination unit
 *      is HELD (via the availability check) but the booking stays on its original unit.
 *   2. Customer {@see confirmByCustomer()} → the move is applied atomically: destination
 *      re-checked under lock, booking re-priced + reassigned, the old OwnerRez booking row is
 *      unlinked (so cancelling it later can't cancel this booking), a new OwnerRez booking is
 *      created on the destination (mapped units), and the passcode is moved to the new lock.
 *
 * Money policy (see {@see TransferPricingCalculator}): same price → nothing; more expensive →
 * absorbed as a discount (no charge); cheaper → staff refund the difference afterwards.
 */
class BookingUnitTransferService
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly TransferPricingCalculator $pricing,
        private readonly LockAccessService $lockAccessService,
        private readonly OwnerRezSyncService $ownerRez,
        private readonly OwnerRezBlockParkingService $parking,
    ) {}

    /**
     * Validate the destination and price the move, without creating anything.
     */
    public function quote(Booking $booking, int $destinationApartmentId): UnitTransferQuote
    {
        $this->assertTransferable($booking);
        $destination = $this->resolveDestination($booking, $destinationApartmentId);

        // Same availability constraints as a new booking, ignoring this booking's own occupancy.
        $this->bookingService->checkAvailability($destination, $booking->check_in, $booking->check_out, $booking->id);

        return $this->pricing->quote($booking, $destination);
    }

    /**
     * Staff start a transfer: create a PendingCustomer request that holds the destination
     * unit while the customer decides. Nothing on the booking changes yet.
     */
    public function initiate(Booking $booking, int $destinationApartmentId): BookingUnitTransfer
    {
        $this->assertTransferable($booking);

        return DB::transaction(function () use ($booking, $destinationApartmentId) {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            $destination = $this->resolveDestination($booking, $destinationApartmentId);
            $this->bookingService->checkAvailability($destination, $booking->check_in, $booking->check_out, $booking->id);

            $quote = $this->pricing->quote($booking, $destination);

            return BookingUnitTransfer::create([
                'booking_id' => $booking->id,
                'from_apartment_id' => $booking->apartment_id,
                'to_apartment_id' => $destination->id,
                'from_ownerrez_booking_id' => $booking->ownerrez_booking_id,
                'check_in' => $booking->check_in,
                'check_out' => $booking->check_out,
                'original_price' => $quote->originalPrice,
                'new_price' => $quote->newPrice,
                'price_delta' => $quote->priceDelta,
                'direction' => $quote->direction->value,
                'refund_amount' => $quote->refundAmount,
                'status' => UnitTransferStatus::PendingCustomer->value,
                'initiated_by' => optional(backpack_user())->id,
            ]);
        });
    }

    /**
     * The customer confirms: apply the move. Atomic (destination re-checked live under lock;
     * OwnerRez create is the last in-transaction step so a failure rolls the move back).
     * Passcode move runs best-effort after commit.
     *
     * @throws ValidationException when the destination is no longer usable (transfer is rejected)
     * @throws \Throwable          when applying fails for a retryable reason (transfer is failed)
     */
    public function confirmByCustomer(BookingUnitTransfer $transfer): Booking
    {
        if ($transfer->status !== UnitTransferStatus::PendingCustomer) {
            throw ValidationException::withMessages(['transfer' => __('api.unit_transfer_not_pending')]);
        }

        $booking = $transfer->booking;
        $oldApartment = $booking->apartment; // capture BEFORE the move (needed for the old lock's credentials)
        $oldOwnerRezBookingId = $booking->ownerrez_booking_id;
        $oldPropertyId = $oldApartment?->ownerrezMapping?->ownerrez_property_id;

        try {
            $this->bookingService->reserveApartment(
                $transfer->to_apartment_id,
                $booking->check_in,
                $booking->check_out,
                $booking->id,
                function (Apartment $destination) use ($transfer, $oldOwnerRezBookingId) {
                    $booking = Booking::whereKey($transfer->booking_id)->lockForUpdate()->firstOrFail();

                    // Defensive re-validation at the commit point.
                    if (! $destination->is_active) {
                        throw ValidationException::withMessages(['apartment_id' => __('api.apartment_not_available')]);
                    }
                    $this->bookingService->validateGuestsCount($destination, $booking->adults_count, $booking->children_count);

                    $quote = $this->pricing->quote($booking, $destination);

                    // Detach the OLD OwnerRez booking from this booking BEFORE creating the new one,
                    // so a later manual cancel of the old booking (webhook) can't cancel this one.
                    if ($oldOwnerRezBookingId) {
                        $this->ownerRez->releaseSupersededOwnerRezBooking($oldOwnerRezBookingId);
                    }

                    $building = $destination->building;

                    $booking->update(array_merge($quote->bookingPrices, [
                        'apartment_id' => $destination->id,
                        'number_of_nights' => $quote->nights,
                        'ownerrez_booking_id' => null, // re-set below if the destination is mapped
                        // The destination is priced at rack (no coupon), so the original coupon no
                        // longer applies to this booking — clear it so the record matches the price.
                        'coupon_id' => null,
                        'coupon_code' => null,
                        'check_in_time' => $building?->check_in_time ?? $booking->check_in_time,
                        'check_out_time' => $building?->check_out_time ?? $booking->check_out_time,
                    ]));

                    // Refresh the money snapshot to the fresh quote (guards against price drift
                    // between initiate and confirm).
                    $transfer->update([
                        'original_price' => $quote->originalPrice,
                        'new_price' => $quote->newPrice,
                        'price_delta' => $quote->priceDelta,
                        'direction' => $quote->direction->value,
                        'refund_amount' => $quote->refundAmount,
                        'confirmed_at' => now(),
                    ]);

                    // Destination mapped → create a NEW OwnerRez booking (there is no "move" API).
                    // LAST fallible step: if it throws, everything above rolls back.
                    if ($this->destinationIsOwnerRezManaged($destination)) {
                        $this->ownerRez->sendBookingToOwnerRez($booking->fresh());
                    }
                }
            );
        } catch (ValidationException $e) {
            // Destination no longer available / capacity lost → the move cannot happen.
            $transfer->update([
                'status' => UnitTransferStatus::Rejected->value,
                'error' => collect($e->errors())->flatten()->first(),
            ]);

            throw $e;
        } catch (\Throwable $e) {
            // Retryable failure (e.g. OwnerRez create threw) — booking untouched (rolled back).
            $message = $this->describeThrowable($e);

            $transfer->update([
                'status' => UnitTransferStatus::Failed->value,
                'error' => $message,
            ]);

            Log::error('Unit transfer confirm failed', [
                'transfer_id' => $transfer->id,
                'booking_id' => $transfer->booking_id,
                'to_apartment_id' => $transfer->to_apartment_id,
                'error' => $message,
            ]);

            throw new \RuntimeException($message, 0, $e);
        }

        $booking = $booking->fresh();

        $transfer->update([
            'status' => UnitTransferStatus::Applied->value,
            'applied_at' => now(),
            'to_ownerrez_booking_id' => $booking->ownerrez_booking_id,
            'refund_status' => $transfer->direction === TransferDirection::Refund
                ? BookingUnitTransfer::REFUND_PENDING
                : null,
        ]);

        // Passcode move runs only AFTER commit — external, best-effort, never rolls back the move.
        $this->movePasscode($booking, $oldApartment);

        // Free the OLD unit in OwnerRez automatically by parking its (now-superseded) booking
        // to a dead past slot — replaces the manual "cancel in OwnerRez" deeplink. Best-effort:
        // on failure the panel still offers the deeplink fallback.
        $this->parkOldOwnerRezBooking($transfer, $oldOwnerRezBookingId, $oldPropertyId);

        return $booking;
    }

    /**
     * Withdraw a transfer (staff cancel, customer decline, or dismiss a failed one), releasing
     * the held unit. Allowed while it is still open OR after it failed to apply.
     */
    public function cancel(BookingUnitTransfer $transfer): void
    {
        if (! $transfer->isOpen() && $transfer->status !== UnitTransferStatus::Failed) {
            throw ValidationException::withMessages(['transfer' => __('api.unit_transfer_cannot_cancel')]);
        }

        $transfer->update(['status' => UnitTransferStatus::Rejected->value]);
    }

    /**
     * Re-attempt a transfer that failed to apply (e.g. a transient DB deadlock or an OwnerRez
     * hiccup). Safe: the failed attempt rolled back, so the booking is still on its original
     * unit. Re-holds the destination and runs the same confirm/apply path.
     */
    public function retry(BookingUnitTransfer $transfer): Booking
    {
        if ($transfer->status !== UnitTransferStatus::Failed) {
            throw ValidationException::withMessages(['transfer' => __('api.unit_transfer_cannot_retry')]);
        }

        $transfer->update(['status' => UnitTransferStatus::PendingCustomer->value, 'error' => null]);

        return $this->confirmByCustomer($transfer->fresh());
    }

    /**
     * Expire stale PendingCustomer transfers, releasing their held destination units.
     */
    public function expireStale(int $minutes): int
    {
        $threshold = now()->subMinutes($minutes);

        return BookingUnitTransfer::where('status', UnitTransferStatus::PendingCustomer->value)
            ->where('updated_at', '<=', $threshold)
            ->update(['status' => UnitTransferStatus::Rejected->value]);
    }

    /**
     * Refund the price difference for a cheaper-unit move (full or partial), idempotently.
     *
     * @return 'approved'|'processing'|'failed'
     */
    public function processRefund(BookingUnitTransfer $transfer, ?float $amount = null): string
    {
        if (! $transfer->needsRefund()) {
            throw ValidationException::withMessages(['transfer' => __('api.unit_transfer_no_refund_due')]);
        }

        $amount = $amount !== null ? round($amount, 2) : $transfer->refundableAmount();

        if ($amount <= 0 || $amount > $transfer->refundableAmount() + 0.001) {
            throw ValidationException::withMessages(['amount' => __('cms.refund_amount')]);
        }

        $transfer->update([
            'refund_amount' => $amount,
            'refund_status' => BookingUnitTransfer::REFUND_PROCESSING,
        ]);

        return app(ProcessUnitTransferRefund::class)->execute($transfer->fresh());
    }

    /**
     * Staff confirm they have cancelled the OLD OwnerRez booking by hand (via the deeplink).
     */
    public function markOldOwnerRezCancelled(BookingUnitTransfer $transfer): void
    {
        $transfer->update(['old_ownerrez_cancelled' => true]);
    }

    /**
     * Re-attempt the automatic parking of the OLD OwnerRez booking (used when the best-effort
     * park during confirm failed, often transiently). On success, flags the transfer so the
     * manual fallback is hidden.
     *
     * @return bool true when parked (or nothing to park); false when it still could not be freed.
     */
    public function retryParkOldBooking(BookingUnitTransfer $transfer): bool
    {
        if (empty($transfer->from_ownerrez_booking_id) || $transfer->old_ownerrez_cancelled) {
            return true;
        }

        $propertyId = $transfer->fromApartment?->ownerrezMapping?->ownerrez_property_id;

        $parked = $this->parking->park(
            (string) $transfer->from_ownerrez_booking_id,
            $propertyId !== null ? (string) $propertyId : null,
            'unit_transfer',
            $transfer->id,
            'unit_transfer',
        );

        if ($parked) {
            $transfer->update(['old_ownerrez_cancelled' => true]);
        }

        return $parked;
    }

    private function assertTransferable(Booking $booking): void
    {
        if ($booking->status !== BookingStatus::Approved->value || $booking->payment_status !== 'paid') {
            throw ValidationException::withMessages(['booking_id' => __('api.booking_cannot_be_transferred')]);
        }

        if ($booking->hasOpenDateChangeRequest()) {
            throw ValidationException::withMessages(['booking_id' => __('api.unit_transfer_resolve_date_change_first')]);
        }

        if ($booking->hasOpenUnitTransfer()) {
            throw ValidationException::withMessages(['booking_id' => __('api.unit_transfer_already_pending')]);
        }

        if (! $booking->isWithinTransferWindow()) {
            throw ValidationException::withMessages(['booking_id' => __('api.unit_transfer_too_late')]);
        }
    }

    private function resolveDestination(Booking $booking, int $destinationApartmentId): Apartment
    {
        if ($destinationApartmentId === (int) $booking->apartment_id) {
            throw ValidationException::withMessages(['apartment_id' => __('api.unit_transfer_same_apartment')]);
        }

        $destination = Apartment::find($destinationApartmentId);

        if (! $destination || ! $destination->is_active) {
            throw ValidationException::withMessages(['apartment_id' => __('api.apartment_not_available')]);
        }

        $this->bookingService->validateGuestsCount($destination, $booking->adults_count, $booking->children_count);

        return $destination;
    }

    private function destinationIsOwnerRezManaged(Apartment $destination): bool
    {
        $mapping = $destination->ownerrezMapping;

        return $mapping && $mapping->sync_enabled;
    }

    /**
     * Build a human-readable error from a failed confirm. For an OwnerRez API failure this digs
     * the real reason out of the response body (which OwnerRez often returns without a `message`
     * key, so the client falls back to "Unknown error"), plus the HTTP status.
     */
    private function describeThrowable(\Throwable $e): string
    {
        if ($e instanceof \App\Exceptions\OwnerRez\OwnerRezApiException) {
            $body = $e->getResponseData();

            $detail = data_get($body, 'message')
                ?? data_get($body, 'Message')
                ?? data_get($body, 'error')
                ?? data_get($body, 'title');

            if ($detail === null && ! empty($body)) {
                $detail = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            $detail = $detail !== null && $detail !== '' ? (string) $detail : $e->getMessage();

            return 'OwnerRez ['.$e->getStatusCode().']: '.\Illuminate\Support\Str::limit($detail, 500);
        }

        return $e->getMessage();
    }

    private function movePasscode(Booking $booking, ?Apartment $oldApartment): void
    {
        if (! $oldApartment) {
            return;
        }

        // Nothing to move if neither the old nor the new apartment has a smart lock.
        if (empty($oldApartment->smart_lock_id) && empty($booking->apartment?->smart_lock_id)) {
            return;
        }

        try {
            $this->lockAccessService->moveForBooking($booking, $oldApartment);
        } catch (\Throwable $e) {
            // Never fail the move on a lock hiccup — the retry command recovers it.
            Log::error('Passcode move failed during unit transfer', [
                'booking_id' => $booking->id,
                'old_apartment_id' => $oldApartment->id,
                'new_apartment_id' => $booking->apartment_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Park the OLD OwnerRez booking (PATCH-move it to a dead past slot) so the source unit is
     * freed automatically — no manual OwnerRez cancel needed. On success the transfer is flagged
     * so the dashboard hides the manual deeplink; on failure the deeplink fallback stays visible.
     */
    private function parkOldOwnerRezBooking(BookingUnitTransfer $transfer, ?string $oldOwnerRezBookingId, ?string $oldPropertyId): void
    {
        if (empty($oldOwnerRezBookingId)) {
            return;
        }

        try {
            $parked = $this->parking->park(
                (string) $oldOwnerRezBookingId,
                $oldPropertyId !== null ? (string) $oldPropertyId : null,
                'unit_transfer',
                $transfer->id,
                'unit_transfer',
            );

            if ($parked) {
                $transfer->update(['old_ownerrez_cancelled' => true]);

                return;
            }

            Log::warning('Could not park old OwnerRez booking after unit transfer — manual deeplink fallback.', [
                'transfer_id' => $transfer->id,
                'ownerrez_booking_id' => $oldOwnerRezBookingId,
            ]);
        } catch (\Throwable $e) {
            Log::error('Parking old OwnerRez booking failed after unit transfer', [
                'transfer_id' => $transfer->id,
                'ownerrez_booking_id' => $oldOwnerRezBookingId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
