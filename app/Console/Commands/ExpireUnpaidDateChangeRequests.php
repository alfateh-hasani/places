<?php

namespace App\Console\Commands;

use App\Services\DateChangeService;
use Illuminate\Console\Command;

class ExpireUnpaidDateChangeRequests extends Command
{
    protected $signature = 'date-changes:expire-unpaid {--minutes=30 : Expire awaiting-payment/pending requests older than this}';

    protected $description = 'Release date-change requests stuck awaiting payment (or transient), freeing their reserved window.';

    public function handle(DateChangeService $service): int
    {
        $minutes = (int) $this->option('minutes');
        $count = $service->expireStale($minutes);

        $this->info("Expired {$count} unpaid date-change request(s) older than {$minutes} minute(s).");

        return self::SUCCESS;
    }
}
