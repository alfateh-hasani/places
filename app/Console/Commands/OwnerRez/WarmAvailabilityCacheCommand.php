<?php

namespace App\Console\Commands\OwnerRez;

use App\Models\OwnerRezPropertyMapping;
use App\Services\OwnerRez\OwnerRezSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class WarmAvailabilityCacheCommand extends Command
{
    protected $signature = 'ownerrez:warm-cache {--apartment-id= : Warm cache for a specific apartment only}';

    protected $description = 'Refresh OwnerRez calendar cache for all active property mappings (stale-while-revalidate)';

    public function handle(OwnerRezSyncService $syncService): int
    {
        $apartmentId = $this->option('apartment-id');

        $query = OwnerRezPropertyMapping::where('sync_enabled', true);

        if ($apartmentId) {
            $query->where('apartment_id', $apartmentId);
        }

        $mappings = $query->get();

        if ($mappings->isEmpty()) {
            $this->warn('No active property mappings found.');

            return self::SUCCESS;
        }

        $this->info("Refreshing OwnerRez calendar cache for {$mappings->count()} properties");

        $successCount = 0;
        $failedCount = 0;

        foreach ($mappings as $mapping) {
            $propertyId = $mapping->ownerrez_property_id;
            $cacheKey = "ownerrez:calendar:v1:{$propertyId}";

            $refreshed = $syncService->refreshCalendarCache($propertyId);

            if ($refreshed) {
                $bookingsCount = count(Cache::get($cacheKey, []));
                $this->line("  ✓ {$mapping->ownerrez_property_name} (property: {$propertyId}) — {$bookingsCount} bookings cached");
                $successCount++;
            } else {
                $this->warn("  ~ {$mapping->ownerrez_property_name} (property: {$propertyId}) — API failed, old cache kept");
                $failedCount++;
            }
        }

        $this->newLine();
        $this->table(
            ['Success', 'Failed (old cache kept)'],
            [[$successCount, $failedCount]]
        );

        return $failedCount > 0 ? self::FAILURE : self::SUCCESS;
    }
}
