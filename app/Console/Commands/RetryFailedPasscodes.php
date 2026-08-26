<?php

namespace App\Console\Commands;

use App\Models\PasscodeRetryAttempt;
use App\Services\Locks\LockAccessService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class RetryFailedPasscodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passcode:retry-failed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry failed smart-lock passcode operations (provisioning and revocation)';

    public function __construct(private readonly LockAccessService $locks)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $attempts = PasscodeRetryAttempt::getReadyForRetry();

        if ($attempts->isEmpty()) {
            $this->info('No attempts ready for retry');

            return;
        }

        $this->info("Found {$attempts->count()} attempts ready for retry");

        $successCount = 0;
        $failedCount = 0;

        foreach ($attempts as $attempt) {
            $booking = $attempt->booking;

            if (! $booking) {
                $attempt->delete();

                continue;
            }

            $attempt->markAsInProgress();

            try {
                if ($attempt->operation === 'revoke') {
                    $this->locks->revokeForBooking($booking, 'retry command');
                } else {
                    $this->locks->provisionForBooking($booking);
                }

                $attempt->markAsCompleted();
                $successCount++;
                $this->info("Successfully retried {$attempt->operation} for booking #{$booking->number_of_booking}");
            } catch (Throwable $e) {
                $attempt->markAsFailed($e->getMessage());
                $failedCount++;
                $this->error("Failed to retry {$attempt->operation} for booking #{$booking->number_of_booking}: ".$e->getMessage());
                Log::error('Error processing passcode retry', [
                    'booking_id' => $booking->id,
                    'operation' => $attempt->operation,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Retry process completed. Success: {$successCount}, Failed: {$failedCount}");
    }
}
