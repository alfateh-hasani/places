<?php

namespace App\Services;

use App\Models\Apartment;
use App\Models\Coupon;
use App\Models\Booking;
use App\Models\Building;
use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\Transaction;
use Carbon\Carbon;
use Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    protected $paymentService;

    public function __construct(ProcessPaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function validateCoupon($apartment, $couponCode)
    {
        if ($couponCode != null) {
            $coupon = Coupon::where('code', $couponCode)->first();
    
            if (!$coupon) {
                throw ValidationException::withMessages(['coupon_code' => __('api.coupon_invalid')]);
            }
            $hasApartment = $coupon->apartments && $coupon->apartments->contains($apartment->id);
            $hasBuilding = $coupon->building && $coupon->building->contains($apartment->building_id);
    
            if (!$hasApartment && !$hasBuilding) {
                throw ValidationException::withMessages(['coupon_code' => __('api.coupon_invalid_apartment')]);
            }
    
            return $coupon;
        }
        
        return null;
    }
    

    //checkAvailability
    public function checkAvailability($apartment, $checkIn, $checkOut): void
    {
        $existingBooking = Booking::where('apartment_id', $apartment->id)
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->where(function ($query) use ($checkIn, $checkOut) {
                    $query->where('check_in', '<=', $checkIn)
                          ->where('check_out', '>', $checkIn);
                })->orWhere(function ($query) use ($checkIn, $checkOut) {
                    $query->where('check_in', '<', $checkOut)
                          ->where('check_out', '>=', $checkOut);
                })->orWhere(function ($query) use ($checkIn, $checkOut) {
                    $query->where('check_in', '>=', $checkIn)
                          ->where('check_out', '<=', $checkOut);
                });
            })->exists();
    
        if ($existingBooking) {
            throw ValidationException::withMessages(['apartment_id' => __('api.already_booked')]);
        }
    }
    

    public function calculatePrices($apartmentPrice, $numberOfNights, $coupon = null): array
    {
        $totalPrice = $apartmentPrice * $numberOfNights;
        $discount = 0;
        if ($coupon) {
            if ($coupon->type === 'percentage') {
                $discount = ($coupon->discount / 100) * $totalPrice;
            } elseif ($coupon->type === 'fixed') {
                $discount = $coupon->discount;
            }
        }
        $finalPrice = $totalPrice - $discount;
        if ($finalPrice < 0) {
            $finalPrice = 0;
        }
        return [
            'total_price' =>   number_format( $totalPrice, 2, '.', ''),
            'discount' =>      number_format( $discount, 2, '.', ''),
            'final_price' =>   number_format($finalPrice, 2, '.', ''),
        ];
    }


    public function calculateNumberOfNights($checkIn, $checkOut)
    {
        return Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut));
    }


    public function createPayment($validatedData, $customer, $apartment , $plaform = 'web')
    {
    
                $numberOfNights = $this->calculateNumberOfNights($validatedData['check_in'], $validatedData['check_out']);
                $coupon = null;
                 if (!empty($validatedData['coupon_code'])) {
                    $coupon = $this->validateCoupon($apartment, $validatedData['coupon_code']);
                }
                $priceWithTax = calculateTotalWithTax($apartment->price);
                $prices = $this->calculatePrices($priceWithTax, $numberOfNights, $coupon);
                $transaction = $this->paymentService->addTransaction($validatedData,$prices,$customer , $plaform);
                $this->createBooking($transaction->id, null);
        return  $this->paymentService->processPayment($transaction, $validatedData['payment_method_code']);
    }

    //createBooking

    public function createBooking($transaction_id,$payment_id)
    {
        DB::beginTransaction();
         $transaction = Transaction::where('id',$transaction_id)->first();
         if (!$transaction){
                throw ValidationException::withMessages(['transaction_id' =>  __('api.transaction_not_exists')]);
         }
         $data = json_decode($transaction->booking_data);
        //  

        $apartment  = Apartment::where('id',$transaction->apartment_id)->first();
        $building = Building::where('id',$apartment->building_id)->first();

         $booking =  Booking::create([
            'apartment_id' => $transaction->apartment_id,
            'customer_id' => $transaction->customer_id,
            'customer_full_name' => $transaction->customer->first_name . ' ' . $transaction->customer->last_name,
            'customer_email' => $transaction->customer->email,
            'number_of_nights' => $this->calculateNumberOfNights($data->check_in, $data->check_out),
            'adults_count' => $data->adults_count,
            'children_count' => $data->children_count,
            'total_price' => $data->total_price,
            'discount' => $data->discount,
            'final_price' =>  calculateTotalWithTax($data->final_price),
            'booking_source' => $data->booking_source,
            'coupon_id' => $data->coupon_id ?? null  ,
            'coupon_code' => $data->coupon_code ?? null,
            'status' => 'pending',
            'payment_id' => $payment_id??null,
            'payment_status' => 'pending',
            'check_in' => $data->check_in,
            'check_out' => $data->check_out,

            'check_in_time' => $building->check_in_time,
            'check_out_time' => $building->check_out_time,

            'transaction_id' => $transaction->id,
            'tax' => Config::get('settings.tax', 15),
         ]);
         $transaction->update(['booking_id' => $booking->id]);
         DB::commit();
         return $booking;
    }

    public function getDetermineBooking($apartment,$check_in,$check_out)
    {
        $numberOfNights = $this->calculateNumberOfNights($check_in, $check_out);
        $prices = $this->calculatePrices($apartment->price, $numberOfNights);
        return [
            'number_of_nights' => $numberOfNights,
            'one_nights' => floatval($apartment->price),
            'total_price' => $prices['total_price'],
            'discount' => $prices['discount'],
            'final_price' => $prices['final_price'],
        ];

    }

    public function validateGuestsCount( $apartment,   $number_of_adults,   $number_of_children): void
    {
        $validations = [
            'number_of_adults' => ['value' => $number_of_adults, 'max' => $apartment->adults_count, 'message' => __('api.max_adults')],
            'number_of_children' => ['value' => $number_of_children, 'max' => $apartment->children_count, 'message' => __('api.max_children')],
        ];
        foreach ($validations as $key => $validation) {
            if ($validation['value'] > $validation['max']) {
                throw ValidationException::withMessages([$key => $validation['message']]);
            }
        }
    }


    

    public function deleteUnpaidBookings()
    {
        $tenMinutesAgo = now()->subMinutes(10);
        $unpaidBookings = Booking::where('status', 'pending')
                                ->where('created_at', '<=', $tenMinutesAgo)
                                ->get();

        foreach ($unpaidBookings as $booking) {
            $booking->delete();
        }
    }


    //bookingServices
    public function addServicesToBooking($request, $customer_id)
    {
        $date = Carbon::now()->toDateString();
        $booking = Booking::where([
            ['id', $request->booking_id],
            ['customer_id', $customer_id],
            ['check_out', '>',$date ]
        ])->first();
        if (!$booking) {
            return [
                'success' => false,
                'message' => __('api.booking_not_found')
            ];
        }
        try {
            $servicesData = Service::whereIn('id', $request->services)->get()->map(function ($service) use ($booking) {
                return [
                    'booking_id' => $booking->id,
                    'service_id' => $service->id,
                    'price' => $service->price,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();
          
           $servicesDat = ServiceBooking::insert($servicesData);
 
            return [
                'success' => true,
                'message' => __('api.services_added')
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    
    }
    
 
    



}
