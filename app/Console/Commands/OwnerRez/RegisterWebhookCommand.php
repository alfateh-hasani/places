<?php

namespace App\Console\Commands\OwnerRez;

use App\Services\OwnerRez\OwnerRezApiService;
use Illuminate\Console\Command;

class RegisterWebhookCommand extends Command
{
    protected $signature = 'ownerrez:register-webhook';

    protected $description = 'Register webhook with OwnerRez';

    public function handle(OwnerRezApiService $apiService): int
    {
        $webhookUrl = route('ownerrez.webhook');
        $this->info("Registering webhook: {$webhookUrl}");

        try {
            $response = $apiService->registerWebhook(
                $webhookUrl,
                ['booking'],
                'entity_create,entity_update,entity_delete'
            );

            $this->info('Webhook registered successfully!');
            $this->line('Webhook ID: '.($response['id'] ?? 'N/A'));

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to register webhook: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
