<?php

namespace Tests\Feature;

use App\Events\BookingApproved;
use App\Events\BookingCancelled;
use App\Jobs\SendNewBookingStaffNotification;
use App\Models\Apartment;
use App\Models\Booking;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class NewBookingStaffNotificationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Fake only the booking domain events so their real listeners (e.g. the
        // synchronous OwnerRez sync) don't run. Eloquent model events still fire,
        // so the model boot hooks under test execute normally.
        Event::fake([BookingApproved::class, BookingCancelled::class]);
        Bus::fake();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeBooking(array $overrides = []): Booking
    {
        return Booking::create(array_merge([
            'number_of_booking' => '00'.random_int(100000, 999999),
            'customer_full_name' => 'Test Customer',
            'apartment_id' => Apartment::query()->value('id'),
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-03',
            'total_price' => 100,
            'final_price' => 100,
            'number_of_nights' => 2,
            'adults_count' => 1,
            'children_count' => 0,
            'status' => 'pending',
            'payment_status' => 'pending',
            'booking_source' => 'web',
            'is_airbnb_booking' => false,
        ], $overrides));
    }

    public function test_pending_web_booking_does_not_notify_before_payment(): void
    {
        $this->makeBooking(['status' => 'pending', 'payment_status' => 'pending']);

        Bus::assertNotDispatched(SendNewBookingStaffNotification::class);
    }

    public function test_web_booking_notifies_when_confirmed_after_payment(): void
    {
        $booking = $this->makeBooking(['status' => 'pending', 'payment_status' => 'pending']);
        Bus::assertNotDispatched(SendNewBookingStaffNotification::class);

        $booking->update(['status' => 'approved', 'payment_status' => 'paid']);

        Bus::assertDispatched(SendNewBookingStaffNotification::class);
    }

    public function test_booking_created_already_paid_notifies(): void
    {
        $this->makeBooking(['status' => 'approved', 'payment_status' => 'paid', 'booking_source' => 'dashboard']);

        Bus::assertDispatched(SendNewBookingStaffNotification::class);
    }

    public function test_imported_ownerrez_paid_booking_does_not_notify(): void
    {
        $this->makeBooking(['status' => 'approved', 'payment_status' => 'paid', 'booking_source' => 'ownerrez']);

        Bus::assertNotDispatched(SendNewBookingStaffNotification::class);
    }
}
