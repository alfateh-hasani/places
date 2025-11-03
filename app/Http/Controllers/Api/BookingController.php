<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApartmentResource;
use App\Http\Resources\BookingResource;
use App\Models\Apartment;
use App\Models\Policy;
use App\Models\Booking;
use App\Models\Review;
use App\Models\Service;
use App\Services\BookingService;
use App\Services\Pricing\PricingService;
use Auth;
use Mail;
use Illuminate\Http\Request;
use App\Services\ProcessPaymentService;
use App\Models\Building;


class BookingController extends Controller{
    protected $booking;
    protected $bookingService;
    protected $pricingService;

    public function __construct(BookingService $bookingService, Booking $booking, PricingService $pricingService)
    {
        $this->booking = $booking;
        $this->bookingService = $bookingService;
        $this->pricingService = $pricingService;
    }

    public function getBooking(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
        ]);
        $customer = Auth::guard('api')->user();
        $booking = $this->booking->where([
            ['id', $request->booking_id],
            ['customer_id', $customer->id]
        ])->first();
        if (!$booking) {
            return $this->errorResponse(__('api.booking_not_found'));
        }

        $this->data['booking'] = new BookingResource($booking);
        $this->data['customer_services_title'] = __('booking.customer_services_title');
        $this->data['customer_services_text1'] = __('booking.customer_services_text1');
        $this->data['customer_services_text2'] = __('booking.customer_services_text2');
        
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

        $active_passcode = $booking->getActivePasscode();

        $this->data['login_info'] = [
            'unit_number' => $booking->apartment?->unit_number,
            'floor_number' =>  $booking->apartment?->floor_number,
            'passcode' =>  $active_passcode?->keyboard_pwd ?? __('booking.no_passcode'),
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
            $paymentResponse = $this->bookingService->createPayment($validatedData, $customer, $apartment , 'api');            
            if (is_array($paymentResponse) && isset($paymentResponse['transaction']['url'])) {
                $this->data['callback'] = $paymentResponse['transaction']['url'];
                $this->data['booking_id'] = $paymentResponse['booking_id'];
                return $this->successResponse($this->data, __('api.transaction_url'));
            } else {
                return $this->errorResponse(__('api.payment_creation_failed'));
            }
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage());
        }
        

    }

    public function cancelBookingPayment(Request $request , $booking_id)
    {
        $customer = Auth::guard('api')->user();
         
        $booking =  Booking::where([
            ['id', $booking_id],
            ['customer_id', $customer->id]
        ])->where('status','pending')->first();
        if (!$booking) {
            return $this->errorResponse(__('api.booking_not_found'));
        }
        $booking->delete();
        return $this->successResponse(__('api.booking_cancelled'));
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
        //   $booking =  $this->bookingService->createBooking($transaction_id,$handlePayment['payment_id']);
            $booking =  $this->booking->where('transaction_id',$transaction_id)->first();
            $booking->status = 'approved';
            $booking->payment_status = 'paid';
            $booking->payment_method_code = $paymentMethodCode;
            $booking->save();
            $booking = $booking->refresh();
          $this->data['booking'] = $booking->id;    

          try {
            Mail::to($booking->customer_email)->send(new \App\Mail\ReservationDetails($booking));

            $building = Building::where('id', $booking?->apartment?->building_id)->first();
            if ($building) {
                $superVisor = \App\Models\User::where('id', $building->supervisor_id)->first();
                $superVisorEmail = $superVisor->email;
                if($superVisorEmail) {
                    Mail::to($superVisorEmail)->send(new \App\Mail\ReservationDetails($booking));
                }

            }

          } catch (\Exception $e) {
            //
          }
          return redirect(route('paymentMethodSuccess',['booking_id'=>$booking->id,'booking_number'=>$booking->number_of_booking]));
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
        $apartment = Apartment::where('id',$request->apartment_id)->with('building')->first();
        $this->bookingService->checkAvailability($apartment, $request->check_in, $request->check_out);
        $this->bookingService->validateGuestsCount($apartment, $request->number_of_adults, $request->number_of_children);
        $data = $this->bookingService->getDetermineBooking($apartment, $request->check_in, $request->check_out, null);
        $policy = Policy::where('type', 'booking')->first();
        $data['policy_title'] = $policy?->{'name_' . app()->getLocale()};
        $data['policy_description'] = $policy?->{'description_' . app()->getLocale()};
        $data['apartments'] = new ApartmentResource($apartment);

        
        $data['check_in_time'] =   $apartment?->building?->check_in_time?->format('h:i A');
        $data['check_out_time'] =  $apartment?->building?->check_out_time?->format('h:i A');

        $data['payment_details'] = $this->getPaymentDetails();
        return $this->successResponse($data);
    }

    //calculatePrice
    public function calculatePriceWithCoupon(Request $request)
    {

        $request->validate([
            'apartment_id' => 'required|exists:apartments,id',
            'coupon_code' => 'required|exists:coupons,code',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
        ]);
        $apartment = Apartment::findOrFail($request->apartment_id);
        $coupon = $this->bookingService->validateCoupon($apartment, $request->coupon_code);
        
        // استخدام نظام التسعير الجديد
        $data = $this->bookingService->calculatePricesWithDates($apartment, $request->check_in, $request->check_out, $coupon);
        return $this->successResponse($data);
    }

    //calculatePriceWithOutCoupon
    public function calculatePriceWithOutCoupon(Request $request)
    {

        $request->validate([
            'apartment_id' => 'required|exists:apartments,id',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
        ]);
        $apartment = Apartment::findOrFail($request->apartment_id);
        
        // استخدام نظام التسعير الجديد
        $data = $this->bookingService->calculatePricesWithDates($apartment, $request->check_in, $request->check_out);
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
                'name' => $gatewayData['title'],
                'icon' => url('icons/' . $gatewayData['value'] . '.png'),
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


    //services
    public function getServices()
    {
 
        $this->data['services'] =  Service::get()->map(function ($service) {
            return [
                'id' => $service->id,
                'name' => $service->{'name_' . app()->getLocale()},
                'price' => $service->price,
            ];
        });
        return $this->successResponse($this->data);
    }


    //bookingServices
    public function addServicesToBooking(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'services' => 'required|array',
            'services.*' => 'required|exists:services,id',
        ]);
        $customer_id = Auth::guard('api')->id();
        $response = $this->bookingService->addServicesToBooking($request, $customer_id);
        if (!$response['success']) {
            return $this->errorResponse($response['message']);
        }
        return $this->successResponse(__('api.services_added'));
    }

    public function cancelBooking(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
        ]);
        
        $customer = Auth::guard('api')->user();
        $booking = $this->booking->where([
            ['id', $request->booking_id],
            ['customer_id', $customer->id]
        ])->first();
        
        if (!$booking) {
            return $this->errorResponse(__('api.booking_not_found'));
        }
        
        // التحقق من إمكانية إلغاء الحجز
        if (!$booking->canBeCanceled()) {
            return $this->errorResponse(__('api.booking_cannot_be_canceled'));
        }
        
        // تحديث حالة الحجز إلى customer_canceled وrefund_status إلى pending
        $booking->status = 'customer_canceled';
        $booking->refund_status = 'pending';
        $booking->refund_amount = $booking->final_price;
        $booking->save();
        
        // إرسال إيميل للمشرف عند الإلغاء
        try {
            $building = \App\Models\Building::where('id', $booking?->apartment?->building_id)->first();
            if ($building) {
                $superVisor = \App\Models\User::where('id', $building->supervisor_id)->first();
                $superVisorEmail = $superVisor?->email;
                if ($superVisorEmail) {
                    Mail::to($superVisorEmail)->send(new \App\Mail\BookingCanceled($booking));
                }
            }
        } catch (\Exception $e) {
            // تجاهل أخطاء الإيميل لتجنب فشل عملية الإلغاء
            \Log::error('فشل في إرسال إيميل الإلغاء للمشرف: ' . $e->getMessage());
        }
        
        return $this->successResponse(__('api.booking_canceled_successfully'));
    }

}