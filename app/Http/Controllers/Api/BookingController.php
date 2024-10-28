<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Apartment;
use App\Models\Policy;
use App\Models\Booking;
use App\Services\BookingService;
use Auth;
use Illuminate\Http\Request;
use App\Services\ProcessPaymentService;
class BookingController extends Controller
{
    protected $booking;
    protected $bookingService;

    public function __construct(BookingService $bookingService, Booking $booking)
    {
        $this->booking = $booking;
        $this->bookingService = $bookingService;
    }

    public function getBooking(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
        ]);
        $booking = $this->booking->where([
            ['id', $request->booking_id],
            ['customer_id', Auth::guard('api')->id()]
        ])->first();
        if (!$booking) {
            return $this->errorResponse(__('api.booking_not_found'));
        }
        $this->data['booking'] = new BookingResource($booking);
        return $this->successResponse($this->data);
    }

    //loginApartment
    public function loginApartment(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
        ]);
        $booking = $this->booking->where([
            ['id', $request->booking_id],
            ['customer_id', Auth::guard('api')->id()],
            ['check_out' ,'>', now()]
        ])->first();
        if (!$booking) {
            return $this->errorResponse(__('api.booking_not_found'));
        }
        $this->data['login_info'] = [
            'unit_number' => $booking->apartment?->unit_number,
            'floor_number' =>  $booking->apartment?->floor_number,
            'passcode' => $booking->apartment?->lock?->lock_id,
            'lock_alias' => $booking->apartment?->lock?->lock_alias,
        ];
        return $this->successResponse($this->data);
    }

    public function addBooking(Request $request)
    {

        $validatedData = $request->validate([
            'apartment_id' => 'required|exists:apartments,id',
            'check_in' => 'required|date',
            'check_out' => 'required|date',
            'adults_count' => 'required|integer|min:1',
            'children_count' => 'required|integer|min:0',
            'coupon_code' => 'nullable|exists:coupons,code',
            'notes' => 'nullable|string',
            'booking_source' => 'nullable|in:,web', 'android', 'ios',
            'payment_method_code' => 'required',
        ]);
        try {
            $customer = Auth::guard('api')->user();
            $apartment = Apartment::findOrFail($validatedData['apartment_id']);
            $this->bookingService->checkAvailability($apartment, $validatedData['check_in'], $validatedData['check_out']);
            $paymentResponse = $this->bookingService->createPayment($validatedData, $customer, $apartment);            
            if (is_array($paymentResponse) && isset($paymentResponse['transaction']['url'])) {
                $this->data['callback'] = $paymentResponse['transaction']['url'];
                return $this->successResponse($this->data, __('api.transaction_url'));
            } else {
                return $this->errorResponse(__('api.payment_creation_failed'));
            }
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage());
        }
        

    }




    public function paymentMethodCallBack(Request $request , $paymentMethodCode , $transaction_id){
        if(!in_array($paymentMethodCode,array_keys(config('payments.gateways')))){
            return $this->errorResponse(['Payment Method not Exists!']);
        }
        $processPaymentService = new ProcessPaymentService();
        $data = $request->all();
        $data['transaction_id'] = $transaction_id;
        $handlePayment = $processPaymentService->handleCallBack($paymentMethodCode , $data);
        if($handlePayment['status']==true){
          $booking =  $this->bookingService->createBooking($transaction_id,$handlePayment['payment_id']);
          $this->data['booking'] = $booking->id;
          return redirect(route('paymentMethodSuccess',['booking_id'=>$booking->id,'booking_number'=>$booking->booking_number]));
        }
        return redirect(route('paymentMethodFailed'));
    }

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
        $data['policy_description'] = $payment_methods?->{'description_' . app()->getLocale()};

        $data['payment_details'] = $this->getPaymentDetails();
        return $this->successResponse($data);
    }

    //calculatePrice
    public function calculatePriceWithCoupon(Request $request)
    {

        $request->validate([
            'apartment_id' => 'required|exists:apartments,id',
            'coupon_code' => 'required|exists:coupons,code',
            'number_of_nights' => 'required|integer|min:1',
        ]);
        $apartment = Apartment::findOrFail($request->apartment_id);
        $coupon = $this->bookingService->validateCoupon($apartment, $request->coupon_code);
        $data = $this->bookingService->calculatePrices($apartment->price, $request->number_of_nights, $coupon);
        return $this->successResponse($data);
    }

    //calculatePriceWithOutCoupon
    public function calculatePriceWithOutCoupon(Request $request)
    {

        $request->validate([
            'apartment_id' => 'required|exists:apartments,id',
            'number_of_nights' => 'required|integer|min:1',
        ]);
        $apartment = Apartment::findOrFail($request->apartment_id);
        $data = $this->bookingService->calculatePrices($apartment->price, $request->number_of_nights);
        return $this->successResponse($data);
    }


    //  getBookingViaCustomer
    public function getBookingViaCustomer()
    {
        $customer = Auth::guard('api')->user();
        $bookings = $this->booking->where('customer_id', $customer->id)->latest()->get();
        $this->data['bookings'] =  BookingResource::collection($bookings);
        return $this->successResponse($this->data);
    }


    private function getPaymentDetails()
    {
        $paymentMethods = [];
        foreach (config('payments.gateways') as $gateway => $gatewayData) {
            $paymentMethods[] = [
                'name' => $gateway,
                'icon' => asset('images/' . $gatewayData['icon'] . '.svg'),
                'value' => $gatewayData['value'],
            ];
        }
        return $paymentMethods;
    }


    //paymentMethodSuccess
    public function paymentMethodSuccess($booking_id)
    {
        $data ['booking_id'] = $booking_id;
        $data ['booking_number'] = $this->booking->find($booking_id)->booking_number;
         return $this->successResponse($data);
    }

    public function paymentMethodFailed()
    {
        return $this->errorResponse('Payment Failed');
    }


    //entryApartment random true or false
    public function entryApartment(Request $request)
    {
        $this->data['entry'] = (bool)random_int(0, 1);
        return $this->successResponse($this->data);
    }

}
