<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeletePendingBookingsCommandTest extends TestCase
{
    private array $bookingIds = [];

    private array $transactionIds = [];

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        if ($this->transactionIds !== []) {
            DB::table('transactions')->whereIn('id', $this->transactionIds)->delete();
        }

        if ($this->bookingIds !== []) {
            DB::table('bookings')->whereIn('id', $this->bookingIds)->delete();
        }

        parent::tearDown();
    }

    public function test_command_only_deletes_pending_bookings_with_pending_transactions(): void
    {
        Carbon::setTestNow('2026-03-18 12:00:00');

        $bookingToDeleteId = $this->createBooking(
            status: 'pending',
            createdAt: '2026-03-18 11:50:00'
        );
        $pendingTransactionId = $this->createTransaction(
            bookingId: $bookingToDeleteId,
            status: 'pending'
        );
        DB::table('bookings')
            ->where('id', $bookingToDeleteId)
            ->update(['transaction_id' => $pendingTransactionId]);

        $bookingWithCompletedTransactionId = $this->createBooking(
            status: 'pending',
            createdAt: '2026-03-18 11:50:00'
        );
        $completedTransactionId = $this->createTransaction(
            bookingId: $bookingWithCompletedTransactionId,
            status: 'completed'
        );
        DB::table('bookings')
            ->where('id', $bookingWithCompletedTransactionId)
            ->update(['transaction_id' => $completedTransactionId]);

        $bookingWithoutTransactionId = $this->createBooking(
            status: 'pending',
            createdAt: '2026-03-18 11:50:00'
        );

        $approvedBookingId = $this->createBooking(
            status: 'approved',
            createdAt: '2026-03-18 11:50:00'
        );
        $approvedBookingTransactionId = $this->createTransaction(
            bookingId: $approvedBookingId,
            status: 'pending'
        );
        DB::table('bookings')
            ->where('id', $approvedBookingId)
            ->update(['transaction_id' => $approvedBookingTransactionId]);

        $exitCode = Artisan::call('delete:pending-bookings');

        $this->assertSame(0, $exitCode);

        $this->assertDatabaseMissing('bookings', ['id' => $bookingToDeleteId]);
        $this->assertDatabaseHas('bookings', ['id' => $bookingWithCompletedTransactionId]);
        $this->assertDatabaseHas('bookings', ['id' => $bookingWithoutTransactionId]);
        $this->assertDatabaseHas('bookings', ['id' => $approvedBookingId]);
    }

    private function createBooking(string $status, string $createdAt): int
    {
        $bookingId = DB::table('bookings')->insertGetId([
            'number_of_booking' => 'TST'.str_replace('.', '', uniqid('', true)),
            'customer_full_name' => 'Delete Pending Test',
            'customer_email' => 'delete-pending@example.com',
            'apartment_id' => 1,
            'check_in' => '2026-03-20',
            'check_out' => '2026-03-21',
            'discount' => 0,
            'total_price' => 500,
            'final_price' => 500,
            'tax' => 0,
            'number_of_nights' => 1,
            'adults_count' => 1,
            'children_count' => 0,
            'status' => $status,
            'payment_status' => 'pending',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        $this->bookingIds[] = $bookingId;

        return $bookingId;
    }

    private function createTransaction(int $bookingId, string $status): int
    {
        $transactionId = DB::table('transactions')->insertGetId([
            'booking_id' => $bookingId,
            'transaction_reference' => 'TXN'.str_replace('.', '', uniqid('', true)),
            'amount' => 500,
            'currency' => 'SAR',
            'type' => 'deposit',
            'status' => $status,
            'payment_gateway' => 'tap',
            'platform' => 'web',
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(10),
        ]);

        $this->transactionIds[] = $transactionId;

        return $transactionId;
    }
}
