<?php

namespace App\Jobs\OwnerRez;

use App\Models\Customer;
use App\Services\OwnerRez\OwnerRezSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncCustomerToOwnerRezJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public int $customerId
    ) {}

    public function handle(OwnerRezSyncService $syncService): void
    {
        $customer = Customer::find($this->customerId);

        if (! $customer) {
            Log::warning('Customer not found for OwnerRez sync', ['customer_id' => $this->customerId]);

            return;
        }

        // Check if sync is enabled
        if (! config('ownerrez.sync.sync_guest_data')) {
            Log::info('OwnerRez guest sync is disabled', ['customer_id' => $this->customerId]);

            return;
        }

        // Skip if customer doesn't have email
        if (! $customer->email) {
            Log::info('Customer has no email, skipping OwnerRez sync', ['customer_id' => $this->customerId]);

            return;
        }

        try {
            Log::info('Syncing customer to OwnerRez', ['customer_id' => $this->customerId]);

            $guestId = $syncService->createOwnerRezGuest($customer);

            Log::info('Successfully synced customer to OwnerRez', [
                'customer_id' => $this->customerId,
                'ownerrez_guest_id' => $guestId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync customer to OwnerRez', [
                'customer_id' => $this->customerId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Sync customer to OwnerRez job failed after all retries', [
            'customer_id' => $this->customerId,
            'error' => $exception->getMessage(),
        ]);
    }
}
