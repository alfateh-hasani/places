<?php

namespace Tests\Feature;

use App\Actions\Refunds\ProcessBookingRefund;
use App\Models\Booking;
use App\Services\PaymentMethods\GeideaPayment;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

/**
 * Requires the Phase 2 migrations to be applied (refunds table + booking refund columns).
 * Runs against the configured MySQL DB (no RefreshDatabase) and cleans up in tearDown,
 * matching this project's testing convention.
 */
class ProcessBookingRefundTest extends TestCase
{
    private array $bookingIds = [];

    private array $transactionIds = [];

    protected function tearDown(): void
    {
        if ($this->transactionIds !== []) {
            DB::table('refunds')->whereIn('transaction_id', $this->transactionIds)->delete();
            DB::table('transactions')->whereIn('id', $this->transactionIds)->delete();
        }
        if ($this->bookingIds !== []) {
            DB::table('bookings')->whereIn('id', $this->bookingIds)->delete();
        }

        parent::tearDown();
    }

    public function test_idempotent_when_gateway_already_refunded_does_not_call_refund(): void
    {
        $booking = $this->makeCanceledBooking('order-idem', 500.0);

        $gateway = Mockery::mock(GeideaPayment::class);
        $gateway->shouldReceive('verifyPayment')->once()->with('order-idem')
            ->andReturn($this->refundedOrder('order-idem', 500));
        $gateway->shouldNotReceive('refund'); // critical: never double-refund
        $this->app->instance(GeideaPayment::class, $gateway);

        $outcome = app(ProcessBookingRefund::class)->execute($booking);

        $this->assertSame('approved', $outcome);
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'refund_status' => 'approved']);
        $this->assertDatabaseHas('refunds', ['transaction_id' => $booking->transaction_id, 'status' => 'refunded']);
    }

    public function test_successful_refund_confirmed_marks_approved(): void
    {
        $booking = $this->makeCanceledBooking('order-ok', 500.0);

        $gateway = Mockery::mock(GeideaPayment::class);
        $gateway->shouldReceive('verifyPayment')
            ->andReturn($this->paidOrder('order-ok', 500), $this->refundedOrder('order-ok', 500));
        $gateway->shouldReceive('refund')->once()->with('order-ok', 500.0)
            ->andReturn(['success' => true, 'data' => ['refundId' => 'r-1']]);
        $this->app->instance(GeideaPayment::class, $gateway);

        $outcome = app(ProcessBookingRefund::class)->execute($booking);

        $this->assertSame('approved', $outcome);
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'refund_status' => 'approved']);
    }

    public function test_accepted_but_unconfirmed_marks_processing(): void
    {
        $booking = $this->makeCanceledBooking('order-async', 500.0);

        $gateway = Mockery::mock(GeideaPayment::class);
        // pre-check paid, post-check still paid (gateway not yet reflecting the refund)
        $gateway->shouldReceive('verifyPayment')
            ->andReturn($this->paidOrder('order-async', 500), $this->paidOrder('order-async', 500));
        $gateway->shouldReceive('refund')->once()->andReturn(['success' => true, 'data' => []]);
        $this->app->instance(GeideaPayment::class, $gateway);

        $outcome = app(ProcessBookingRefund::class)->execute($booking);

        $this->assertSame('processing', $outcome);
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'refund_status' => 'processing']);
        $this->assertDatabaseHas('refunds', ['transaction_id' => $booking->transaction_id, 'status' => 'processing']);
    }

    public function test_business_decline_marks_failed_with_reason_no_exception(): void
    {
        $booking = $this->makeCanceledBooking('order-fail', 500.0);

        $gateway = Mockery::mock(GeideaPayment::class);
        $gateway->shouldReceive('verifyPayment')->andReturn($this->paidOrder('order-fail', 500));
        $gateway->shouldReceive('refund')->once()
            ->andReturn(['success' => false, 'message' => 'declined', 'error' => ['detailedResponseMessage' => 'Partial Refund not enabled', 'responseCode' => '999']]);
        $this->app->instance(GeideaPayment::class, $gateway);

        // A business decline must NOT throw (would 500 the admin); it marks failed and returns.
        $outcome = app(ProcessBookingRefund::class)->execute($booking);

        $this->assertSame('failed', $outcome);
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'refund_status' => 'failed', 'refund_error' => 'Partial Refund not enabled']);
        $this->assertDatabaseHas('refunds', ['transaction_id' => $booking->transaction_id, 'status' => 'failed', 'error_message' => 'Partial Refund not enabled']);
    }

    private function makeCanceledBooking(string $orderId, float $amount): Booking
    {
        $bookingId = DB::table('bookings')->insertGetId([
            'number_of_booking' => 'RFN'.str_replace('.', '', uniqid('', true)),
            'customer_full_name' => 'Refund Test',
            'customer_email' => 'refund@example.com',
            'apartment_id' => 1,
            'check_in' => '2026-03-20',
            'check_out' => '2026-03-21',
            'discount' => 0,
            'total_price' => $amount,
            'final_price' => $amount,
            'tax' => 0,
            'number_of_nights' => 1,
            'adults_count' => 1,
            'children_count' => 0,
            'status' => 'canceled',
            'payment_status' => 'paid',
            'refund_status' => 'pending',
            'refund_amount' => $amount,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->bookingIds[] = $bookingId;

        $transactionId = DB::table('transactions')->insertGetId([
            'booking_id' => $bookingId,
            'transaction_reference' => 'TXN'.str_replace('.', '', uniqid('', true)),
            'order_id' => $orderId,
            'amount' => $amount,
            'currency' => 'SAR',
            'type' => 'deposit',
            'status' => 'completed',
            'payment_gateway' => 'geidea',
            'platform' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->transactionIds[] = $transactionId;

        DB::table('bookings')->where('id', $bookingId)->update(['transaction_id' => $transactionId]);

        return Booking::find($bookingId);
    }

    private function paidOrder(string $orderId, float $amount): array
    {
        return ['order' => ['orderId' => $orderId, 'detailedStatus' => 'Paid', 'totalRefundedAmount' => 0], 'responseCode' => '000'];
    }

    private function refundedOrder(string $orderId, float $amount): array
    {
        return ['order' => ['orderId' => $orderId, 'detailedStatus' => 'Refunded', 'totalRefundedAmount' => $amount], 'responseCode' => '000'];
    }
}
