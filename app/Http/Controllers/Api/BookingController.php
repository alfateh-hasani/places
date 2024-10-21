<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Policy;
use App\Models\Booking;
use App\Services\BookingService;
use Auth;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    protected $booking;
    protected $bookingService;

    public function __construct(BookingService $bookingService, Booking $booking)
    {
        $this->booking =  $booking;
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

    //getBookingStatus

    public function determineBookingStatus(Request $request)
    {

        $request->validate([
            'apartment_id' => 'required|exists:apartments,id',
            'check_in' => 'required|date',
            'check_out' => 'required|date',
            'number_of_adults' => 'required|integer|min:1',
            'number_of_children' => 'required|integer|min:0',
        ]);
        $apartment = Apartment::findOrFail($request->apartment_id);
        $this->bookingService->checkAvailability($apartment, $request->check_in, $request->check_out);
        $this->bookingService->validateGuestsCount($apartment, $request->number_of_adults, $request->number_of_children);
        $data = $this->bookingService->getDetermineBooking($apartment, $request->check_in, $request->check_out);
        $payment_methods = Policy::where('type', 'booking')->first();
        $data['policy_title'] = $payment_methods?->{'name_' . app()->getLocale()};
        $data['policy_description'] =$payment_methods?->{'description_' . app()->getLocale()};

        $data['payment_details'] = [];
        return  $this->successResponse($data);
    }

    //calculatePrice
     public function calculatePriceWithCoupon(Request $request){

        $request->validate([
            'apartment_id' => 'required|exists:apartments,id',
            'coupon_code' => 'required|exists:coupons,code',
            'number_of_nights' => 'required|integer|min:1',
        ]);
        $apartment = Apartment::findOrFail($request->apartment_id);
        $coupon = $this->bookingService->validateCoupon($apartment, $request->coupon_code);
        $data = $this->bookingService->calculatePrices($apartment->price, $request->number_of_nights, $coupon);
        return  $this->successResponse($data);
     }
}
