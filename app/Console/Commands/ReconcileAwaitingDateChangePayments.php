<?php

namespace App\Console\Commands;

use App\Enums\DateChangeStatus;
use App\Models\DateChangeRequest;
use App\Services\DateChangeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReconcileAwaitingDateChangePayments extends Command
{
    protected $signature = 'date-changes:reconcile-awaiting-payment';

    protected $description = 'Apply date-change requests whose surcharge was paid (transaction completed) but never applied — e.g. the customer never returned to the browser callback and the webhook missed it. Idempotent safety net for confirmSurchargePayment().';

    public function handle(DateChangeService $service): int
    {
        $stuck = DateChangeRequest::query()
            ->where('status', DateChangeStatus::AwaitingPayment->value)
            ->whereHas('transaction', fn ($q) => $q->where('status', 'completed'))
            ->get();

        $this->info("Reconciling {$stuck->count()} paid-but-unapplied date-change request(s).");

        foreach ($stuck as $request) {
            try {
                $service->confirmSurchargePayment($request->fresh());
                $this->line("Request #{$request->id}: applied");
            } catch (\Throwable $e) {
                Log::channel('geidea')->warning('Reconcile awaiting date-change payment failed', [
                    'request_id' => $request->id,
                    'error' => $e->getMessage(),
                ]);
                $this->warn("Request #{$request->id}: failed — {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
