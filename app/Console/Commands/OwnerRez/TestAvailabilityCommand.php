<?php

namespace App\Console\Commands\OwnerRez;

use App\Models\Apartment;
use App\Services\OwnerRez\OwnerRezSyncService;
use Illuminate\Console\Command;

class TestAvailabilityCommand extends Command
{
    protected $signature = 'ownerrez:test-availability {apartment_id} {check_in} {check_out}';

    protected $description = 'Test availability check for an apartment';

    public function handle(OwnerRezSyncService $syncService): int
    {
        $apartmentId = $this->argument('apartment_id');
        $checkIn = $this->argument('check_in');
        $checkOut = $this->argument('check_out');

        $apartment = Apartment::find($apartmentId);
        if (! $apartment) {
            $this->error("Apartment not found: {$apartmentId}");

            return self::FAILURE;
        }

        $mapping = $apartment->ownerrezMapping;
        if (! $mapping) {
            $this->error('Apartment not mapped to OwnerRez');

            return self::FAILURE;
        }

        $this->info("Testing availability for: {$apartment->{'name_'.app()->getLocale()}}");
        $this->info("OwnerRez Property ID: {$mapping->ownerrez_property_id}");
        $this->info("Check-in: {$checkIn}");
        $this->info("Check-out: {$checkOut}");

        try {
            $isAvailable = $syncService->checkAvailability(
                $mapping->ownerrez_property_id,
                $checkIn,
                $checkOut
            );

            if ($isAvailable) {
                $this->info('✓ Apartment is AVAILABLE');
            } else {
                $this->warn('✗ Apartment is NOT AVAILABLE');

                $bookings = $syncService->getActiveBookings(
                    $mapping->ownerrez_property_id,
                    $checkIn,
                    $checkOut
                );

                $this->line("\nConflicting bookings:");
                foreach ($bookings as $booking) {
                    $this->line("- {$booking['arrival']} to {$booking['departure']} (ID: {$booking['id']})");
                }
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
