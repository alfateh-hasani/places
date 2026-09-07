<?php

namespace App\Enums;

/**
 * Who initiated a booking cancellation. Stored on Booking.cancel_source so the
 * shared {@see BookingStatus::CancellationRequested} state can be labelled
 * honestly (customer self-service vs. a staff-started cancellation) without
 * needing a separate booking status per source.
 */
enum CancelSource: string
{
    case Customer = 'customer';
    case Staff = 'staff';

    public function label(): string
    {
        return match ($this) {
            self::Customer => __('cms.cancel_source_customer'),
            self::Staff => __('cms.cancel_source_staff'),
        };
    }
}
