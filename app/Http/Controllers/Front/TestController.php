<?php

namespace App\Http\Controllers\Front;

 
use App\Http\Controllers\Controller;
use App\Services\ScienerLockService;
use Carbon\Carbon;
use App\Services\BookingService;
use App\Models\Booking;

class TestController extends Controller
{ 
    protected $scienerLockService;
    protected $bookingService;

    public function __construct(ScienerLockService $scienerLockService ,BookingService $bookingService)
    {
        $this->scienerLockService = $scienerLockService;
        $this->bookingService = $bookingService;
    }
  
    public function index() 
    {

        $booking = Booking::where('id',8)->first();
        $this->bookingService->addPasscodeToSmartLock($booking);

        dd($booking);
         
        // $datetime = Carbon::createFromTimestampMs('1738159200000', 'UTC');
        // dd( $datetime->format('Y-m-d H:i:s'));

        
        // $lockId = 18860573; // ID الخاص بالقفل
        // $keyboardPwd = '123413'; // الرمز بطول 6 أرقام
        // $startDate = '2025-01-27 12:50:00'; // تاريخ البداية (اختياري)
        // $endDate = '2025-01-29 15:00:00'; // تاريخ النهاية (اختياري)
 
        // // استدعاء الميثود
        // $response =$this->scienerLockService->addCustomPasscode($lockId, $keyboardPwd, $startDate, $endDate);

        // if ($response) {
        //     echo "Passcode ID: " . $response['keyboardPwdId'];
        // } else {
        //     echo "Failed to add custom passcode.";
        // }

        
        
    }
 
}
