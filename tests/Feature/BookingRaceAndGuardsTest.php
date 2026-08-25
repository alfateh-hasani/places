<?php

namespace Tests\Feature;

use App\Enums\DateChangeStatus;
use App\Models\Apartment;
use App\Models\Booking;
use App\Models\Coupon;
use App\Services\BookingService;
use App\Services\Coupons\CouponUsageGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Covers three fixes from the booking-flows gap analysis:
 *  - G3: BookingService::reserveApartment() locks the apartment and re-checks availability,
 *        preventing two overlapping bookings from both being created.
 *  - G8: CouponUsageGuard enforces Coupon.uses_total / uses_customer, previously unenforced.
 *  - G4: Booking::canBeCanceled() blocks cancellation while a date-change request is open.
 *
 * Follows this project's convention: no RefreshDatabase, runs against the configured DB,
 * cleans up its own rows in tearDown (see ProcessBookingRefundTest / DeletePendingBookingsCommandTest).
 */
class BookingRaceAndGuardsTest extends TestCase
{
    private int $apartmentId;

    private array $bookingIds = [];

    private array $couponIds = [];

    private array $dateChangeRequestIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Deliberately NOT OwnerRez-linked: reserveApartment() now does a live (uncached) OwnerRez
        // call for mapped apartments, which would make this suite depend on real network/config
        // state. Fall back to any apartment only if none are unmapped in this environment.
        $this->apartmentId = (int) (Apartment::whereDoesntHave('ownerrezMapping')->value('id')
            ?? Apartment::query()->value('id'));
    }

    protected function tearDown(): void
    {
        if ($this->dateChangeRequestIds !== []) {
            DB::table('booking_date_change_requests')->whereIn('id', $this->dateChangeRequestIds)->delete();
        }
        if ($this->couponIds !== []) {
            DB::table('coupons')->whereIn('id', $this->couponIds)->delete();
        }
        if ($this->bookingIds !== []) {
            DB::table('bookings')->whereIn('id', $this->bookingIds)->delete();
        }

        parent::tearDown();
    }

    public function test_reserve_apartment_rejects_overlapping_dates_and_never_runs_the_closure(): void
    {
        $this->createBooking(status: 'approved', checkIn: '2026-05-10', checkOut: '2026-05-15');

        $closureRan = false;

        $this->expectException(ValidationException::class);

        try {
            app(BookingService::class)->reserveApartment(
                $this->apartmentId,
                '2026-05-12',
                '2026-05-14',
                null,
                function () use (&$closureRan) {
                    $closureRan = true;

                    return 'should not happen';
                }
            );
        } finally {
            $this->assertFalse($closureRan, 'The create closure must not run when the apartment is unavailable.');
        }
    }

    public function test_reserve_apartment_runs_the_closure_when_available(): void
    {
        $result = app(BookingService::class)->reserveApartment(
            $this->apartmentId,
            '2026-06-01',
            '2026-06-03',
            null,
            fn (Apartment $apartment) => 'created-for-'.$apartment->id
        );

        $this->assertSame('created-for-'.$this->apartmentId, $result);
    }

    public function test_coupon_usage_guard_blocks_after_total_limit_reached(): void
    {
        $coupon = $this->createCoupon(usesTotal: 1, usesCustomer: 0);
        $this->createBooking(status: 'approved', couponId: $coupon->id, customerId: 501);

        $this->expectException(ValidationException::class);

        app(CouponUsageGuard::class)->assertAvailable($coupon->fresh(), 999);
    }

    public function test_coupon_usage_guard_ignores_canceled_bookings(): void
    {
        $coupon = $this->createCoupon(usesTotal: 1, usesCustomer: 0);
        $this->createBooking(status: 'canceled', couponId: $coupon->id, customerId: 501);

        // The one existing redemption was canceled, so the quota is still free.
        app(CouponUsageGuard::class)->assertAvailable($coupon->fresh(), 999);
        $this->addToAssertionCount(1);
    }

    public function test_coupon_usage_guard_blocks_after_per_customer_limit_reached(): void
    {
        $coupon = $this->createCoupon(usesTotal: 0, usesCustomer: 1);
        $this->createBooking(status: 'approved', couponId: $coupon->id, customerId: 501);

        // Same customer, quota reached.
        try {
            app(CouponUsageGuard::class)->assertAvailable($coupon->fresh(), 501);
            $this->fail('Expected ValidationException for repeat use by the same customer.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('coupon_code', $e->errors());
        }

        // A different customer is unaffected by the per-customer limit.
        app(CouponUsageGuard::class)->assertAvailable($coupon->fresh(), 999);
        $this->addToAssertionCount(1);
    }

    public function test_cannot_cancel_booking_with_open_date_change_request(): void
    {
        $bookingId = $this->createBooking(status: 'approved', paymentStatus: 'paid', checkIn: now()->addDays(30)->toDateString(), checkOut: now()->addDays(32)->toDateString());
        $booking = Booking::find($bookingId);

        $this->assertTrue($booking->canBeCanceled());

        $requestId = DB::table('booking_date_change_requests')->insertGetId([
            'booking_id' => $bookingId,
            'original_check_in' => $booking->check_in,
            'original_check_out' => $booking->check_out,
            'new_check_in' => now()->addDays(35)->toDateString(),
            'new_check_out' => now()->addDays(37)->toDateString(),
            'status' => DateChangeStatus::PendingReview->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->dateChangeRequestIds[] = $requestId;

        $this->assertFalse($booking->fresh()->canBeCanceled());

        DB::table('booking_date_change_requests')->where('id', $requestId)->update(['status' => DateChangeStatus::Rejected->value]);

        $this->assertTrue($booking->fresh()->canBeCanceled());
    }

    private function createBooking(
        string $status,
        string $checkIn = '2026-05-10',
        string $checkOut = '2026-05-15',
        ?int $couponId = null,
        ?int $customerId = null,
        string $paymentStatus = 'paid',
    ): int {
        $bookingId = DB::table('bookings')->insertGetId([
            'number_of_booking' => 'RACE'.str_replace('.', '', uniqid('', true)),
            'customer_id' => $customerId,
            'customer_full_name' => 'Race Guard Test',
            'customer_email' => 'race-guard@example.com',
            'apartment_id' => $this->apartmentId,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'discount' => 0,
            'total_price' => 500,
            'final_price' => 500,
            'tax' => 0,
            'number_of_nights' => 1,
            'adults_count' => 1,
            'children_count' => 0,
            'status' => $status,
            'payment_status' => $paymentStatus,
            'coupon_id' => $couponId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->bookingIds[] = $bookingId;

        return $bookingId;
    }

    private function createCoupon(int $usesTotal, int $usesCustomer): Coupon
    {
        $couponId = DB::table('coupons')->insertGetId([
            'name_ar' => 'كوبون اختبار',
            'name_en' => 'Test coupon',
            'code' => 'RACE'.str_replace('.', '', uniqid('', true)),
            'type' => 'fixed',
            'discount' => 50,
            'uses_total' => $usesTotal,
            'uses_customer' => $usesCustomer,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->couponIds[] = $couponId;

        return Coupon::find($couponId);
    }
}
