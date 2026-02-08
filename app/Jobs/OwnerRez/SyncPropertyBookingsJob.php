<?php

namespace App\Jobs\OwnerRez;

use App\Models\OwnerRezPropertyMapping;
use App\Services\OwnerRez\OwnerRezApiService;
use App\Services\OwnerRez\OwnerRezSyncService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncPropertyBookingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public int $propertyMappingId,
        public ?string $fromDate = null,
        public ?string $toDate = null
    ) {}

    public function handle(OwnerRezApiService $apiService, OwnerRezSyncService $syncService): void
    {
        $logger = Log::channel('ownerrez');
        $mapping = OwnerRezPropertyMapping::find($this->propertyMappingId);

        if (! $mapping || ! $mapping->sync_enabled) {
            $logger->warning('Property mapping not found or sync disabled', [
                'mapping_id' => $this->propertyMappingId,
            ]);

            return;
        }

        try {
            $logger->info('Syncing bookings for property', [
                'mapping_id' => $this->propertyMappingId,
                'property_id' => $mapping->ownerrez_property_id,
            ]);

            $from = $this->fromDate ?? Carbon::now()->format('Y-m-d');
            $to = $this->toDate ?? Carbon::now()->addYear()->format('Y-m-d');

            $response = $apiService->getBookings([
                'property_ids' => $mapping->ownerrez_property_id,
                'from' => $from,
                'to' => $to,
                'status' => 'Active',
            ]);

            $bookings = $response['items'] ?? [];
            $logger->info('OwnerRez fetch bookings result', [
                'mapping_id' => $this->propertyMappingId,
                'property_id' => $mapping->ownerrez_property_id,
                'from' => $from,
                'to' => $to,
                'status' => 'Active',
                'fetched_count' => count($bookings),
                'next_page_url' => $response['next_page_url'] ?? null,
            ]);
            $syncedCount = 0;

            foreach ($bookings as $bookingData) {
                try {
                    $syncService->syncBookingFromWebhook([
                        'action' => 'entity_create',
                        'data' => $bookingData,
                    ]);
                    $syncedCount++;
                } catch (\Exception $e) {
                    $logger->error('Failed to sync individual booking', [
                        'ownerrez_booking_id' => $bookingData['id'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $mapping->markAsSynced();

            $logger->info('Successfully synced property bookings', [
                'mapping_id' => $this->propertyMappingId,
                'total_bookings' => count($bookings),
                'synced_count' => $syncedCount,
            ]);
        } catch (\Exception $e) {
            $logger->error('Failed to sync property bookings', [
                'mapping_id' => $this->propertyMappingId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('ownerrez')->error('Sync property bookings job failed after all retries', [
            'mapping_id' => $this->propertyMappingId,
            'error' => $exception->getMessage(),
        ]);
    }
}
