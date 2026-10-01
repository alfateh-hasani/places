<?php

namespace Tests\Feature;

use App\Enums\CustomerSource;
use App\Events\BookingApproved;
use App\Exceptions\OwnerRez\OwnerRezApiException;
use App\Models\Apartment;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\OwnerRezBooking;
use App\Models\OwnerRezPropertyMapping;
use App\Models\Transaction;
use App\Services\DirectBookingService;
use App\Services\OwnerRez\OwnerRezApiService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Manual dashboard booking (bank transfer). Mirrors a paid web booking, and for a mapped
 * apartment pushes to OwnerRez synchronously with full rollback on failure.
 */
class DirectBookingServiceTest extends TestCase
{
    private int $ownerrezPropertyId;

    private array $apartmentIds = [];

    private array $customerIds = [];

    private array $bookingIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        // Isolate the service's own synchronous OwnerRez push from the async listener +
        // smart-lock provisioning that BookingApproved would otherwise trigger.
        Event::fake([BookingApproved::class]);

        // Skip the live availability API call (we're testing creation + push, not availability)
        // and the OwnerRez guest POST.
        config(['ownerrez.availability.enabled' => false]);
        config(['ownerrez.sync.sync_guest_data' => false]);

        $this->ownerrezPropertyId = 999000000 + (int) (microtime(true) * 1000) % 100000;
    }

    protected function tearDown(): void
    {
        Transaction::whereIn('booking_id', $this->bookingIds)->delete();
        OwnerRezBooking::whereIn('local_booking_id', $this->bookingIds)->delete();
        Booking::whereIn('id', $this->bookingIds)->forceDelete();
        Customer::whereIn('id', $this->customerIds)->forceDelete();
        OwnerRezPropertyMapping::where('ownerrez_property_id', $this->ownerrezPropertyId)->delete();
        Apartment::whereIn('id', $this->apartmentIds)->delete();

        parent::tearDown();
    }

    public function test_creates_paid_booking_and_bank_transfer_transaction_for_unmapped_apartment(): void
    {
        $apartment = $this->createApartment();
        $customer = $this->createCustomer();

        // No mapping → no OwnerRez API interaction at all.
        $this->mock(OwnerRezApiService::class)->shouldNotReceive('createBooking');

        $booking = app(DirectBookingService::class)->createManualBooking(
            $this->payload($apartment->id, ['customer_id' => $customer->id, 'transfer_number' => 'TRX-99'])
        );
        $this->bookingIds[] = $booking->id;

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'apartment_id' => $apartment->id,
            'customer_id' => $customer->id,
            'status' => 'approved',
            'payment_status' => 'paid',
            'booking_source' => 'dashboard',
            'payment_method_code' => 'bank_transfer',
            'ownerrez_booking_id' => null,
        ]);

        $this->assertDatabaseHas('transactions', [
            'booking_id' => $booking->id,
            'payment_gateway' => 'bank_transfer',
            'platform' => 'dashboard',
            'status' => 'completed',
            'type' => 'deposit',
            'transfer_number' => 'TRX-99',
        ]);
        $this->assertSame((float) $booking->final_price, (float) $booking->transaction->amount);
    }

    public function test_mapped_apartment_pushes_to_ownerrez_synchronously(): void
    {
        $apartment = $this->createApartment();
        $this->createMapping($apartment->id);
        $customer = $this->createCustomer();

        $mock = $this->mock(OwnerRezApiService::class);
        $mock->shouldReceive('createBooking')->once()->andReturn(['id' => 55501234]);
        // ensureBookingCustomField reads the booking; return the marker already present so
        // no createCustomField call is needed.
        $mock->shouldReceive('getBooking')->andReturn(['fields' => [['code' => 'BXSOURCEDOMAIN', 'value' => 'places']]]);

        $booking = app(DirectBookingService::class)->createManualBooking(
            $this->payload($apartment->id, ['customer_id' => $customer->id])
        );
        $this->bookingIds[] = $booking->id;

        $this->assertSame('55501234', (string) $booking->ownerrez_booking_id);
        $this->assertDatabaseHas('ownerrez_bookings', [
            'local_booking_id' => $booking->id,
            'ownerrez_booking_id' => '55501234',
            'sync_direction' => 'outbound',
            'sync_status' => 'synced',
        ]);
    }

    public function test_ownerrez_failure_rolls_back_everything_and_throws(): void
    {
        $apartment = $this->createApartment();
        $this->createMapping($apartment->id);
        $customer = $this->createCustomer();

        $mock = $this->mock(OwnerRezApiService::class);
        $mock->shouldReceive('createBooking')->once()->andThrow(new OwnerRezApiException('OwnerRez unavailable'));

        try {
            app(DirectBookingService::class)->createManualBooking(
                $this->payload($apartment->id, ['customer_id' => $customer->id])
            );
            $this->fail('Expected the OwnerRez failure to bubble up.');
        } catch (\Throwable $e) {
            $this->assertInstanceOf(OwnerRezApiException::class, $e);
        }

        // Full rollback: no local-only booking or transaction.
        $this->assertDatabaseMissing('bookings', ['apartment_id' => $apartment->id, 'customer_id' => $customer->id]);
        $this->assertSame(0, Transaction::where('apartment_id', $apartment->id)->count());
    }

    public function test_creates_new_customer_inside_transaction_when_no_customer_id(): void
    {
        $apartment = $this->createApartment();

        $this->mock(OwnerRezApiService::class)->shouldNotReceive('createBooking');

        $booking = app(DirectBookingService::class)->createManualBooking(
            $this->payload($apartment->id, [
                'new_customer' => [
                    'first_name' => 'Direct',
                    'last_name' => 'Caller',
                    'phone' => '+966500000099',
                    'email' => 'direct.caller@example.com',
                ],
            ])
        );
        $this->bookingIds[] = $booking->id;
        $this->customerIds[] = $booking->customer_id;

        $customer = Customer::find($booking->customer_id);
        $this->assertNotNull($customer);
        $this->assertSame(CustomerSource::Local, $customer->source);
        $this->assertSame('+966500000099', $customer->phone);
    }

    public function test_new_customer_without_email_syncs_guest_to_ownerrez(): void
    {
        config(['ownerrez.sync.sync_guest_data' => true]);

        $apartment = $this->createApartment();
        $this->createMapping($apartment->id);

        $mock = $this->mock(OwnerRezApiService::class);
        // Email is null → we must NOT search by email; we create the guest directly.
        $mock->shouldNotReceive('searchGuests');
        $mock->shouldReceive('createGuest')->once()->andReturn(['id' => 77001]);
        $mock->shouldReceive('createBooking')->once()->andReturn(['id' => 55509999]);
        $mock->shouldReceive('getBooking')->andReturn(['fields' => [['code' => 'BXSOURCEDOMAIN', 'value' => 'places']]]);

        $booking = app(DirectBookingService::class)->createManualBooking(
            $this->payload($apartment->id, [
                'new_customer' => [
                    'first_name' => 'NoEmail',
                    'last_name' => 'Guest',
                    'phone' => '+966500000077',
                    // no email
                ],
            ])
        );
        $this->bookingIds[] = $booking->id;
        $this->customerIds[] = $booking->customer_id;

        $this->assertNull($booking->customer->email);
        $this->assertSame('55509999', (string) $booking->ownerrez_booking_id);
    }

    public function test_admin_price_override_is_respected(): void
    {
        $apartment = $this->createApartment();
        $customer = $this->createCustomer();

        $this->mock(OwnerRezApiService::class)->shouldNotReceive('createBooking');

        $booking = app(DirectBookingService::class)->createManualBooking(
            $this->payload($apartment->id, ['customer_id' => $customer->id, 'final_price' => 1234.56])
        );
        $this->bookingIds[] = $booking->id;

        $this->assertSame(1234.56, (float) $booking->final_price);
        $this->assertSame(1234.56, (float) $booking->transaction->amount);
    }

    // ---- helpers ----

    private function payload(int $apartmentId, array $overrides = []): array
    {
        return array_merge([
            'apartment_id' => $apartmentId,
            'check_in' => now()->addDays(10)->format('Y-m-d'),
            'check_out' => now()->addDays(12)->format('Y-m-d'),
            'number_of_adults' => 2,
            'number_of_children' => 0,
            'customer_id' => null,
            'new_customer' => null,
            'final_price' => null,
            'transfer_number' => null,
            'receipt' => null,
        ], $overrides);
    }

    private function createApartment(): Apartment
    {
        $apartment = Apartment::create([
            'name_ar' => 'شقة حجز مباشر',
            'name_en' => 'Direct Booking Apartment',
            'num_rooms' => 2,
            'num_beds' => 2,
            'area' => 80,
            'price' => 300.00,
            'adults_count' => 6,
            'children_count' => 4,
            'is_active' => true,
        ]);
        $this->apartmentIds[] = $apartment->id;

        return $apartment;
    }

    private function createMapping(int $apartmentId): OwnerRezPropertyMapping
    {
        return OwnerRezPropertyMapping::create([
            'apartment_id' => $apartmentId,
            'ownerrez_property_id' => (string) $this->ownerrezPropertyId,
            'ownerrez_property_name' => 'Direct-Test',
            'sync_enabled' => true,
            'check_availability_enabled' => true,
        ]);
    }

    private function createCustomer(): Customer
    {
        $customer = Customer::forceCreate([
            'first_name' => 'Existing',
            'last_name' => 'Caller',
            'email' => 'existing.caller.'.uniqid().'@example.com',
            'phone' => '+9660'.mt_rand(100000000, 999999999),
        ]);
        $this->customerIds[] = $customer->id;

        return $customer;
    }
}
