<?php

namespace App\Listeners;

use App\Events\BookingCancelled;
use App\Services\Locks\LockAccessService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class RevokeSmartLockAccess implements ShouldQueue
{
    /**
     * Only run after the booking's status-change transaction has committed —
     * the vendor API call must never happen while a DB transaction is open.
     */
    public $afterCommit = true;

    public function __construct(private readonly LockAccessService $locks) {}

    public function handle(BookingCancelled $event): void
    {
        try {
            $this->locks->revokeForBooking($event->booking, "status changed to {$event->booking->status}");
        } catch (Throwable $e) {
            Log::error('RevokeSmartLockAccess listener failed', [
                'booking_id' => $event->booking->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
