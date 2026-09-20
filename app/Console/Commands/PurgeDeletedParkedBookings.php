<?php

namespace App\Console\Commands;

use App\Models\OwnerRezParkedBooking;
use App\Services\OwnerRez\OwnerRezBlockParkingService;
use Illuminate\Console\Command;

/**
 * Once the tech team deletes parked bookings in the OwnerRez UI, drop the local registry rows —
 * but only on an explicit 404 (deleted). Anything uncertain is never purged.
 */
class PurgeDeletedParkedBookings extends Command
{
    protected $signature = 'ownerrez:purge-parked-bookings
        {--dry-run : Report which rows would be purged without deleting them}';

    protected $description = 'Delete parked OwnerRez booking records whose bookings no longer exist in OwnerRez';

    public function handle(OwnerRezBlockParkingService $parking): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        if (OwnerRezParkedBooking::query()->count() === 0) {
            $this->info('No parked OwnerRez bookings to check. Nothing to do.');

            return self::SUCCESS;
        }

        $purged = $stillPresent = $skipped = 0;

        OwnerRezParkedBooking::query()->chunkById(100, function ($rows) use ($parking, $isDryRun, &$purged, &$stillPresent, &$skipped): void {
            foreach ($rows as $row) {
                $result = $parking->fetchBooking((string) $row->ownerrez_booking_id);

                if ($result === false) {            // explicit 404 → deleted in OwnerRez
                    if ($isDryRun) {
                        $this->warn("[DRY RUN] Would purge parked booking #{$row->ownerrez_booking_id} (not found).");
                    } else {
                        $row->delete();
                    }
                    $purged++;

                    continue;
                }

                if ($result === null) {             // disabled / API error → never delete
                    $skipped++;

                    continue;
                }

                $stillPresent++;                    // array → still exists, awaiting deletion
            }
        });

        $verb = $isDryRun ? 'would be purged' : 'purged';
        $this->info("Done. {$purged} record(s) {$verb}, {$stillPresent} still present, {$skipped} skipped.");

        return self::SUCCESS;
    }
}
