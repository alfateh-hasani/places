<?php

namespace App\Actions\UnitTransfers;

use App\Models\BookingUnitTransfer;
use App\Services\PaymentMethods\GeideaPayment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Idempotent, async-aware Geidea partial refund of a unit-transfer price difference
 * (used only when the destination unit is cheaper than what the customer paid).
 *
 * Mirrors ProcessDateChangeRefund exactly, but operates on a BookingUnitTransfer (not the
 * booking's refund_* columns) so a transfer refund never collides with a cancellation
 * refund on the same transaction.
 *
 * Resolves to one of: approved (refunded) · processing (accepted, awaiting gateway) · failed.
 */
class ProcessUnitTransferRefund
{
    public function __construct(private readonly GeideaPayment $gateway) {}

    public function execute(BookingUnitTransfer $transfer): string
    {
        $orderId = $transfer->gateway_order_id ?: $transfer->booking?->transaction?->order_id;

        if (! $orderId) {
            $this->markFailed($transfer, 'No gateway order id to refund.');

            return 'failed';
        }

        $amount = round($transfer->refundableAmount(), 2);
        if ($amount <= 0) {
            $this->markFailed($transfer, 'No positive refund amount.');

            return 'failed';
        }

        $transfer->forceFill(['gateway_order_id' => $orderId])->save();

        // Already refunded — idempotent no-op.
        if ($transfer->refund_status === BookingUnitTransfer::REFUND_APPROVED && $transfer->gateway_reference) {
            return 'approved';
        }

        $lock = Cache::lock('unit-transfer-refund:'.$orderId, 30);
        if (! $lock->get()) {
            return 'processing';
        }

        try {
            $transfer->increment('attempts');
            $transfer->forceFill(['last_attempt_at' => now()])->save();

            // 1) Pre-check: did the gateway already refund this order (prior attempt / external)?
            $order = $this->safeGetOrder($orderId);
            if ($this->gatewayShowsRefunded($order)) {
                return $this->finishRefunded($transfer, null, $order);
            }

            // 2) A prior attempt already issued a successful refund → only poll, never re-issue.
            if ($this->wasIssued($transfer)) {
                return $this->markProcessing($transfer);
            }

            // 3) Issue the refund.
            try {
                $result = $this->gateway->refund($orderId, $amount);
            } catch (\Throwable $e) {
                Log::channel('geidea')->warning('Unit-transfer refund gateway call failed (network) — marking processing', [
                    'transfer_id' => $transfer->id,
                    'order_id' => $orderId,
                    'error' => $e->getMessage(),
                ]);

                return $this->markProcessing($transfer);
            }

            $transfer->forceFill(['response_payload' => $result])->save();

            if (($result['success'] ?? false) === true) {
                $confirm = $this->safeGetOrder($orderId);

                return $this->gatewayShowsRefunded($confirm)
                    ? $this->finishRefunded($transfer, $result, $confirm)
                    : $this->markProcessing($transfer);
            }

            $message = data_get($result, 'error.detailedResponseMessage')
                ?? data_get($result, 'error.responseMessage')
                ?? data_get($result, 'message')
                ?? 'Refund failed';

            $this->markFailed($transfer, (string) $message);

            return 'failed';
        } finally {
            $lock->release();
        }
    }

    private function wasIssued(BookingUnitTransfer $transfer): bool
    {
        return data_get($transfer->response_payload, 'success') === true;
    }

    private function finishRefunded(BookingUnitTransfer $transfer, ?array $result, ?array $order): string
    {
        $transfer->forceFill([
            'refund_status' => BookingUnitTransfer::REFUND_APPROVED,
            'gateway_reference' => data_get($result, 'data.refundId') ?? data_get($order, 'order.orderId'),
            'error' => null,
        ])->save();

        return 'approved';
    }

    private function markProcessing(BookingUnitTransfer $transfer): string
    {
        $transfer->forceFill(['refund_status' => BookingUnitTransfer::REFUND_PROCESSING])->save();

        Log::channel('geidea')->info('Unit-transfer refund awaiting gateway confirmation', [
            'transfer_id' => $transfer->id,
            'order_id' => $transfer->gateway_order_id,
        ]);

        return 'processing';
    }

    private function markFailed(BookingUnitTransfer $transfer, string $message): void
    {
        $transfer->forceFill([
            'refund_status' => BookingUnitTransfer::REFUND_FAILED,
            'error' => $message,
        ])->save();
    }

    private function safeGetOrder(string $orderId): ?array
    {
        try {
            $order = $this->gateway->verifyPayment($orderId);

            return is_array($order) ? $order : null;
        } catch (\Throwable $e) {
            Log::channel('geidea')->warning('getOrder failed (network) — treating as inconclusive', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

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
