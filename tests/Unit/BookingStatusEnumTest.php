<?php

namespace Tests\Unit;

use App\Enums\BookingStatus;
use PHPUnit\Framework\TestCase;

/**
 * Pure (no-DB, no-app) guarantees for the BookingStatus single source of truth.
 */
class BookingStatusEnumTest extends TestCase
{
    public function test_cancellation_requested_keeps_backward_compatible_stored_value(): void
    {
        // The stored value must stay 'customer_canceled' — the mobile app and existing
        // rows depend on it. The code name changed; the wire value must not.
        $this->assertSame('customer_canceled', BookingStatus::CancellationRequested->value);
        $this->assertSame(BookingStatus::CancellationRequested, BookingStatus::from('customer_canceled'));
    }

    public function test_all_values_are_present(): void
    {
        $this->assertEqualsCanonicalizing(
            ['pending', 'approved', 'rejected', 'booked', 'finished', 'canceled', 'customer_canceled'],
            BookingStatus::values(),
        );
    }

    public function test_occupying_holds_the_unit_including_a_pending_cancellation(): void
    {
        // A cancellation request must keep blocking the unit until it is finalized.
        $this->assertSame(
            ['pending', 'approved', 'booked', 'customer_canceled'],
            BookingStatus::occupying(),
        );
        $this->assertNotContains('canceled', BookingStatus::occupying());
    }

    public function test_cancellation_workflow_covers_request_and_finalized_states(): void
    {
        $this->assertSame(
            ['customer_canceled', 'canceled'],
            BookingStatus::cancellationWorkflow(),
        );
    }

    public function test_presentation_helpers_return_values_for_every_case(): void
    {
        foreach (BookingStatus::cases() as $case) {
            $this->assertNotEmpty($case->color());
            $this->assertNotEmpty($case->icon());
        }
    }
}
