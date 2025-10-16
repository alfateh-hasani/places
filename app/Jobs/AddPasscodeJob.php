<?php

namespace App\Jobs;

use App\Services\ScienerLockService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AddPasscodeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $lockId;
    protected $keyboardPwd;
    protected $startDate;
    protected $endDate;
    protected $keyboardPwdName;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($lockId, $keyboardPwd, $startDate, $endDate, $keyboardPwdName = 'Booking Passcode')
    {
        $this->lockId = $lockId;
        $this->keyboardPwd = $keyboardPwd;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->keyboardPwdName = $keyboardPwdName;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ScienerLockService $scienerLockService)
    {
        try {
            // استدعاء خدمة Sciener لإضافة رمز المرور
            $response = $scienerLockService->addCustomPasscode(
                $this->lockId,
                $this->keyboardPwd,
                $this->startDate,
                $this->endDate,
                $this->keyboardPwdName
            );

            if ($response) {
                Log::info('Passcode added successfully', ['response' => $response]);
            } else {
                Log::error('Failed to add passcode.');
            }
        } catch (\Exception $e) {
            Log::error('Error in AddPasscodeJob: ' . $e->getMessage());
        }
    }
}
