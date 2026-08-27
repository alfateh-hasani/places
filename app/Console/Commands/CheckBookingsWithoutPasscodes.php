<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use App\Services\Locks\LockAccessService;
use Illuminate\Support\Facades\Log;
use Throwable;

class CheckBookingsWithoutPasscodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'booking:check-missing-passcodes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for approved bookings that need passcode generation';

    public function __construct(private readonly LockAccessService $locks)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('Starting check for bookings without passcodes');
        $this->info('Checking for bookings that need passcode generation...');

        // البحث عن الحجوزات المعتمدة التي تحتاج إلى باس كود
        $bookingsNeedingPasscodes = Booking::where('status', 'approved')
            ->where(function($query) {
                $query->where('passcode_status', '!=', 'generated')
                      ->orWhereNull('passcode_status');
            })
            ->where('is_airbnb_booking', '!=', 1)
            ->whereDoesntHave('smartLockPasscodes')
            ->whereDoesntHave('retryAttempt', function ($query) {
                $query->where('operation', 'provision')->where('status', 'max_attempts_reached');
            })
            ->with(['apartment.smartLock'])
            ->get();

        if ($bookingsNeedingPasscodes->isEmpty()) {
            $this->info('No bookings found that need passcode generation');
            Log::info('No bookings found that need passcode generation');
            return;
        }

        $this->info("Found {$bookingsNeedingPasscodes->count()} bookings that need passcode generation");
        Log::info("Found {$bookingsNeedingPasscodes->count()} bookings that need passcode generation");

        $processedCount = 0;
        $skippedCount = 0;

        foreach ($bookingsNeedingPasscodes as $booking) {
            // التحقق من وجود smart lock للشقة
            if (!$booking->apartment || !$booking->apartment->smartLock) {
                $this->warn("Booking #{$booking->number_of_booking} has no smart lock configured");
                $skippedCount++;
                continue;
            }

            // محاولة إنشاء الباس كود مباشرة (الفشل يُسجَّل تلقائياً في PasscodeRetryAttempt عبر LockAccessService)
            try {
                $this->locks->provisionForBooking($booking);
                $this->info("Successfully created passcode for booking #{$booking->number_of_booking}");
                $processedCount++;
            } catch (Throwable $e) {
                $this->error("Failed to create passcode for booking #{$booking->number_of_booking}: " . $e->getMessage());
                $processedCount++;
            }
        }

        $this->info("Process completed. Processed: {$processedCount}, Skipped: {$skippedCount}");
        Log::info("Check bookings without passcodes completed. Processed: {$processedCount}, Skipped: {$skippedCount}");
    }
} 