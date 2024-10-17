<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Reservation;
use App\Services\BookingService;
use Auth;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    protected $reservation;
    protected $bookingService;

    public function __construct(BookingService $bookingService,Reservation $reservation)
    {
        $this->reservation =  $reservation;
        $this->bookingService = $bookingService;
    }

    public function index()
    {

    }

    //addBooking

    public function addBooking(Request $request)
    {
        $validatedData = $request->validate([
            'apartment_id' => 'required|exists:apartments,id',
            'check_in' => 'required|date',
            'check_out' => 'required|date',
            'adults_count' => 'required|integer|min:1',
            'children_count' => 'required|integer|min:0',
            'number_of_nights' => 'required|integer|min:1',
            'coupon_id' => 'nullable|exists:coupons,id',
            'notes' => 'nullable|string',
        ]);
        try {
            $customer = Auth::guard('api')->user();
            $apartment = Apartment::findOrFail($validatedData['apartment_id']);
            $this->bookingService->validateCoupon($apartment, $validatedData['coupon_id']);
            $this->bookingService->checkAvailability($apartment, $validatedData['check_in'], $validatedData['check_out']);
            $bookingSource = 'mobile_app';
            $booking = $this->bookingService->createBooking($validatedData, $customer, $apartment, $bookingSource);
            return  $this->successResponse($booking, __('api.booking_added'));
        }catch (\Exception $exception){
            return  $this->errorResponse($exception->getMessage());
        }

    }
}
