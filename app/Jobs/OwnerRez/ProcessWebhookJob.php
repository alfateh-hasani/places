<?php

namespace App\Jobs\OwnerRez;

use App\Services\OwnerRez\OwnerRezSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public array $webhookData
    ) {}

    public function handle(OwnerRezSyncService $syncService): void
    {
        try {
            Log::info('Processing OwnerRez webhook', [
                'event' => $this->webhookData['event'] ?? null,
                'action' => $this->webhookData['action'] ?? null,
            ]);

            $syncService->syncBookingFromWebhook($this->webhookData);

            Log::info('Successfully processed OwnerRez webhook');
        } catch (\Exception $e) {
            Log::error('Failed to process OwnerRez webhook', [
                'error' => $e->getMessage(),
                'webhook_data' => $this->webhookData,
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('OwnerRez webhook job failed after all retries', [
            'error' => $exception->getMessage(),
            'webhook_data' => $this->webhookData,
        ]);
    }
}
