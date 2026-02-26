<?php

namespace App\Console\Commands\OwnerRez;

use App\Models\OwnerRezPropertyMapping;
use App\Services\OwnerRez\OwnerRezSyncService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class WarmAvailabilityCacheCommand extends Command
{
    protected $signature = 'ownerrez:warm-cache {--apartment-id= : Warm cache for a specific apartment only}';

    protected $description = 'Pre-warm OwnerRez availability cache for all active property mappings';

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

        $from = Carbon::now()->format('Y-m-d');
        $to = Carbon::now()->addYear()->format('Y-m-d');

        $this->info("Warming OwnerRez availability cache for {$mappings->count()} properties ({$from} → {$to})");

        $success = 0;
        $failed = 0;

        foreach ($mappings as $mapping) {
            $propertyId = $mapping->ownerrez_property_id;
            $cacheKey = "ownerrez:availability:v2:{$propertyId}:{$from}:{$to}";

            try {
                // نمسح الكاش القديم لضمان جلب بيانات طازجة
                Cache::forget($cacheKey);

                $bookings = $syncService->getActiveBookings($propertyId, $from, $to);

                $this->line("  ✓ {$mapping->ownerrez_property_name} (property: {$propertyId}) — {$bookings->count()} bookings cached");
                $success++;
            } catch (\Exception $e) {
                $this->error("  ✗ {$mapping->ownerrez_property_name} (property: {$propertyId}) — {$e->getMessage()}");
                $failed++;
            }
        }

        $this->newLine();
        $this->table(
            ['Success', 'Failed'],
            [[$success, $failed]]
        );

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
