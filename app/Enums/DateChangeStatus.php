<?php

namespace App\Enums;

/**
 * Lifecycle of a customer request to change a booking's dates.
 *
 *   pending          → transient, just created
 *   AwaitingPayment  → surcharge (delta > 0): waiting for the customer to pay the difference
 *   PendingReview    → refund (delta < 0): waiting for staff to review before refunding
 *   Processing       → the difference (refund) is being settled with the gateway
 *   Applied          → dates changed successfully (terminal success)
 *   Rejected         → staff rejected the request (terminal); booking keeps its original dates
 *   Failed           → settlement failed (retryable)
 */
enum DateChangeStatus: string
{
    case Pending = 'pending';
    case AwaitingPayment = 'awaiting_payment';
    case PendingReview = 'pending_review';
    case Processing = 'processing';
    case Applied = 'applied';
    case Rejected = 'rejected';
    case Failed = 'failed';

    /**
     * Statuses that keep a request "open" — the booking is still committed to the
     * change and the requested window must stay reserved.
     *
     * @return array<int, string>
     */
    public static function openValues(): array
    {
        return [
            self::AwaitingPayment->value,
            self::PendingReview->value,
            self::Processing->value,
            self::Pending->value,
        ];
    }

    public function isOpen(): bool
    {
        return in_array($this->value, self::openValues(), true);
    }

    /**
     * Statuses the customer should still see/act on in their booking page:
     * pre-acceptance only. Once accepted (processing/applied) or terminal,
     * the request is hidden — the difference settlement is handled internally.
     *
     * @return array<int, string>
     */
    public static function customerVisibleValues(): array
    {
        return [
            self::AwaitingPayment->value,
            self::PendingReview->value,
            self::Pending->value,
        ];
    }
}
