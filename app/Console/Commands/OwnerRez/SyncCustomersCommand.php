<?php

namespace App\Console\Commands\OwnerRez;

use App\Models\Customer;
use App\Services\OwnerRez\OwnerRezApiService;
use Illuminate\Console\Command;

class SyncCustomersCommand extends Command
{
    protected $signature = 'ownerrez:sync-customers';

    protected $description = 'Sync customers data from OwnerRez using their guest ID';

    public function handle(OwnerRezApiService $apiService): int
    {
        $customers = Customer::whereNotNull('ownerrez_guest_id')->get();

        $this->info("Found {$customers->count()} customers with OwnerRez guest ID");

        $updatedCount = 0;
        $skippedCount = 0;
        $failedCount = 0;

        foreach ($customers as $customer) {
            $this->line("  ┌ Customer #{$customer->id} | ownerrez_guest_id: {$customer->ownerrez_guest_id}");

            try {
                $guestData = $apiService->getGuest($customer->ownerrez_guest_id);

                $phone = isset($guestData['phones'][0]['number'])
                    ? str_replace(' ', '', $guestData['phones'][0]['number'])
                    : null;

                $email = $guestData['email_addresses'][0]['address'] ?? null;

                // Skip email if already used by another customer
                $emailAlreadyTaken = $email && Customer::where('email', $email)
                    ->where('id', '!=', $customer->id)
                    ->exists();

                $updateData = array_filter([
                    'first_name' => $guestData['first_name'] ?? null,
                    'last_name' => $guestData['last_name'] ?? null,
                    'email' => $emailAlreadyTaken ? null : $email,
                    'phone' => $phone,
                ]);

                if (empty($updateData)) {
                    $this->warn("  └ SKIPPED: no data to update");
                    $skippedCount++;
                    continue;
                }

                // updateQuietly to avoid triggering SyncCustomerToOwnerRezJob
                $customer->updateQuietly($updateData);

                $this->info("  └ UPDATED: name={$customer->first_name} {$customer->last_name} | phone={$phone}");
                $updatedCount++;
            } catch (\Exception $e) {
                $this->error("  └ FAILED: {$e->getMessage()}");
                $failedCount++;
            }
        }

        $this->newLine();
        $this->table(
            ['Total', 'Updated', 'Skipped', 'Failed'],
            [[$customers->count(), $updatedCount, $skippedCount, $failedCount]]
        );

        return self::SUCCESS;
    }
}
