<?php

namespace App\Console\Commands;

use App\Actions\Refunds\ProcessBookingRefund;
use App\Models\Booking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReconcilePendingRefunds extends Command
{
    protected $signature = 'refunds:reconcile {--minutes=10 : Only retry refunds whose last attempt is older than this} {--max-attempts=10}';

    protected $description = 'Re-run stuck Geidea refunds (processing/failed) — idempotent; resolves async refunds and retries failures.';

    public function handle(ProcessBookingRefund $action): int
    {
        $threshold = now()->subMinutes((int) $this->option('minutes'));
        $maxAttempts = (int) $this->option('max-attempts');

        $bookings = Booking::whereIn('refund_status', ['processing', 'failed'])
            ->where('refund_attempts', '<', $maxAttempts)
            ->where(function ($q) use ($threshold): void {
                $q->whereNull('last_refund_attempt_at')
                    ->orWhere('last_refund_attempt_at', '<=', $threshold);
            })
            ->get();

        $this->info("Reconciling {$bookings->count()} pending refund(s).");

        foreach ($bookings as $booking) {
            try {
                $outcome = $action->execute($booking);
                $this->line("Booking {$booking->number_of_booking}: {$outcome}");
            } catch (\Throwable $e) {
                Log::channel('geidea')->warning('Refund reconcile attempt failed', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
                $this->warn("Booking {$booking->number_of_booking}: failed — {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
