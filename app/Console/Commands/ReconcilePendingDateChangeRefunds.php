<?php

namespace App\Console\Commands;

use App\Enums\DateChangeStatus;
use App\Models\DateChangeRequest;
use App\Services\DateChangeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReconcilePendingDateChangeRefunds extends Command
{
    protected $signature = 'date-changes:reconcile-refunds {--minutes=10 : Only retry settlements whose last attempt is older than this} {--max-attempts=10}';

    protected $description = 'Re-settle stuck date-change requests (processing/failed) — retry-first: re-applies paid surcharges whose OwnerRez sync failed, and retries pending difference refunds. Idempotent.';

    public function handle(DateChangeService $service): int
    {
        $threshold = now()->subMinutes((int) $this->option('minutes'));
        $maxAttempts = (int) $this->option('max-attempts');

        $requests = DateChangeRequest::whereIn('status', [
            DateChangeStatus::Processing->value,
            DateChangeStatus::Failed->value,
        ])
            ->where('attempts', '<', $maxAttempts)
            ->where(function ($q) use ($threshold): void {
                $q->whereNull('last_attempt_at')
                    ->orWhere('last_attempt_at', '<=', $threshold);
            })
            ->get();

        $this->info("Reconciling {$requests->count()} pending date-change request(s).");

        foreach ($requests as $request) {
            try {
                // retry-first: re-apply if the dates were never applied, else retry the refund.
                $outcome = $service->retrySettlement($request);
                $this->line("Request #{$request->id}: {$outcome}");
            } catch (\Throwable $e) {
                Log::channel('geidea')->warning('Date-change reconcile attempt failed', [
                    'request_id' => $request->id,
                    'error' => $e->getMessage(),
                ]);
                $this->warn("Request #{$request->id}: failed — {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
