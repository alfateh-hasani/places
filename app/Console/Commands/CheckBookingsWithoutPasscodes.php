<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use App\Models\SmartLockPasscode;
use App\Models\PasscodeRetryAttempt;
use App\Services\BookingService;
use Illuminate\Support\Facades\Log;

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
                $query->where('status', 'max_attempts_reached');
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

            // إنشاء محاولة إعادة إذا لم تكن موجودة
            $retryAttempt = PasscodeRetryAttempt::firstOrCreate(
                ['booking_id' => $booking->id],
                [
                    'apartment_id' => $booking->apartment_id,
                    'customer_id' => $booking->customer_id,
                    'attempt_count' => 0,
                    'max_attempts' => 100,
                    'status' => 'pending',
                    'next_attempt_at' => now()->addMinutes(5), // محاولة بعد 5 دقائق
                ]
            );

            // محاولة إنشاء الباس كود مباشرة
            try {
                $bookingService = app(BookingService::class);
                $bookingService->addPasscodeToSmartLock($booking);
                $this->info("Successfully created passcode for booking #{$booking->number_of_booking}");
                $processedCount++;
            } catch (\Exception $e) {
                $this->error("Failed to create passcode for booking #{$booking->number_of_booking}: " . $e->getMessage());
                $processedCount++;
            }
        }

        $this->info("Process completed. Processed: {$processedCount}, Skipped: {$skippedCount}");
        Log::info("Check bookings without passcodes completed. Processed: {$processedCount}, Skipped: {$skippedCount}");
    }
} 