<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PasscodeRetryAttempt;
use App\Models\Booking;
use App\Models\SmartLock;
use App\Services\ScienerLockService;
use App\Models\SmartLockPasscode;
use Illuminate\Support\Facades\Log;

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
    protected $description = 'Retry failed passcode generation attempts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('Starting passcode retry process');
        $this->info('Starting passcode retry process...');

        // الحصول على المحاولات الجاهزة لإعادة المحاولة
        $attempts = PasscodeRetryAttempt::getReadyForRetry();

        if ($attempts->isEmpty()) {
            $this->info('No attempts ready for retry');
            Log::info('No attempts ready for retry');
            return;
        }

        $this->info("Found {$attempts->count()} attempts ready for retry");
        Log::info("Found {$attempts->count()} attempts ready for retry");

        $successCount = 0;
        $failedCount = 0;

        foreach ($attempts as $attempt) {
            $this->info("Processing attempt for booking #{$attempt->booking->number_of_booking}");
            
            try {
                // تحديد المحاولة كقيد التنفيذ
                $attempt->markAsInProgress();

                // التحقق من وجود smart lock للشقة
                $smartLock = SmartLock::where('apartment_id', $attempt->apartment_id)->first();
                
                if (!$smartLock) {
                    $this->error("No smart lock found for apartment {$attempt->apartment_id}");
                    $attempt->markAsFailed("No smart lock found for apartment {$attempt->apartment_id}");
                    $failedCount++;
                    continue;
                }

                // التحقق من وجود الباس كود بالفعل
                $existingPasscode = SmartLockPasscode::where('booking_id', $attempt->booking_id)->first();
                if ($existingPasscode) {
                    $this->info("Passcode already exists for booking #{$attempt->booking->number_of_booking}");
                    $attempt->markAsCompleted();
                    $attempt->booking->markPasscodeAsGenerated();
                    $successCount++;
                    continue;
                }

                // إنشاء الباس كود
                $success = $this->createPasscode($attempt);
                
                if ($success) {
                    $attempt->markAsCompleted();
                    $attempt->booking->markPasscodeAsGenerated();
                    $successCount++;
                    $this->info("Successfully created passcode for booking #{$attempt->booking->number_of_booking}");
                } else {
                    $attempt->booking->markPasscodeAsFailed("Failed in retry attempt #{$attempt->attempt_count}");
                    $failedCount++;
                    $this->error("Failed to create passcode for booking #{$attempt->booking->number_of_booking}");
                }

            } catch (\Exception $e) {
                $attempt->markAsFailed($e->getMessage());
                $attempt->booking->markPasscodeAsFailed($e->getMessage());
                $failedCount++;
                $this->error("Error processing attempt for booking #{$attempt->booking->number_of_booking}: " . $e->getMessage());
                Log::error("Error processing passcode retry: " . $e->getMessage());
            }
        }

        $this->info("Retry process completed. Success: {$successCount}, Failed: {$failedCount}");
        Log::info("Retry process completed. Success: {$successCount}, Failed: {$failedCount}");
    }

    /**
     * إنشاء الباس كود للحجز
     */
    private function createPasscode(PasscodeRetryAttempt $attempt)
    {
        try {
            $booking = $attempt->booking;
            $smartLock = SmartLock::where('apartment_id', $attempt->apartment_id)->first();

            if (!$smartLock) {
                return false;
            }

            // إنشاء خدمة Sciener
            $scienerService = new ScienerLockService(
                $smartLock->sciener_username,
                $smartLock->sciener_password
            );


            $startDate = $booking->check_in->format('Y-m-d') .' '.$booking->check_in_time->format('H:i:s'); 
            $endDate = $booking->check_out->format('Y-m-d') .' '.$booking->check_out_time->format('H:i:s'); 


            // إنشاء الباس كود
            $passcodeData = $scienerService->getPasscode(
                $smartLock->lock_id,
                $startDate,
                $endDate,
                "Booking #{$booking->number_of_booking}"
            );

            if (!$passcodeData || !isset($passcodeData['keyboardPwd'])) {
                $errorMessage = "Failed to generate passcode from Sciener API";
                $attempt->markAsFailed($errorMessage);
                return false;
            }

            // حفظ الباس كود في قاعدة البيانات
            SmartLockPasscode::create([
                'smart_lock_id' => $smartLock->id,
                'apartment_id' => $attempt->apartment_id,
                'customer_id' => $attempt->customer_id,
                'booking_id' => $attempt->booking_id,
                'nickname' => "Booking #{$booking->number_of_booking}",
                'keyboard_pwd' => $passcodeData['keyboardPwd'],
                'start_date' => $booking->check_in->format('Y-m-d 15:00:00'),
                'end_date' => $booking->check_out->format('Y-m-d 12:00:00'),
            ]);

            Log::info("Successfully created passcode for booking #{$booking->number_of_booking}");
            return true;

        } catch (\Exception $e) {
            $attempt->markAsFailed($e->getMessage());
            Log::error("Error creating passcode: " . $e->getMessage());
            return false;
        }
    }
} 