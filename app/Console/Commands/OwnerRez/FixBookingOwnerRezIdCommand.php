<?php

namespace App\Console\Commands\OwnerRez;

use App\Models\Booking;
use App\Models\OwnerRezBooking;
use App\Services\OwnerRez\OwnerRezSyncService;
use Illuminate\Console\Command;

class FixBookingOwnerRezIdCommand extends Command
{
    protected $signature = 'ownerrez:fix-booking-ids';

    protected $description = 'Fix bookings that are missing ownerrez_booking_id and customer_id by reading from ownerrez_bookings table';

    public function handle(OwnerRezSyncService $syncService): int
    {
        $records = OwnerRezBooking::whereNotNull('ownerrez_booking_id')
            ->whereNotNull('local_booking_id')
            ->whereHas('localBooking', function ($query) {
                $query->whereNull('ownerrez_booking_id');
            })
            ->get();

        $this->info("Found {$records->count()} bookings with missing ownerrez_booking_id");

        if ($records->isEmpty()) {
            $this->info('Nothing to fix.');

            return self::SUCCESS;
        }

        $fixedCount = 0;
        $skippedCount = 0;

        foreach ($records as $record) {
            $this->line("  ┌ ownerrez_booking_id={$record->ownerrez_booking_id} → local_booking_id={$record->local_booking_id}");

            $booking = Booking::where('id', $record->local_booking_id)
                ->whereNull('ownerrez_booking_id')
                ->first();

            if (! $booking) {
                $this->warn("  └ SKIPPED: booking not found or already has ownerrez_booking_id");
                $skippedCount++;
                continue;
            }

            $updateData = ['ownerrez_booking_id' => $record->ownerrez_booking_id];

            // Fix customer if missing
            if (! $booking->customer_id) {
                $rawData = $record->raw_data;
                $guestId = $rawData['guest_id'] ?? ($rawData['guest']['id'] ?? null);
                $guestData = $rawData['guest'] ?? null;

                if ($guestId) {
                    $customer = $syncService->findOrCreateCustomerFromOwnerRez($guestId, $guestData);
                    if ($customer) {
                        $updateData['customer_id'] = $customer->id;
                        $this->line("  │ Customer synced: #{$customer->id} ({$customer->first_name} {$customer->last_name})");
                    } else {
                        $this->warn("  │ Customer not found or could not be created for guest_id={$guestId}");
                    }
                }
            }

            $booking->update($updateData);
            $this->info("  └ FIXED");
            $fixedCount++;
        }

        $this->newLine();
        $this->table(
            ['Total', 'Fixed', 'Skipped'],
            [[$records->count(), $fixedCount, $skippedCount]]
        );

        return self::SUCCESS;
    }
}
