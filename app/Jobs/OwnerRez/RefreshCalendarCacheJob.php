<?php

namespace App\Jobs\OwnerRez;

use App\Services\OwnerRez\OwnerRezSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefreshCalendarCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public readonly int|string $propertyId) {}

    public function handle(OwnerRezSyncService $syncService): void
    {
        $syncService->refreshCalendarCache($this->propertyId);
    }
}
