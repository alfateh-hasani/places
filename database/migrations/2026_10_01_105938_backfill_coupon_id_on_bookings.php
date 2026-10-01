<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Mobile-app bookings stored only coupon_code, so CouponUsageGuard (which counts via
     * coupon_id) never saw them and coupon usage limits were never enforced. Link past
     * redemptions to their coupon so they count from now on.
     */
    public function up(): void
    {
        // Codes were typed by customers, so match the way MySQL compares them: ignoring
        // case and surrounding spaces.
        $normalize = fn (?string $code): string => mb_strtolower(trim((string) $code));
        $couponIds = DB::table('coupons')->get(['id', 'code'])
            ->mapWithKeys(fn ($coupon) => [$normalize($coupon->code) => $coupon->id]);

        DB::table('bookings')
            ->whereNull('coupon_id')
            ->whereNotNull('coupon_code')
            ->where('coupon_code', '!=', '')
            ->chunkById(500, function ($bookings) use ($couponIds, $normalize) {
                foreach ($bookings as $booking) {
                    if ($couponId = $couponIds[$normalize($booking->coupon_code)] ?? null) {
                        DB::table('bookings')->where('id', $booking->id)->update(['coupon_id' => $couponId]);
                    }
                }
            });
    }

    /**
     * Data backfill: the original null values can't be told apart afterwards, so this is
     * intentionally irreversible.
     */
    public function down(): void
    {
        //
    }
};
