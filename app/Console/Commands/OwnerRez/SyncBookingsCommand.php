<?php

namespace App\Console\Commands\OwnerRez;

use App\Jobs\OwnerRez\SyncPropertyBookingsJob;
use App\Models\OwnerRezPropertyMapping;
use Illuminate\Console\Command;

class SyncBookingsCommand extends Command
{
    protected $signature = 'ownerrez:sync-bookings {--property-id= : Specific property mapping ID to sync}';

    protected $description = 'Sync bookings from OwnerRez';

    public function handle(): int
    {
        $propertyId = $this->option('property-id');

        if ($propertyId) {
            $mapping = OwnerRezPropertyMapping::find($propertyId);
            if (! $mapping) {
                $this->error("Property mapping not found: {$propertyId}");

                return self::FAILURE;
            }

            $this->info("Syncing bookings for property: {$mapping->ownerrez_property_name}");
            SyncPropertyBookingsJob::dispatch($mapping->id);
            $this->info('Sync job dispatched');
        } else {
            $mappings = OwnerRezPropertyMapping::where('sync_enabled', true)->get();
            $this->info("Syncing bookings for {$mappings->count()} properties");

            foreach ($mappings as $mapping) {
                SyncPropertyBookingsJob::dispatch($mapping->id);
                $this->line("- Dispatched sync for: {$mapping->ownerrez_property_name}");
            }
        }

        return self::SUCCESS;
    }
}
