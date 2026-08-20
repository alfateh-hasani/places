<?php

namespace App\Actions\Refunds;

use App\Models\Booking;
use App\Models\Refund;
use App\Services\PaymentMethods\GeideaPayment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Orchestrates an idempotent, async-aware Geidea refund for a cancelled booking.
 *
 * Guarantees:
 *  - Never issues the refund endpoint twice for the same transaction (Refund record + gateway pre-check).
 *  - Resolves to one of: approved (refunded), processing (accepted, awaiting gateway), failed (retryable).
 *
 * Booking.refund_status vocabulary: pending → processing → approved | failed  (rejected = staff denied).
 */
class ProcessBookingRefund
{
    public function __construct(
        private readonly GeideaPayment $gateway,
    ) {}

    public function execute(Booking $booking): string
    {
        $transaction = $booking->transaction;
        $orderId = $transaction?->order_id;

        if (! $transaction || ! $orderId) {
            throw new RuntimeException("Booking {$booking->id} has no gateway order id to refund.");
        }

        $amount = (float) ($booking->refund_amount ?: $booking->final_price);
        if ($amount <= 0) {
            throw new RuntimeException("Booking {$booking->id} has no positive refund amount.");
        }

        $refund = Refund::firstOrCreate(
            ['transaction_id' => $transaction->id],
            [
                'booking_id' => $booking->id,
                'order_id' => $orderId,
                'amount' => $amount,
                'currency' => $transaction->currency ?? 'SAR',
                'status' => Refund::STATUS_PENDING,
            ],
        );

        // Already completed — idempotent no-op.
        if ($refund->status === Refund::STATUS_REFUNDED) {
            return $this->syncBookingRefunded($booking, $refund);
        }

        $lock = Cache::lock('refund:'.$orderId, 30);
        if (! $lock->get()) {
            // Another worker is processing this refund right now.
            return $booking->refund_status ?? 'processing';
        }

        try {
            $refund->increment('attempts');
            $booking->forceFill([
                'refund_attempts' => (int) $booking->refund_attempts + 1,
                'last_refund_attempt_at' => now(),
            ])->save();

            // 1) Pre-check the gateway: was this order already refunded (by a prior attempt or externally)?
            $order = $this->safeGetOrder($orderId);
            if ($this->gatewayShowsRefunded($order)) {
                return $this->finishRefunded($booking, $refund, null, $order);
            }

            // 2) If we already issued a refund before, DO NOT call refund() again — only poll.
            if ($refund->wasIssued()) {
                return $this->markProcessing($booking, $refund, $order);
            }

            // 3) Issue the refund.
            $result = $this->gateway->refund($orderId, $amount);
            $refund->forceFill(['response_payload' => $result])->save();

            if (($result['success'] ?? false) === true) {
                // Confirm via getOrder; if not yet reflected, leave as processing for reconciliation.
                $confirm = $this->safeGetOrder($orderId);

                return $this->gatewayShowsRefunded($confirm)
                    ? $this->finishRefunded($booking, $refund, $result, $confirm)
                    : $this->markProcessing($booking, $refund, $confirm);
            }

            // Hard failure — mark failed and throw so the queue retries with backoff.
            $message = data_get($result, 'error.detailedResponseMessage')
                ?? data_get($result, 'message')
                ?? 'Refund failed';

            $this->markFailed($booking, $refund, (string) $message);

            throw new RuntimeException("Geidea refund failed for booking {$booking->id}: {$message}");
        } finally {
            $lock->release();
        }
    }

    private function finishRefunded(Booking $booking, Refund $refund, ?array $result, ?array $order): string
    {
        $refund->forceFill([
            'status' => Refund::STATUS_REFUNDED,
            'gateway_refund_id' => data_get($result, 'data.refundId')
                ?? data_get($order, 'order.orderId'),
            'error_message' => null,
        ])->save();

        return $this->syncBookingRefunded($booking, $refund);
    }

    private function syncBookingRefunded(Booking $booking, Refund $refund): string
    {
        $booking->forceFill([
            'refund_status' => 'approved', // reuse existing terminal value = refund completed
            'refund_date' => $booking->refund_date ?? now(),
            'refund_reference' => $refund->gateway_refund_id,
            'refund_error' => null,
        ])->save();

        return 'approved';
    }

    private function markProcessing(Booking $booking, Refund $refund, ?array $order): string
    {
        $refund->forceFill(['status' => Refund::STATUS_PROCESSING])->save();
        $booking->forceFill(['refund_status' => 'processing'])->save();

        Log::channel('geidea')->info('Refund awaiting gateway confirmation', [
            'booking_id' => $booking->id,
            'order_id' => $refund->order_id,
        ]);

        return 'processing';
    }

    private function markFailed(Booking $booking, Refund $refund, string $message): void
    {
        $refund->forceFill([
            'status' => Refund::STATUS_FAILED,
            'error_message' => $message,
        ])->save();

        $booking->forceFill([
            'refund_status' => 'failed',
            'refund_error' => $message,
        ])->save();
    }

    /** getOrder can fail on a network blip — never let that abort the flow. */
    private function safeGetOrder(string $orderId): ?array
    {
        $order = $this->gateway->verifyPayment($orderId);

        return is_array($order) ? $order : null;
    }

    /**
     * Defensive multi-signal check for "the gateway shows this order refunded".
     * Verify field names against the Geidea sandbox for your merchant before enabling in production.
     */
    private function gatewayShowsRefunded(?array $order): bool
    {
        if (! is_array($order)) {
            return false;
        }

        $status = strtolower((string) (data_get($order, 'order.detailedStatus')
            ?? data_get($order, 'detailedStatus', '')));

        if (in_array($status, ['refunded', 'partiallyrefunded'], true)) {
            return true;
        }

        $refunded = (float) (data_get($order, 'order.totalRefundedAmount')
            ?? data_get($order, 'order.refundedAmount', 0));

        return $refunded > 0;
    }
}
