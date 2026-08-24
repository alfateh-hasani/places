<?php

namespace App\Actions\DateChanges;

use App\Enums\DateChangeStatus;
use App\Models\DateChangeRequest;
use App\Services\PaymentMethods\GeideaPayment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Idempotent, async-aware Geidea partial refund of a date-change price difference.
 *
 * Mirrors the guarantees of ProcessBookingRefund but operates on a DateChangeRequest
 * (not the booking's refund_* columns) so cancellation refunds and date-change refunds
 * never collide on the same transaction record.
 *
 * Assumes the caller has already applied the new dates. Resolves to one of:
 *   applied (refunded) · processing (accepted, awaiting gateway) · failed (retryable).
 */
class ProcessDateChangeRefund
{
    public function __construct(
        private readonly GeideaPayment $gateway,
    ) {}

    public function execute(DateChangeRequest $request): string
    {
        $orderId = $request->gateway_order_id ?: $request->booking?->transaction?->order_id;

        // No gateway order to refund against (e.g. booking never paid through the gateway) — this is
        // unrecoverable, so surface it as a clear failure instead of leaving the request stuck.
        if (! $orderId) {
            $this->markFailed($request, 'No gateway order id to refund.');

            return 'failed';
        }

        $amount = round($request->refundableAmount(), 2);
        if ($amount <= 0) {
            $this->markFailed($request, 'No positive refund amount.');

            return 'failed';
        }

        $request->forceFill(['gateway_order_id' => $orderId])->save();

        // Already refunded — idempotent no-op.
        if ($request->status === DateChangeStatus::Applied->value && $request->gateway_reference) {
            return 'applied';
        }

        $lock = Cache::lock('date-change-refund:'.$orderId, 30);
        if (! $lock->get()) {
            // Another worker is settling this refund right now.
            return 'processing';
        }

        try {
            $request->increment('attempts');
            $request->forceFill(['last_attempt_at' => now()])->save();

            // 1) Pre-check: did the gateway already refund this order (prior attempt / external)?
            $order = $this->safeGetOrder($orderId);
            if ($this->gatewayShowsRefunded($order)) {
                return $this->finishRefunded($request, null, $order);
            }

            // 2) If a prior attempt already issued a successful refund, only poll — never re-issue.
            if ($this->wasIssued($request)) {
                return $this->markProcessing($request);
            }

            // 3) Issue the refund.
            try {
                $result = $this->gateway->refund($orderId, $amount);
            } catch (\Throwable $e) {
                Log::channel('geidea')->warning('Date-change refund gateway call failed (network) — marking processing', [
                    'request_id' => $request->id,
                    'order_id' => $orderId,
                    'error' => $e->getMessage(),
                ]);

                return $this->markProcessing($request);
            }

            $request->forceFill(['response_payload' => $result])->save();

            if (($result['success'] ?? false) === true) {
                $confirm = $this->safeGetOrder($orderId);

                return $this->gatewayShowsRefunded($confirm)
                    ? $this->finishRefunded($request, $result, $confirm)
                    : $this->markProcessing($request);
            }

            $message = data_get($result, 'error.detailedResponseMessage')
                ?? data_get($result, 'error.responseMessage')
                ?? data_get($result, 'message')
                ?? 'Refund failed';

            $this->markFailed($request, (string) $message);

            return 'failed';
        } finally {
            $lock->release();
        }
    }

    private function wasIssued(DateChangeRequest $request): bool
    {
        return data_get($request->response_payload, 'success') === true;
    }

    private function finishRefunded(DateChangeRequest $request, ?array $result, ?array $order): string
    {
        $request->forceFill([
            'status' => DateChangeStatus::Applied->value,
            'gateway_reference' => data_get($result, 'data.refundId') ?? data_get($order, 'order.orderId'),
            'error' => null,
        ])->save();

        return 'applied';
    }

    private function markProcessing(DateChangeRequest $request): string
    {
        $request->forceFill(['status' => DateChangeStatus::Processing->value])->save();

        Log::channel('geidea')->info('Date-change refund awaiting gateway confirmation', [
            'request_id' => $request->id,
            'order_id' => $request->gateway_order_id,
        ]);

        return 'processing';
    }

    private function markFailed(DateChangeRequest $request, string $message): void
    {
        $request->forceFill([
            'status' => DateChangeStatus::Failed->value,
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
