<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\CancelSource;
use App\Models\Booking;
use App\Services\Bookings\BookingCancellationService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Covers the staff-facing cancellation lifecycle and the cancel_source source-of-truth.
 *
 * Requires the add_cancel_source_to_bookings migration to be applied. Runs against the
 * configured MySQL DB (no RefreshDatabase) and cleans up its own rows in tearDown,
 * matching this project's testing convention.
 */
class BookingCancellationServiceTest extends TestCase
{
    private array $bookingIds = [];

    protected function tearDown(): void
    {
        if ($this->bookingIds !== []) {
            DB::table('bookings')->whereIn('id', $this->bookingIds)->delete();
        }

        parent::tearDown();
    }

    private function service(): BookingCancellationService
    {
        return app(BookingCancellationService::class);
    }

    public function test_staff_cancellation_of_ownerrez_linked_booking_only_requests(): void
    {
        $booking = $this->makeApprovedBooking(ownerrezBookingId: '18915071');

        $outcome = $this->service()->startStaffCancellation($booking);
        $booking->refresh();

        $this->assertSame('requested_ownerrez', $outcome);
        $this->assertSame(BookingStatus::CancellationRequested->value, $booking->status, 'unit stays held until OwnerRez finalizes');
        $this->assertSame(CancelSource::Staff->value, $booking->cancel_source);
        $this->assertSame('pending', $booking->refund_status);
        $this->assertEqualsWithDelta((float) $booking->final_price, (float) $booking->refund_amount, 0.001);
    }

    public function test_staff_cancellation_of_unmapped_booking_finalizes_locally(): void
    {
        $booking = $this->makeApprovedBooking(ownerrezBookingId: null);

        $outcome = $this->service()->startStaffCancellation($booking);
        $booking->refresh();

        $this->assertSame('canceled_local', $outcome);
        $this->assertSame(BookingStatus::Canceled->value, $booking->status, 'unmapped unit is freed immediately');
        $this->assertSame(CancelSource::Staff->value, $booking->cancel_source);
        $this->assertSame('pending', $booking->refund_status);
    }

    public function test_cancellation_request_without_explicit_source_defaults_to_customer(): void
    {
        // Simulates the customer self-service path (API/web) which only sets the status.
        $booking = $this->makeApprovedBooking(ownerrezBookingId: null);

        $booking->update([
            'status' => BookingStatus::CancellationRequested->value,
            'refund_status' => 'pending',
            'refund_amount' => $booking->final_price,
        ]);
        $booking->refresh();

        $this->assertSame(CancelSource::Customer->value, $booking->cancel_source);
    }

    public function test_reject_reinstates_the_booking(): void
    {
        $booking = $this->makeApprovedBooking(ownerrezBookingId: '18915072');
        $this->service()->startStaffCancellation($booking);

        $this->service()->reject($booking->fresh());
        $booking->refresh();

        $this->assertSame(BookingStatus::Approved->value, $booking->status);
        $this->assertSame('rejected', $booking->refund_status);
    }

    public function test_finalize_local_cancellation_is_idempotent(): void
    {
        $booking = $this->makeApprovedBooking(ownerrezBookingId: null);
        $booking->update(['status' => BookingStatus::Canceled->value]);

        $this->service()->finalizeLocalCancellation($booking->fresh());
        $booking->refresh();

        $this->assertSame(BookingStatus::Canceled->value, $booking->status);
    }

    private function makeApprovedBooking(?string $ownerrezBookingId): Booking
    {
        // Inserted directly (no model events) so only the cancellation transition under
        // test fires hooks; mirrors ProcessBookingRefundTest's convention.
        $bookingId = DB::table('bookings')->insertGetId([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'number_of_booking' => 'CAN'.str_replace('.', '', uniqid('', true)),
            'customer_full_name' => 'Cancellation Test',
            'customer_email' => 'cancel@example.com',
            'apartment_id' => 1,
            'ownerrez_booking_id' => $ownerrezBookingId,
            'check_in' => '2026-05-20',
            'check_out' => '2026-05-21',
            'discount' => 0,
            'total_price' => 400,
            'final_price' => 400,
            'tax' => 0,
            'number_of_nights' => 1,
            'adults_count' => 1,
            'children_count' => 0,
            'status' => BookingStatus::Approved->value,
            'payment_status' => 'paid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->bookingIds[] = $bookingId;

        return Booking::find($bookingId);
    }
}
