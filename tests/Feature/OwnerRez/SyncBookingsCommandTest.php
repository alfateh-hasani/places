<?php

namespace Tests\Feature\OwnerRez;

use App\Models\Apartment;
use App\Models\OwnerRezPropertyMapping;
use App\Services\OwnerRez\OwnerRezApiService;
use App\Services\OwnerRez\OwnerRezSyncService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SyncBookingsCommandTest extends TestCase
{
    private string $ownerrezPropertyId;

    private array $createdApartmentIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->ownerrezPropertyId = (string) (996000000 + ((int) (microtime(true) * 1000) % 100000));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        OwnerRezPropertyMapping::where('ownerrez_property_id', $this->ownerrezPropertyId)->delete();
        Apartment::whereIn('id', $this->createdApartmentIds)->delete();

        parent::tearDown();
    }

    public function test_command_fetches_bookings_through_yesterday_and_excludes_today(): void
    {
        Carbon::setTestNow('2026-03-12 10:00:00');

        $apartment = $this->createApartment();
        $mapping = OwnerRezPropertyMapping::create([
            'apartment_id' => $apartment->id,
            'ownerrez_property_id' => $this->ownerrezPropertyId,
            'ownerrez_property_name' => 'S402-Test',
            'sync_enabled' => true,
            'check_availability_enabled' => true,
        ]);

        $apiService = $this->mock(OwnerRezApiService::class);
        $apiService->shouldReceive('getBookings')
            ->once()
            ->with([
                'property_ids' => $this->ownerrezPropertyId,
                'from' => '2025-03-11',
                'to' => '2026-03-11',
                'status' => 'Active',
            ])
            ->andReturn(['items' => []]);

        $this->mock(OwnerRezSyncService::class);

        $exitCode = Artisan::call('ownerrez:sync-bookings');

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString(
            'Date range: 2025-03-11 -> 2026-03-11 (includes yesterday, excludes today)',
            Artisan::output()
        );

        $this->assertNotNull($mapping->fresh()->last_synced_at);
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
}
