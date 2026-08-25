<?php

namespace App\Jobs;

use App\Actions\Refunds\ProcessBookingRefund;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessGeideaRefundJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 120;

    public function __construct(
        public int $bookingId
    ) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300, 900, 1800];
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('refund-'.$this->bookingId))->releaseAfter(60)];
    }

    public function handle(ProcessBookingRefund $action): void
    {
        $booking = Booking::find($this->bookingId);

        if (! $booking) {
            Log::warning('ProcessGeideaRefundJob: booking not found', ['booking_id' => $this->bookingId]);

            return;
        }

        $action->execute($booking);
    }

    public function failed(\Throwable $exception): void
    {
        $booking = Booking::find($this->bookingId);

        if ($booking && $booking->refund_status !== 'approved') {
            $booking->forceFill([
                'refund_status' => 'failed',
                'refund_error' => $exception->getMessage(),
            ])->save();
        }

        Log::channel('geidea')->error('ProcessGeideaRefundJob failed after retries', [
            'booking_id' => $this->bookingId,
            'error' => $exception->getMessage(),
        ]);
    }
}
