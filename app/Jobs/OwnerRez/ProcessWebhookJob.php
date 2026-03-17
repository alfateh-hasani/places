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

    /**
     * Exponential backoff: 10s, 30s, 90s
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 30, 90];
    }

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
                'event' => $this->webhookData['event'] ?? null,
                'action' => $this->webhookData['action'] ?? null,
                'entity_id' => $this->webhookData['entity_id'] ?? null,
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('OwnerRez webhook job failed after all retries', [
            'error' => $exception->getMessage(),
            'event' => $this->webhookData['event'] ?? null,
            'action' => $this->webhookData['action'] ?? null,
            'entity_id' => $this->webhookData['entity_id'] ?? null,
        ]);
    }
}
