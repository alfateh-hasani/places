<?php

namespace App\Enums;

/**
 * Single source of truth for a booking's lifecycle status.
 *
 *   Pending               → created, awaiting payment
 *   Approved              → paid & confirmed (the active/live state)
 *   Booked                → imported/synced reservation holding the unit
 *   Canceled              → cancellation finalized; the unit is FREED
 *   CancellationRequested → a cancellation is requested and under review; the unit
 *                           stays HELD until it's finalized. Raised either by the
 *                           customer (self-service) or started by staff — tell them
 *                           apart via Booking.cancel_source (see {@see CancelSource}).
 *
 * Backing value note: CancellationRequested is stored as 'customer_canceled' for
 * backward compatibility with the mobile app and existing rows. Do NOT change the
 * stored value without a coordinated data migration + mobile release.
 */
enum BookingStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Booked = 'booked';
    case Canceled = 'canceled';
    case CancellationRequested = 'customer_canceled';

    /**
     * Human label. Source-agnostic — for the cancellation-request state prefer
     * {@see self::labelForBooking()} so the wording reflects who started it.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => __('cms.status_pending'),
            self::Approved => __('cms.status_approved'),
            self::Booked => __('cms.status_booked'),
            self::Canceled => __('cms.status_canceled'),
            self::CancellationRequested => __('cms.status_customer_canceled'),
        };
    }

    /**
     * Badge/text colour as a hex value (used by admin badges and calendars).
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => '#6c757d',
            self::Approved => '#28a745',
            self::Booked => '#007bff',
            self::Canceled => '#dc3545',
            self::CancellationRequested => '#fd7e14',
        };
    }

    /**
     * Line-awesome icon class.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Pending => 'la-clock',
            self::Approved => 'la-check-circle',
            self::Booked => 'la-calendar-check',
            self::Canceled => 'la-ban',
            self::CancellationRequested => 'la-user-times',
        };
    }

    /**
     * Rendered admin badge (inline-styled so it survives any Bootstrap theme).
     * Pass a label override to show source-aware wording for the request state.
     */
    public function badge(?string $labelOverride = null): string
    {
        $label = $labelOverride ?? $this->label();

        return "<span class='badge' style='background-color:{$this->color()};color:#fff;padding:.45em .7em;font-size:.82rem;font-weight:600;border-radius:6px;'>"
            ."<i class='la {$this->icon()}' style='font-size:1.05rem;vertical-align:-2px;'></i> {$label}</span>";
    }

    /**
     * Statuses that OCCUPY a unit for availability/overlap checks. A cancellation
     * request keeps the unit held until it's finalized (→ Canceled frees it).
     *
     * @return array<int, string>
     */
    public static function occupying(): array
    {
        return [
            self::Pending->value,
            self::Approved->value,
            self::Booked->value,
            self::CancellationRequested->value,
        ];
    }

    /**
     * The cancellation "in progress / needs staff action" states — a request that
     * isn't yet finalized, or a finalized cancel still awaiting its refund decision.
     *
     * @return array<int, string>
     */
    public static function cancellationWorkflow(): array
    {
        return [
            self::CancellationRequested->value,
            self::Canceled->value,
        ];
    }

    /**
     * All backing values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * value => label map for dropdowns and filters.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    public function is(self $other): bool
    {
        return $this === $other;
    }

    /**
     * Terminal states — a booking here must not be re-activated by a manual status
     * change (Canceled frees the unit; re-activating would risk a double-booking with
     * no availability re-check). Booked is import-only.
     */
    public function isLeaf(): bool
    {
        return in_array($this, [self::Canceled, self::Booked], true);
    }

    /**
     * Allowed manual dashboard actions for the current status — the single source of
     * truth for both the row dropdown and the changeStatus() guard.
     *
     *   Pending  → confirm (Geidea-first, else bank transfer)
     *   Approved → cancel (guided cancel→refund)
     *   others   → none (CancellationRequested uses the "Manage cancellation" modal;
     *              leaf states get nothing)
     *
     * @return array<int, string>  subset of: confirm, cancel
     */
    public function manualActions(): array
    {
        return match ($this) {
            self::Pending => ['confirm'],
            self::Approved => ['cancel'],
            default => [],
        };
    }
}
