<?php

namespace App\Console\Commands\OwnerRez;

use App\Models\Booking;
use App\Services\OwnerRez\OwnerRezSyncService;
use Illuminate\Console\Command;

class SetCustomFieldsCommand extends Command
{
    protected $signature = 'ownerrez:set-custom-fields {--booking-id= : Specific local booking ID to process}';

    protected $description = 'Set custom field on OwnerRez bookings created from this application';

    public function handle(OwnerRezSyncService $syncService): int
    {
        $bookingId = $this->option('booking-id');

        $query = Booking::whereNotNull('ownerrez_booking_id')
            ->whereNotIn('booking_source', ['ownerrez', 'airbnb', 'booking_com', 'guesty'])
            ->where('created_at', '>=', now()->subMonths(2));

        if ($bookingId) {
            $query->where('id', $bookingId);
        }

        $bookings = $query->get();

        if ($bookings->isEmpty()) {
            $this->info('No bookings found to process.');

            return self::SUCCESS;
        }

        $this->info("Processing {$bookings->count()} booking(s)...");
        $bar = $this->output->createProgressBar($bookings->count());
        $bar->start();

        $created = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($bookings as $booking) {
            try {
                $syncService->ensureBookingCustomField((int) $booking->ownerrez_booking_id);
                $created++;
            } catch (\Exception $e) {
                $failed++;
                $this->newLine();
                $this->error("Failed for booking #{$booking->id} (OwnerRez: {$booking->ownerrez_booking_id}): {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Done! Processed: {$created}, Failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
