<?php

namespace App\Console\Commands\OwnerRez;

use App\Services\OwnerRez\OwnerRezApiService;
use Illuminate\Console\Command;

class SyncPropertiesCommand extends Command
{
    protected $signature = 'ownerrez:sync-properties';

    protected $description = 'Sync properties from OwnerRez';

    public function handle(OwnerRezApiService $apiService): int
    {
        $this->info('Fetching properties from OwnerRez...');

        try {
            $response = $apiService->getProperties();
            $properties = $response['items'] ?? [];

            $this->info('Found '.count($properties).' properties');

            foreach ($properties as $property) {
                $this->line("- {$property['name']} (ID: {$property['id']})");
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to fetch properties: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
