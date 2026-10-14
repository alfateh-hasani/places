<?php

namespace App\Support;

/**
 * Single source of truth for which customer product tour (if any) belongs to the
 * current request's route.
 *
 * Shared by common/parts/tour.blade.php (which loads driver.js + tour.js) and the
 * header "?" replay trigger (which is only rendered when a tour exists). Comment a
 * line out below to disable that page's tour in BOTH places at once.
 */
class ProductTour
{
    public static function currentKey(): ?string
    {
        return match (true) {
            // request()->routeIs('home') => 'home',
            // request()->routeIs('apartments.show')             => 'details',
            // request()->routeIs('web-booking.confirm-booking') => 'booking',
            // Keep 'customer.booking.details' above the account arm so it isn't
            // swallowed by the exact 'customer.booking' match when re-enabled.
            // request()->routeIs('customer.booking.details')    => 'booking-details',
            request()->routeIs('customer.account'),
            request()->routeIs('customer.booking'),
            request()->routeIs('customer.favorite'),
            request()->routeIs('customer.notifications') => 'account',
            default => null,
        };
    }

    public static function existsForCurrentRequest(): bool
    {
        return self::currentKey() !== null;
    }
}
