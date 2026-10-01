<?php

namespace App\Enums;

/**
 * The price relationship between the destination unit and what the customer already paid.
 *
 *   Even      → destination costs the same → no money movement.
 *   Surcharge → destination costs MORE → the difference is absorbed as a system discount
 *               (the customer is never charged extra for a staff-initiated move).
 *   Refund    → destination costs LESS → staff refund the difference from the dashboard
 *               after the customer confirms.
 */
enum TransferDirection: string
{
    case Even = 'even';
    case Surcharge = 'surcharge';
    case Refund = 'refund';

    /**
     * Resolve the direction from a signed price delta (new − original), using a
     * one-cent threshold so float noise never reads as a spurious surcharge/refund.
     */
    public static function fromDelta(float $delta): self
    {
        return match (true) {
            $delta > 0.009 => self::Surcharge,
            $delta < -0.009 => self::Refund,
            default => self::Even,
        };
    }

    public function isRefund(): bool
    {
        return $this === self::Refund;
    }

    public function isSurcharge(): bool
    {
        return $this === self::Surcharge;
    }
}
