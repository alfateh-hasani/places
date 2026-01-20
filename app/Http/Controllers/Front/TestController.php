<?php

namespace App\Http\Controllers\Front;

 
use App\Http\Controllers\Controller;
use App\Services\ScienerLockService;
use Carbon\Carbon;
use App\Services\BookingService;
use App\Models\Booking;
use App\Services\OwnerRez\OwnerRezApiService;

class TestController extends Controller
{ 
    protected $scienerLockService;
    protected $bookingService;
    protected $apiService;
    public function __construct( OwnerRezApiService $apiService)
    {
        $this->apiService = $apiService;
    }
  
    public function index() 
    {   

        return ; 

        $fullBookingData = $this->apiService->getBooking(15863300);
        dd($fullBookingData);
        exit; 
 
        
        
    }
 
}
