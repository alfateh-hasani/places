<?php
namespace App\Http\Controllers\Front;
use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Apartment;
use App\Models\Policy;
use App\Models\Booking;
use App\Models\Coupon;
use App\Services\BookingService;
use Auth;
use Illuminate\Http\Request;
use App\Services\ProcessPaymentService;
use Carbon\Carbon;
use Config;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller{
    protected $booking;
    protected $bookingService;

    public function __construct(BookingService $bookingService, Booking $booking)
    {
        $this->booking = $booking;
        $this->bookingService = $bookingService;
    }

     

     

    public function addBooking(Request $request)
    {
 
            $validator = Validator::make($request->all(), [
                'apartment_id' => 'required|exists:apartments,id',
                'check_in' => 'required|date',
                'check_out' => 'required|date',
                'coupon_code' => 'nullable|exists:coupons,code',
                'notes' => 'nullable|string',
                'booking_source' => 'nullable|in:web,android,ios',
                'payment_method_code' => 'required',
            ]);
        
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }
        try {
            $validatedData = $validator->validated();
            
            $customer = Auth::user();
            $apartment = Apartment::findOrFail($validatedData['apartment_id']);
            $validatedData['adults_count'] = $apartment->adults_count;
            $validatedData['children_count'] = $apartment->children_count;
            $validatedData['status'] = 'pending';  
            $this->bookingService->checkAvailability($apartment, $validatedData['check_in'], $validatedData['check_out']);            
            $paymentResponse = $this->bookingService->createPayment($validatedData, $customer, $apartment); 
            dd($paymentResponse);
            if (is_array($paymentResponse) && isset($paymentResponse['transaction']['url'])) {
                return redirect($paymentResponse['transaction']['url']);
            } else {
                return  redirect()->back()->with('error', $paymentResponse);
            }
        } catch (\Exception $exception) {
            return  redirect()->back()->with('error', $exception->getMessage());
        }
        

    }




    public function paymentMethodCallBack(Request $request , $paymentMethodCode , $transaction_id){
        if(!in_array($paymentMethodCode,array_keys(config('payments.gateways')))){
            return  redirect()->back()->with('error', __('api.payment_method_not_supported'));
        }
        $processPaymentService = new ProcessPaymentService();
        $data = $request->all();
        $data['transaction_id'] = $transaction_id;
        $handlePayment = $processPaymentService->handleCallBack($paymentMethodCode , $data);
        if($handlePayment['status']==true){
        //  $booking =  $this->bookingService->createBooking($transaction_id,$handlePayment['payment_id']);
         $booking =  $this->booking->where('transaction_id',$transaction_id)->first();
          return redirect(route('customer.booking.details',[$booking->number_of_booking]));
        }
        dd($data);
        return redirect(route('web-booking.determine'));
    }

 
    //paymentMethodSuccess
    public function paymentMethodSuccess($booking_id)
    {
        $data ['booking'] = $this->booking->find($booking_id);
         return  view('booking.details',$data);
    }

 


 


    public function couponsVerify(Request $request)
    {
        $request->validate([
            'code' => 'required|string|exists:coupons,code',
        ]);
        $apartment = Apartment::findOrFail($request->apartment_id);
        $coupon = $this->bookingService->validateCoupon($apartment, $request->code);
        $data = $this->bookingService->calculatePrices($apartment->price, $request->total_nights, $coupon);
        return  response()->json($data);
    }

    public function determineBookingStatus(Request $request, $apartment_id)
    {
        try {
            $apartment = Apartment::findOrFail($apartment_id);
                $this->bookingService->checkAvailability($apartment, $request->checkin, $request->checkout);
            $this->bookingService->validateGuestsCount($apartment, $request->number_of_adults, $request->number_of_children);
            $data = $this->bookingService->getDetermineBooking($apartment, $request->checkin, $request->checkout);
            $payment_methods = Policy::where('type', 'booking')->first();
            $data['policy_title'] = $payment_methods?->{'name_' . app()->getLocale()};
            $data['policy_description'] = $payment_methods?->{'description_' . app()->getLocale()};
            $data['apartment'] = $apartment;
            $data['payment_details'] = $this->getPaymentDetails();
 
            return view('booking.determine-booking', $data);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            return back()->withErrors(['error' => __('api.general_error')])->withInput();
        }
    }
    

    private function getPaymentDetails()
    {
        $paymentMethods = [];
        foreach (config('payments.gateways') as $gateway => $gatewayData) {
            $paymentMethods[] = [
                'name' => $gateway,
                'icon' => asset('assets/payments/' . $gatewayData['icon']),
                'value' => $gatewayData['value'],
                'explanation' => $gatewayData['explanation'],
            ];
        }
        return $paymentMethods;
    }

    public function cancelBooking(Request $request)
    {
        $booking_id = $request->booking_id;
        $cancellationWindow =Config::get('settings.cancel_booking');
        $booking = $this->booking->find($booking_id);
        if (!$booking) {
            return redirect()->back()->with('error', __('api.booking_not_found'));
        }
        $checkInDate = Carbon::parse($booking->check_in);
        $currentDate = Carbon::now();
            $daysBeforeCheckIn = $currentDate->diffInDays($checkInDate, false); 
        if ($daysBeforeCheckIn < $cancellationWindow) {
            return redirect()->back()->with('error', __('booking.cancellation_window_expired'));
        }
        $booking->status = 'canceled';
        $booking->save();
        return redirect()->back()->with('success', __('booking.success'));
    }
    

}
