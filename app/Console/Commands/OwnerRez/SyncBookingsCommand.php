<?php

namespace App\Console\Commands\OwnerRez;

use App\Models\OwnerRezBooking;
use App\Models\OwnerRezPropertyMapping;
use App\Services\OwnerRez\OwnerRezApiService;
use App\Services\OwnerRez\OwnerRezSyncService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncBookingsCommand extends Command
{
    protected $signature = 'ownerrez:sync-bookings {--property-id= : Specific property mapping ID to sync} {--apartment-id= : Specific apartment ID to sync}';

    protected $description = 'Sync bookings from OwnerRez';

    public function handle(OwnerRezApiService $apiService, OwnerRezSyncService $syncService): int
    {
        $propertyId = $this->option('property-id');
        $apartmentId = $this->option('apartment-id');

        if ($apartmentId) {
            $mapping = OwnerRezPropertyMapping::where('apartment_id', $apartmentId)
                ->where('sync_enabled', true)
                ->first();
            if (! $mapping) {
                $this->error("No active mapping found for apartment_id: {$apartmentId}");

                return self::FAILURE;
            }

            $this->syncProperty($mapping, $apiService, $syncService);
        } elseif ($propertyId) {
            $mapping = OwnerRezPropertyMapping::find($propertyId);
            if (! $mapping) {
                $this->error("Property mapping not found: {$propertyId}");

                return self::FAILURE;
            }

            $this->syncProperty($mapping, $apiService, $syncService);
        } else {
            $mappings = OwnerRezPropertyMapping::where('sync_enabled', true)->get();
            $this->info("Syncing bookings for {$mappings->count()} properties");

            foreach ($mappings as $mapping) {
                $this->syncProperty($mapping, $apiService, $syncService);
            }
        }

        return self::SUCCESS;
    }

    protected function syncProperty(
        OwnerRezPropertyMapping $mapping,
        OwnerRezApiService $apiService,
        OwnerRezSyncService $syncService
    ): void {
        $this->info("--- Syncing: {$mapping->ownerrez_property_name} (mapping_id: {$mapping->id}) ---");

        $from = Carbon::now()->format('Y-m-d');
        $to = Carbon::now()->addYear()->format('Y-m-d');

        $this->line("  Date range: {$from} → {$to}");

        try {
            $response = $apiService->getBookings([
                'property_ids' => $mapping->ownerrez_property_id,
                'from' => $from,
                'to' => $to,
                'status' => 'Active',
            ]);

            $bookings = $response['items'] ?? [];
            $this->info("  Fetched {$this->count($bookings)} bookings from OwnerRez API");

            $syncedCount = 0;
            $skippedCount = 0;
            $failedCount = 0;

            foreach ($bookings as $bookingData) {
                $ownerrezBookingId = $bookingData['id'] ?? 'unknown';
                $guestId = $bookingData['guest_id'] ?? 'N/A';
                $arrival = $bookingData['arrival'] ?? 'N/A';
                $departure = $bookingData['departure'] ?? 'N/A';

                $this->line("  ┌ Booking #{$ownerrezBookingId} | guest_id: {$guestId} | {$arrival} → {$departure}");

                // Check if already exists
                $existing = OwnerRezBooking::where('ownerrez_booking_id', $ownerrezBookingId)->first();
                if ($existing) {
                    $this->warn("  └ SKIPPED: already exists (local_booking_id: {$existing->local_booking_id})");
                    $skippedCount++;

                    continue;
                }

                try {
                    $syncService->syncBookingFromWebhook([
                        'action' => 'entity_create',
                        'data' => $bookingData,
                    ]);
                    $syncedCount++;
                    $this->info("  └ SUCCESS: booking synced");
                } catch (\Exception $e) {
                    $failedCount++;
                    $this->error("  └ FAILED: {$e->getMessage()}");
                }
            }

            $this->newLine();
            $this->table(
                ['Total', 'Synced', 'Skipped', 'Failed'],
                [[count($bookings), $syncedCount, $skippedCount, $failedCount]]
            );

            $mapping->markAsSynced();
        } catch (\Exception $e) {
            $this->error("  FATAL: {$e->getMessage()}");
        }

        $this->newLine();
    }

    private function count(array $items): int
    {
        return count($items);
    }
}
