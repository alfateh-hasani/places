<?php

namespace Tests\Feature\OwnerRez;

use App\Models\Apartment;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\OwnerRezBooking;
use App\Models\OwnerRezPropertyMapping;
use App\Services\OwnerRez\OwnerRezApiService;
use App\Services\OwnerRez\OwnerRezSyncService;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OwnerRezSyncServiceTest extends TestCase
{
    private OwnerRezSyncService $syncService;

    /** Unique per test run to avoid collisions with production data */
    private int $ownerrezPropertyId;

    private int $ownerrezBookingId;

    private int $ownerrezGuestId;

    /** IDs of records to clean up after each test */
    private array $createdApartmentIds = [];

    private array $createdCustomerIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        // Unique IDs per test execution — kept within INT range (< 2.1B) to avoid
        // overflow issues with varchar/int columns while still avoiding real production data
        $suffix = (int) (microtime(true) * 1000) % 100000;
        $this->ownerrezPropertyId = 999000000 + $suffix;
        $this->ownerrezBookingId  = 998000000 + $suffix;
        $this->ownerrezGuestId    = 997000000 + $suffix;

        config(['ownerrez.sync.sync_guest_data' => false]);
    }

    protected function tearDown(): void
    {
        // Clean up in reverse dependency order to avoid FK violations
        OwnerRezBooking::where('ownerrez_booking_id', $this->ownerrezBookingId)->delete();
        Booking::where('ownerrez_booking_id', $this->ownerrezBookingId)->delete();
        Customer::whereIn('id', $this->createdCustomerIds)->forceDelete();
        OwnerRezPropertyMapping::where('ownerrez_property_id', $this->ownerrezPropertyId)->delete();
        Apartment::whereIn('id', $this->createdApartmentIds)->delete();

        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // entity_create tests
    // -------------------------------------------------------------------------

    public function test_entity_create_with_block_is_skipped(): void
    {
        $this->createSyncService();

        $this->syncService->syncBookingFromWebhook($this->buildWebhookPayload(
            action: 'entity_create',
            entity: array_merge($this->blockEntityData(), ['is_block' => true]),
        ));

        $this->assertDatabaseMissing('bookings', [
            'ownerrez_booking_id' => $this->ownerrezBookingId,
        ]);

        $this->assertDatabaseMissing('ownerrez_bookings', [
            'ownerrez_booking_id' => $this->ownerrezBookingId,
        ]);
    }

    public function test_entity_create_is_skipped_when_property_has_no_mapping(): void
    {
        $this->createSyncService();

        $this->syncService->syncBookingFromWebhook($this->buildWebhookPayload(
            action: 'entity_create',
            entity: $this->realBookingEntityData(),
        ));

        $this->assertDatabaseMissing('bookings', [
            'ownerrez_booking_id' => $this->ownerrezBookingId,
        ]);
    }

    public function test_entity_create_is_skipped_when_sync_is_disabled_on_mapping(): void
    {
        $apartment = $this->createApartment();
        $this->createMapping($apartment->id, syncEnabled: false);
        $this->createSyncService();

        $this->syncService->syncBookingFromWebhook($this->buildWebhookPayload(
            action: 'entity_create',
            entity: $this->realBookingEntityData(),
        ));

        $this->assertDatabaseMissing('bookings', [
            'ownerrez_booking_id' => $this->ownerrezBookingId,
        ]);
    }

    public function test_entity_create_creates_local_booking_and_ownerrez_record(): void
    {
        $apartment = $this->createApartment();
        $this->createMapping($apartment->id);

        $mockApi = $this->mockApiService();
        $mockApi->shouldReceive('getBooking')
            ->once()
            ->with($this->ownerrezBookingId)
            ->andReturn($this->fullBookingApiResponse());

        $mockApi->shouldReceive('getGuest')
            ->once()
            ->with($this->ownerrezGuestId)
            ->andReturn($this->fullGuestApiResponse());

        $this->syncService->syncBookingFromWebhook($this->buildWebhookPayload(
            action: 'entity_create',
            entity: $this->realBookingEntityData(),
        ));

        $this->assertDatabaseHas('bookings', [
            'apartment_id' => $apartment->id,
            'ownerrez_booking_id' => $this->ownerrezBookingId,
            'check_in' => '2026-03-12',
            'check_out' => '2026-03-16',
            'status' => 'approved',
            'booking_source' => 'ownerrez',
            'channel_name' => 'Airbnb',
            'external_reference' => 'HMCFDR58EZ',
        ]);

        $this->assertDatabaseHas('ownerrez_bookings', [
            'ownerrez_booking_id' => $this->ownerrezBookingId,
            'apartment_id' => $apartment->id,
            'sync_status' => 'synced',
            'sync_direction' => 'inbound',
        ]);
    }

    public function test_entity_create_is_skipped_when_booking_already_exists(): void
    {
        $apartment = $this->createApartment();
        $this->createMapping($apartment->id);
        $customer = $this->createCustomer(['ownerrez_guest_id' => $this->ownerrezGuestId]);
        $localBooking = $this->createLocalBooking($apartment->id, $customer->id);
        $this->createOwnerRezBooking($localBooking->id, $apartment->id);
        $this->createSyncService();

        $initialBookingCount = Booking::where('ownerrez_booking_id', $this->ownerrezBookingId)->count();
        $initialOwnerRezCount = OwnerRezBooking::where('ownerrez_booking_id', $this->ownerrezBookingId)->count();

        $this->syncService->syncBookingFromWebhook($this->buildWebhookPayload(
            action: 'entity_create',
            entity: $this->realBookingEntityData(),
        ));

        // Counts must remain unchanged — no duplicate should be created
        $this->assertEquals(
            $initialBookingCount,
            Booking::where('ownerrez_booking_id', $this->ownerrezBookingId)->count()
        );
        $this->assertEquals(
            $initialOwnerRezCount,
            OwnerRezBooking::where('ownerrez_booking_id', $this->ownerrezBookingId)->count()
        );
    }

    // -------------------------------------------------------------------------
    // entity_update tests
    // -------------------------------------------------------------------------

    public function test_entity_update_does_nothing_when_booking_not_found_locally(): void
    {
        $this->createSyncService();

        $this->syncService->syncBookingFromWebhook($this->buildWebhookPayload(
            action: 'entity_update',
            entity: $this->realBookingEntityData(),
        ));

        $this->assertDatabaseMissing('bookings', [
            'ownerrez_booking_id' => $this->ownerrezBookingId,
        ]);
    }

    public function test_entity_update_updates_local_booking_dates_and_adults(): void
    {
        $apartment = $this->createApartment();
        $this->createMapping($apartment->id);
        $customer = $this->createCustomer(['ownerrez_guest_id' => $this->ownerrezGuestId]);
        $localBooking = $this->createLocalBooking($apartment->id, $customer->id, [
            'check_in' => '2026-03-10',
            'check_out' => '2026-03-14',
        ]);
        $this->createOwnerRezBooking($localBooking->id, $apartment->id);
        $this->createSyncService();

        $this->syncService->syncBookingFromWebhook($this->buildWebhookPayload(
            action: 'entity_update',
            entity: array_merge($this->realBookingEntityData(), [
                'arrival' => '2026-03-12',
                'departure' => '2026-03-16',
                'adults' => 3,
                'status' => 'active',
            ]),
        ));

        $this->assertDatabaseHas('bookings', [
            'id' => $localBooking->id,
            'check_in' => '2026-03-12',
            'check_out' => '2026-03-16',
            'adults_count' => 3,
        ]);

        $this->assertDatabaseHas('ownerrez_bookings', [
            'ownerrez_booking_id' => $this->ownerrezBookingId,
            'sync_status' => 'synced',
        ]);
    }

    public function test_entity_update_cancels_local_booking_when_status_is_canceled(): void
    {
        $apartment = $this->createApartment();
        $customer = $this->createCustomer(['ownerrez_guest_id' => $this->ownerrezGuestId]);
        $localBooking = $this->createLocalBooking($apartment->id, $customer->id, [
            'status' => 'approved',
        ]);
        $this->createOwnerRezBooking($localBooking->id, $apartment->id);
        $this->createSyncService();

        $this->syncService->syncBookingFromWebhook($this->buildWebhookPayload(
            action: 'entity_update',
            entity: array_merge($this->realBookingEntityData(), ['status' => 'canceled']),
        ));

        $this->assertDatabaseHas('bookings', [
            'id' => $localBooking->id,
            'status' => 'canceled',
        ]);
    }

    public function test_entity_update_does_not_overwrite_local_pricing(): void
    {
        $apartment = $this->createApartment();
        $this->createMapping($apartment->id);
        $customer = $this->createCustomer(['ownerrez_guest_id' => $this->ownerrezGuestId]);
        $localBooking = $this->createLocalBooking($apartment->id, $customer->id, [
            'total_price' => 999.00,
            'final_price' => 999.00,
            'one_night_price' => 249.75,
        ]);
        $this->createOwnerRezBooking($localBooking->id, $apartment->id);
        $this->createSyncService();

        $this->syncService->syncBookingFromWebhook($this->buildWebhookPayload(
            action: 'entity_update',
            entity: array_merge($this->realBookingEntityData(), [
                'total_amount' => 830.00,
                'status' => 'active',
            ]),
        ));

        $this->assertDatabaseHas('bookings', [
            'id' => $localBooking->id,
            'total_price' => 999.00,
            'final_price' => 999.00,
        ]);
    }

    // -------------------------------------------------------------------------
    // Complete lifecycle scenario
    // -------------------------------------------------------------------------

    /**
     * Simulates the full lifecycle of an Airbnb booking received via OwnerRez webhooks.
     *
     * Real-world flow based on actual webhook samples:
     *   1. entity_create  → is_block: true          → SKIPPED (just an Airbnb hold)
     *   2. entity_update  → is_block: false, active  → booking confirmed, no payment yet (pending)
     *   3. entity_update  → total_paid: partial      → payment_status = partial
     *   4. entity_update  → total_paid: full         → payment_status = paid
     *   5. entity_update  → status: canceled         → booking canceled
     */
    public function test_complete_booking_payment_and_status_lifecycle(): void
    {
        $apartment = $this->createApartment();
        $this->createMapping($apartment->id);
        $customer = $this->createCustomer(['ownerrez_guest_id' => $this->ownerrezGuestId]);
        $this->createSyncService();

        // ── Step 1: entity_create arrives as a block → nothing created locally ──────
        $this->syncService->syncBookingFromWebhook($this->buildWebhookPayload(
            action: 'entity_create',
            entity: array_merge($this->blockEntityData(), ['is_block' => true]),
        ));

        $this->assertDatabaseMissing('bookings', ['ownerrez_booking_id' => $this->ownerrezBookingId]);

        // ── Prerequisite: simulate the booking being created externally (e.g. via API sync) ──
        // In the Airbnb flow the block converts to a booking via entity_update,
        // but handleBookingUpdated only updates existing local records.
        // We create the local record manually to test the subsequent update steps.
        $localBooking = $this->createLocalBooking($apartment->id, $customer->id, [
            'status' => 'approved',
            'payment_status' => 'pending',
            'total_price' => 830.00,
            'final_price' => 830.00,
        ]);
        $this->createOwnerRezBooking($localBooking->id, $apartment->id);

        // ── Step 2: Booking confirmed, no payment yet → payment_status stays pending ──
        $this->syncService->syncBookingFromWebhook($this->buildWebhookPayload(
            action: 'entity_update',
            entity: array_merge($this->realBookingEntityData(), [
                'status' => 'active',
                'total_amount' => 830.00,
                // total_paid absent → defaults to 0
            ]),
        ));

        $this->assertDatabaseHas('bookings', [
            'id' => $localBooking->id,
            'status' => 'approved',
            'payment_status' => 'pending',
        ]);

        // ── Step 3: Partial payment received (categories: financial, payment, transaction) ──
        $this->syncService->syncBookingFromWebhook($this->buildWebhookPayload(
            action: 'entity_update',
            entity: array_merge($this->realBookingEntityData(), [
                'status' => 'active',
                'total_amount' => 830.00,
                'total_paid' => 415.00,
            ]),
        ));

        $this->assertDatabaseHas('bookings', [
            'id' => $localBooking->id,
            'status' => 'approved',
            'payment_status' => 'partial',
        ]);

        // ── Step 4: Full payment received ──────────────────────────────────────────────
        $this->syncService->syncBookingFromWebhook($this->buildWebhookPayload(
            action: 'entity_update',
            entity: array_merge($this->realBookingEntityData(), [
                'status' => 'active',
                'total_amount' => 830.00,
                'total_paid' => 830.00,
            ]),
        ));

        $this->assertDatabaseHas('bookings', [
            'id' => $localBooking->id,
            'status' => 'approved',
            'payment_status' => 'paid',
        ]);

        // ── Step 5: Booking canceled ───────────────────────────────────────────────────
        $this->syncService->syncBookingFromWebhook($this->buildWebhookPayload(
            action: 'entity_update',
            entity: array_merge($this->realBookingEntityData(), [
                'status' => 'canceled',
            ]),
        ));

        $this->assertDatabaseHas('bookings', [
            'id' => $localBooking->id,
            'status' => 'canceled',
        ]);

        // OwnerRez booking record must be updated at each step
        $this->assertDatabaseHas('ownerrez_bookings', [
            'ownerrez_booking_id' => $this->ownerrezBookingId,
            'sync_status' => 'synced',
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createSyncService(): void
    {
        $this->syncService = app(OwnerRezSyncService::class);
    }

    /**
     * @return \Mockery\MockInterface&OwnerRezApiService
     */
    private function mockApiService(): \Mockery\MockInterface
    {
        $mock = $this->mock(OwnerRezApiService::class);
        $this->syncService = app(OwnerRezSyncService::class);

        return $mock;
    }

    private function createApartment(): Apartment
    {
        $apartment = Apartment::create([
            'name_ar' => 'شقة اختبار',
            'name_en' => 'Test Apartment',
            'num_rooms' => 2,
            'num_beds' => 2,
            'area' => 80,
            'price' => 200.00,
            'is_active' => true,
        ]);

        $this->createdApartmentIds[] = $apartment->id;

        return $apartment;
    }

    private function createMapping(int $apartmentId, bool $syncEnabled = true): OwnerRezPropertyMapping
    {
        return OwnerRezPropertyMapping::create([
            'apartment_id' => $apartmentId,
            'ownerrez_property_id' => (string) $this->ownerrezPropertyId,
            'ownerrez_property_name' => 'S402-Test',
            'sync_enabled' => $syncEnabled,
            'check_availability_enabled' => true,
        ]);
    }

    private function createCustomer(array $overrides = []): Customer
    {
        $customer = Customer::forceCreate(array_merge([
            'first_name' => 'Test',
            'last_name' => 'Webhook',
            'email' => 'webhook.test.'.uniqid().'@example.com',
            'phone' => '+9660'.mt_rand(100000000, 999999999),
            'account_verified' => false,
        ], $overrides));

        $this->createdCustomerIds[] = $customer->id;

        return $customer;
    }

    private function createLocalBooking(int $apartmentId, int $customerId, array $overrides = []): Booking
    {
        return Booking::create(array_merge([
            'apartment_id' => $apartmentId,
            'customer_id' => $customerId,
            'ownerrez_booking_id' => $this->ownerrezBookingId,
            'check_in' => '2026-03-12',
            'check_out' => '2026-03-16',
            'number_of_nights' => 4,
            'adults_count' => 2,
            'children_count' => 0,
            'customer_full_name' => 'Test Webhook',
            'total_price' => 830.00,
            'final_price' => 830.00,
            'one_night_price' => 207.50,
            'tax' => 0,
            'status' => 'approved',
            'payment_status' => 'pending',
            'booking_source' => 'ownerrez',
            'channel_name' => 'Airbnb',
            'is_airbnb_booking' => 0,
        ], $overrides));
    }

    private function createOwnerRezBooking(int $localBookingId, int $apartmentId): OwnerRezBooking
    {
        return OwnerRezBooking::create([
            'ownerrez_booking_id' => $this->ownerrezBookingId,
            'local_booking_id' => $localBookingId,
            'apartment_id' => $apartmentId,
            'raw_data' => $this->realBookingEntityData(),
            'sync_status' => 'synced',
            'sync_direction' => 'inbound',
            'synced_at' => now(),
        ]);
    }

    private function buildWebhookPayload(string $action, array $entity): array
    {
        return [
            'action' => $action,
            'event' => 'booking',
            'data' => $entity,
            'entity_id' => $this->ownerrezBookingId,
            'categories' => [],
            'received_at' => now()->toIso8601String(),
            'raw' => [],
        ];
    }

    /**
     * Block entity data — is_block: true (Airbnb hold before guest confirmation)
     */
    private function blockEntityData(): array
    {
        return [
            'id' => $this->ownerrezBookingId,
            'adults' => 0,
            'arrival' => '2026-03-12',
            'departure' => '2026-03-16',
            'check_in' => '16:00',
            'check_out' => '11:00',
            'children' => 0,
            'currency_code' => 'SAR',
            'is_block' => true,
            'listing_site' => 'Airbnb',
            'platform_reservation_number' => 'HMCFDR58EZ',
            'property' => ['id' => $this->ownerrezPropertyId, 'name' => 'S402'],
            'property_id' => $this->ownerrezPropertyId,
            'status' => 'pending',
            'type' => 'block',
        ];
    }

    /**
     * Real booking entity data — is_block: false, status: active
     * Based on the actual OwnerRez entity_update webhook received after Airbnb confirmation.
     */
    private function realBookingEntityData(): array
    {
        return [
            'id' => $this->ownerrezBookingId,
            'adults' => 2,
            'arrival' => '2026-03-12',
            'departure' => '2026-03-16',
            'check_in' => '15:00',
            'check_out' => '12:00',
            'children' => 0,
            'currency_code' => 'SAR',
            'guest' => [
                'first_name' => 'Ibrahim',
                'id' => $this->ownerrezGuestId,
                'last_name' => 'Alshahrani',
            ],
            'guest_id' => $this->ownerrezGuestId,
            'is_block' => false,
            'listing_site' => 'Airbnb',
            'platform_reservation_number' => 'HMCFDR58EZ',
            'property' => ['id' => $this->ownerrezPropertyId, 'name' => 'S402'],
            'property_id' => $this->ownerrezPropertyId,
            'status' => 'active',
            'total_amount' => 830.00,
            'total_owed' => 830.00,
            'type' => 'booking',
        ];
    }

    /**
     * Full booking data as returned by OwnerRez API (used in entity_create flow).
     */
    private function fullBookingApiResponse(): array
    {
        return array_merge($this->realBookingEntityData(), [
            'booked_utc' => '2026-03-08T17:54:45Z',
            'created_utc' => '2026-03-08T17:54:44Z',
            'updated_utc' => '2026-03-08T17:56:10Z',
        ]);
    }

    /**
     * Full guest data as returned by OwnerRez API.
     */
    private function fullGuestApiResponse(): array
    {
        return [
            'id' => $this->ownerrezGuestId,
            'first_name' => 'Ibrahim',
            'last_name' => 'Alshahrani',
            'email_addresses' => [
                ['address' => 'test.webhook.'.$this->ownerrezGuestId.'@example.com', 'is_default' => true, 'type' => 'home'],
            ],
            'phones' => [
                ['number' => '+9660'.$this->ownerrezGuestId, 'is_default' => true, 'type' => 'mobile'],
            ],
        ];
    }
}
