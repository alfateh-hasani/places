<?php

namespace App\Services\Coupons;

use App\Models\Coupon;
use Illuminate\Validation\ValidationException;

/**
 * Enforces Coupon.uses_total / uses_customer redemption limits.
 *
 * "Used" is counted from bookings still committed to the discount (pending/approved/booked) —
 * a booking that was later canceled/rejected does not permanently consume the coupon's quota.
 * A limit value of 0 (or less) means "no limit" (the field is required at input time, so 0 is
 * the only way to express "unlimited" without a schema change).
 */
class CouponUsageGuard
{
    private const COUNTED_STATUSES = ['pending', 'approved', 'booked'];

    /**
     * @param  int|null  $customerId  When null, only the total (store-wide) limit is checked —
     *                                used for anonymous/preview price quotes that have no customer yet.
     */
    public function assertAvailable(Coupon $coupon, ?int $customerId): void
    {
        if ((int) $coupon->uses_total > 0 && $this->totalUses($coupon) >= (int) $coupon->uses_total) {
            throw ValidationException::withMessages([
                'coupon_code' => __('api.coupon_usage_limit_reached'),
            ]);
        }

        if ($customerId === null) {
            return;
        }

        if ((int) $coupon->uses_customer > 0 && $this->customerUses($coupon, $customerId) >= (int) $coupon->uses_customer) {
            throw ValidationException::withMessages([
                'coupon_code' => __('api.coupon_customer_usage_limit_reached'),
            ]);
        }
    }

    private function totalUses(Coupon $coupon): int
    {
        return $coupon->bookings()->whereIn('status', self::COUNTED_STATUSES)->count();
    }

    private function customerUses(Coupon $coupon, int $customerId): int
    {
        return $coupon->bookings()
            ->whereIn('status', self::COUNTED_STATUSES)
            ->where('customer_id', $customerId)
            ->count();
    }
}
