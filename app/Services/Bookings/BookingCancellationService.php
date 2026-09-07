<?php

namespace App\Services\Bookings;

use App\Actions\Refunds\ProcessBookingRefund;
use App\Enums\BookingStatus;
use App\Enums\CancelSource;
use App\Models\Booking;
use App\Models\Refund;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

/**
 * Owns the booking cancellation → refund lifecycle so every entry point (staff
 * dashboard, customer request finalization, OwnerRez webhook) shares one set of
 * safe, idempotent transitions.
 *
 * State model (booking.status × booking.refund_status):
 *   approved ──openRequest──► CancellationRequested [held, refund=pending]
 *   CancellationRequested ──finalizeLocalCancellation / OwnerRez webhook──► Canceled [freed]
 *   CancellationRequested ──reject──► approved [refund=rejected]
 *   Canceled + refund=pending ──refund──► refund=approved | processing | failed
 */
class BookingCancellationService
{
    public function __construct(private ProcessBookingRefund $processRefund) {}

    /**
     * Staff-initiated cancellation from the dashboard.
     *
     * OwnerRez-linked bookings can only be cancelled in OwnerRez (there is no cancel
     * API), so we hold the unit and open a request; the inbound webhook — or a manual
     * force cancel — finalizes it. Unlinked bookings are freed locally immediately.
     *
     * @return 'requested_ownerrez'|'canceled_local'|'noop'
     */
    public function startStaffCancellation(Booking $booking): string
    {
        if ($booking->isCanceled()) {
            return 'noop';
        }

        $this->openRequest($booking, CancelSource::Staff);

        if ($booking->isLinkedToOwnerRez()) {
            return 'requested_ownerrez';
        }

        $this->finalizeLocalCancellation($booking);

        return 'canceled_local';
    }

    /**
     * Move a booking into the "cancellation requested" state: the unit stays held,
     * and for a paid booking a full refund is queued as the default amount. Used by
     * both the staff path and the customer self-service path (which passes Customer).
     */
    public function openRequest(Booking $booking, CancelSource $source): void
    {
        $attributes = [
            'status' => BookingStatus::CancellationRequested->value,
            'cancel_source' => $source->value,
        ];

        if ($booking->payment_status === 'paid' && $booking->refund_status !== 'approved') {
            $attributes['refund_status'] = 'pending';
            $attributes['refund_amount'] = $booking->refund_amount ?: $booking->final_price;
        }

        $booking->update($attributes);
    }

    /**
     * Finalize a cancellation locally (frees the unit). Used by the non-mapped cancel
     * action and by the force-cancel fallback when the OwnerRez webhook never arrives.
     * Idempotent. For a mapped unit this is a local-only override — OwnerRez still
     * holds the booking until it is cancelled there — so we record an audit warning.
     */
    public function finalizeLocalCancellation(Booking $booking): void
    {
        if ($booking->isCanceled()) {
            return;
        }

        if ($booking->isLinkedToOwnerRez()) {
            Log::warning('Booking force-cancelled locally while still linked to OwnerRez', [
                'booking_id' => $booking->id,
                'ownerrez_booking_id' => $booking->ownerrez_booking_id,
                'by' => optional(backpack_user())->id,
            ]);
        }

        $booking->update(['status' => BookingStatus::Canceled->value]);

        // حرّرت الوحدة — امسح كاش تقويم OwnerRez وأعد تسخينه ليظهر التوفّر مباشرةً
        // (لا شيء لغير المربوطة بـ OwnerRez؛ تقويمها يُقرأ محلياً مباشرةً).
        app(\App\Services\OwnerRez\OwnerRezSyncService::class)->invalidateCacheForBooking($booking);
    }

    /**
     * Reject a cancellation request and reinstate the booking. Safe against double
     * booking: the unit stayed held for the whole review, so nothing else took it.
     */
    public function reject(Booking $booking): void
    {
        $booking->update([
            'status' => BookingStatus::Approved->value,
            'refund_status' => 'rejected',
        ]);
    }

    /**
     * Process the refund for a finalized cancellation (full or partial).
     *
     * Routing by how the booking was paid:
     *  - Paid through Geidea (web/mobile → transaction has a gateway order_id): run the
     *    real, idempotent gateway refund.
     *  - Direct/dashboard bookings (bank transfer → no gateway order_id): there is no
     *    gateway to call, so just record the refund as done (staff settle the money
     *    out-of-band) while still writing an audit row in the refunds table.
     *
     * Resilient: a Geidea timeout/decline never bubbles a 500 — it returns an outcome.
     *
     * @return 'approved'|'processing'|'failed'
     */
    public function refund(Booking $booking, float $amount): string
    {
        $booking->update(['refund_amount' => $amount]);

        $transaction = $booking->transaction;

        if ($transaction && $transaction->order_id) {
            try {
                return $this->processRefund->execute($booking->fresh());
            } catch (\Throwable $e) {
                Log::error('Booking refund execution failed', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);

                return 'failed';
            }
        }

        return $this->markRefundedManually($booking, $transaction, $amount);
    }

    /**
     * Record a refund as completed without any gateway call — for direct/dashboard
     * (bank-transfer) bookings where the money is settled manually. Writes the same
     * audit row the Refunds tracker uses, flagged as manual.
     */
    private function markRefundedManually(Booking $booking, ?Transaction $transaction, float $amount): string
    {
        if ($transaction) {
            $refund = Refund::firstOrCreate(
                ['transaction_id' => $transaction->id],
                [
                    'booking_id' => $booking->id,
                    'order_id' => $transaction->order_id,
                    'amount' => $amount,
                    'currency' => $transaction->currency ?? 'SAR',
                    'status' => Refund::STATUS_REFUNDED,
                ],
            );

            $refund->forceFill([
                'amount' => $amount,
                'status' => Refund::STATUS_REFUNDED,
                'gateway_refund_id' => null,
                'error_message' => null,
                'response_payload' => [
                    'manual' => true,
                    'by' => optional(backpack_user())->id,
                ],
            ])->save();
        } else {
            Log::warning('Manual refund marked without a transaction — no audit row written', [
                'booking_id' => $booking->id,
            ]);
        }

        $booking->forceFill([
            'refund_status' => 'approved',
            'refund_amount' => $amount,
            'refund_date' => $booking->refund_date ?? now(),
            'refund_error' => null,
        ])->save();

        return 'approved';
    }
}
