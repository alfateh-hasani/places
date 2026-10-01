<?php

namespace App\Console\Commands;

use App\Services\BookingUnitTransfer\BookingUnitTransferService;
use Illuminate\Console\Command;

class ExpireStaleUnitTransfers extends Command
{
    protected $signature = 'unit-transfers:expire-stale {--minutes=180 : Expire pending-customer transfers older than this}';

    protected $description = 'Release unit-transfer requests stuck awaiting customer confirmation, freeing the held destination unit.';

    public function handle(BookingUnitTransferService $service): int
    {
        $minutes = (int) $this->option('minutes');
        $count = $service->expireStale($minutes);

        $this->info("Expired {$count} pending unit-transfer request(s) older than {$minutes} minute(s).");

        return self::SUCCESS;
    }
}
