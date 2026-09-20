<?php

namespace Tests\Feature\UnitTransfer;

use App\Enums\TransferDirection;
use App\Enums\UnitTransferStatus;
use App\Models\Apartment;
use App\Models\Booking;
use App\Models\BookingUnitTransfer;
use App\Models\Customer;
use App\Services\BookingService;
use App\Services\BookingUnitTransfer\BookingUnitTransferService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Exercises the staff-initiated → customer-confirmed unit transfer for local (non-OwnerRez)
 * units: quote pricing, the destination hold, and the three money outcomes.
 */
class BookingUnitTransferServiceTest extends TestCase
{
    private BookingUnitTransferService $service;

    private array $createdApartmentIds = [];

    private array $createdCustomerIds = [];

    private array $createdBookingIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        // Local units only — never call OwnerRez in these tests.
        config(['ownerrez.availability.enabled' => false]);

        $this->service = app(BookingUnitTransferService::class);
    }

    protected function tearDown(): void
    {
        BookingUnitTransfer::whereIn('booking_id', $this->createdBookingIds)->delete();
        Booking::whereIn('id', $this->createdBookingIds)->delete();
        Customer::whereIn('id', $this->createdCustomerIds)->forceDelete();
        Apartment::whereIn('id', $this->createdApartmentIds)->delete();

        parent::tearDown();
    }

    public function test_quote_for_cheaper_unit_is_a_refund(): void
    {
        $booking = $this->createBooking(200); // 4 nights @ 200 = 800
        $cheaper = $this->createApartment(100); // 4 nights @ 100 = 400

        $quote = $this->service->quote($booking, $cheaper->id);

        $this->assertSame(TransferDirection::Refund, $quote->direction);
        $this->assertEqualsWithDelta(-400.0, $quote->priceDelta, 0.01);
        $this->assertEqualsWithDelta(400.0, $quote->refundAmount, 0.01);
        // Cheaper unit re-prices the booking down to the destination's price.
        $this->assertEqualsWithDelta(400.0, $quote->bookingPrices['final_price'], 0.01);
    }

    public function test_quote_for_more_expensive_unit_is_absorbed(): void
    {
        $booking = $this->createBooking(200);  // paid 800
        $pricier = $this->createApartment(300); // 4 nights @ 300 = 1200

        $quote = $this->service->quote($booking, $pricier->id);

        $this->assertSame(TransferDirection::Surcharge, $quote->direction);
        $this->assertSame(0.0, $quote->refundAmount);
        // No charge: the customer keeps paying the original 800; the 400 diff becomes a discount.
        $this->assertEqualsWithDelta(800.0, $quote->bookingPrices['final_price'], 0.01);
        $this->assertEqualsWithDelta(1200.0, $quote->bookingPrices['total_price'], 0.01);
        $this->assertEqualsWithDelta(400.0, $quote->bookingPrices['discount'], 0.01);
    }

    public function test_initiate_holds_the_destination_and_leaves_the_booking_unchanged(): void
    {
        $booking = $this->createBooking(200);
        $originalApartmentId = $booking->apartment_id;
        $destination = $this->createApartment(200);

        $transfer = $this->service->initiate($booking, $destination->id);

        $this->assertSame(UnitTransferStatus::PendingCustomer, $transfer->status);
        // Booking must NOT move until the customer confirms.
        $this->assertSame($originalApartmentId, $booking->fresh()->apartment_id);

        // The destination is now held → a fresh availability check on it must fail.
        $this->expectException(ValidationException::class);
        app(BookingService::class)->checkAvailability($destination, $booking->check_in, $booking->check_out);
    }

    public function test_confirm_moves_a_cheaper_booking_and_queues_the_refund(): void
    {
        $booking = $this->createBooking(200);
        $cheaper = $this->createApartment(100);

        $transfer = $this->service->initiate($booking, $cheaper->id);
        $this->service->confirmByCustomer($transfer);

        $booking->refresh();
        $transfer->refresh();

        $this->assertSame($cheaper->id, (int) $booking->apartment_id);
        $this->assertEqualsWithDelta(400.0, (float) $booking->final_price, 0.01);
        $this->assertSame(UnitTransferStatus::Applied, $transfer->status);
        $this->assertSame(BookingUnitTransfer::REFUND_PENDING, $transfer->refund_status);
        $this->assertTrue($transfer->needsRefund());
    }

    public function test_confirm_moves_a_pricier_booking_without_charging(): void
    {
        $booking = $this->createBooking(200);
        $pricier = $this->createApartment(300);

        $transfer = $this->service->initiate($booking, $pricier->id);
        $this->service->confirmByCustomer($transfer);

        $booking->refresh();

        $this->assertSame($pricier->id, (int) $booking->apartment_id);
        // Absorbed: still 800, no refund owed.
        $this->assertEqualsWithDelta(800.0, (float) $booking->final_price, 0.01);
        $this->assertNull($transfer->fresh()->refund_status);
    }

    public function test_only_one_open_transfer_is_allowed_per_booking(): void
    {
        $booking = $this->createBooking(200);
        $a = $this->createApartment(200);
        $b = $this->createApartment(200);

        $this->service->initiate($booking, $a->id);

        $this->expectException(ValidationException::class);
        $this->service->initiate($booking, $b->id);
    }

    public function test_cancel_releases_the_held_destination(): void
    {
        $booking = $this->createBooking(200);
        $destination = $this->createApartment(200);

        $transfer = $this->service->initiate($booking, $destination->id);
        $this->service->cancel($transfer);

        $this->assertSame(UnitTransferStatus::Rejected, $transfer->fresh()->status);

        // Freed → a new transfer to the same unit is allowed again.
        $again = $this->service->initiate($booking, $destination->id);
        $this->assertSame(UnitTransferStatus::PendingCustomer, $again->status);
    }

    public function test_transfer_is_blocked_too_close_to_check_in(): void
    {
        $booking = $this->createBooking(200);
        $booking->update([
            'check_in' => now()->addHours(2)->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
        ]);
        $destination = $this->createApartment(200);

        $this->expectException(ValidationException::class);
        $this->service->initiate($booking->fresh(), $destination->id);
    }

    private function createApartment(float $price): Apartment
    {
        $apartment = Apartment::create([
            'name_ar' => 'شقة نقل '.uniqid(),
            'name_en' => 'Transfer Test Apartment '.uniqid(),
            'num_rooms' => 2,
            'num_beds' => 2,
            'area' => 80,
            'price' => $price,
            'adults_count' => 4,
            'children_count' => 4,
            'is_active' => true,
        ]);
        $this->createdApartmentIds[] = $apartment->id;

        return $apartment;
    }

    private function createBooking(float $apartmentPrice): Booking
    {
        $apartment = $this->createApartment($apartmentPrice);

        $customer = Customer::forceCreate([
            'first_name' => 'Unit',
            'last_name' => 'Transfer',
            'email' => 'unit.transfer.'.uniqid().'@example.com',
            'phone' => '+9660'.mt_rand(100000000, 999999999),
            'account_verified' => false,
        ]);
        $this->createdCustomerIds[] = $customer->id;

        $checkIn = now()->addMonths(3)->startOfDay();
        $nights = 4;
        $total = $apartmentPrice * $nights;

        $booking = Booking::create([
            'apartment_id' => $apartment->id,
            'customer_id' => $customer->id,
            'customer_full_name' => 'Unit Transfer',
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkIn->copy()->addDays($nights)->toDateString(),
            'number_of_nights' => $nights,
            'adults_count' => 2,
            'children_count' => 0,
            'total_price' => $total,
            'final_price' => $total,
            'one_night_price' => $apartmentPrice,
            'tax' => 0,
            'status' => 'approved',
            'payment_status' => 'pending',
            'booking_source' => 'web',
            'is_airbnb_booking' => 0,
        ]);
        $booking->update(['payment_status' => 'paid']); // status unchanged → no event
        $this->createdBookingIds[] = $booking->id;

        return $booking->fresh();
    }
}
