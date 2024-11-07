<?php
namespace App\Http\Controllers\Front;
use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Apartment;
use App\Models\Policy;
use App\Models\Booking;
use App\Services\BookingService;
use Auth;
use Illuminate\Http\Request;
use App\Services\ProcessPaymentService;


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
                return redirect($paymentResponse['transaction']['url']);
            } else {
                return $this->errorResponse($paymentResponse);
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
          return redirect(route('paymentMethodSuccess',['booking_id'=>$booking->id,'booking_number'=>$booking->number_of_booking]));
        }
        return redirect(route('paymentMethodFailed'));
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


 

}
