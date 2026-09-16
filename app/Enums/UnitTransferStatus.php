<?php

namespace App\Enums;

/**
 * Lifecycle of a staff-initiated request to move a booking to a different apartment.
 *
 *   PendingCustomer → staff initiated the move; the booking still occupies its ORIGINAL
 *                     unit while the destination unit is HELD, waiting for the customer to
 *                     confirm on their booking page.
 *   Applied         → the customer confirmed; the booking now occupies the NEW unit
 *                     (terminal for the move itself — any cheaper-unit refund is tracked
 *                     separately on {@see \App\Models\BookingUnitTransfer::$refund_status}).
 *   Rejected        → staff cancelled the request, the customer declined, or it expired;
 *                     the booking keeps its original unit (terminal).
 *   Failed          → applying the confirmed move failed (e.g. OwnerRez create threw);
 *                     retryable.
 */
enum UnitTransferStatus: string
{
    case PendingCustomer = 'pending_customer';
    case Applied = 'applied';
    case Rejected = 'rejected';
    case Failed = 'failed';

    /**
     * Statuses that keep a transfer "open" — the destination unit must stay reserved so no
     * other booking can take it while the customer decides.
     *
     * @return array<int, string>
     */
    public static function openValues(): array
    {
        return [
            self::PendingCustomer->value,
        ];
    }

    public function isOpen(): bool
    {
        return in_array($this->value, self::openValues(), true);
    }

    public function label(): string
    {
        return match ($this) {
            self::PendingCustomer => __('cms.unit_transfer_status_pending_customer'),
            self::Applied => __('cms.unit_transfer_status_applied'),
            self::Rejected => __('cms.unit_transfer_status_rejected'),
            self::Failed => __('cms.unit_transfer_status_failed'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PendingCustomer => '#fd7e14',
            self::Applied => '#28a745',
            self::Rejected => '#dc3545',
            self::Failed => '#dc3545',
        };
    }
}
