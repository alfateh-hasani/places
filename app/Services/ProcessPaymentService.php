<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Transaction;
use App\Services\PaymentMethods\TabbyPayment;
use App\Services\PaymentMethods\TapPayment;
use Illuminate\Support\Facades\Auth;

class ProcessPaymentService
{
 //tap , tamara , cardit , for calass

   //protected $customer;

    // public function __construct(Customer $customer)
    // {
    //     $this->customer = $customer;
    // }

    protected function getMethod( $method)
    {

        switch ($method) {
            case 'tabby':
                return (new TabbyPayment());
            case 'tap':
                return (new TapPayment());
                //tap
                //tamara
                //cardit
                //for calass
            default:
        }

    }

    public function processPayment($data, $method)
    {
        $paymentMethod = $this->getMethod($method);

        return $paymentMethod->process($data);


    }

    //add transactions
    public function addTransaction($data,$price,$customer)
    {
        $apartment_data =[
            'apartment_id' => $data['apartment_id'],
            'check_in' => $data['check_in'],
            'check_out' => $data['check_out'],
            'coupon_code' => $data['coupon_code'],
            'adults_count' => $data['adults_count'],
            'children_count' => $data['children_count'],
            'payment_method_code' => $data['payment_method_code'],
            'total_price' => $price['total_price'],
            'discount' => $price['discount'],
            'final_price' => $price['final_price'],
            'booking_source' => \Request()->header('BookingSource') ?? 'web',
        ];
        return Transaction::create([
            'customer_id' => $customer->id,
            'apartment_id' => $data['apartment_id'],
            'booking_data' =>  json_encode($apartment_data),
            'transaction_reference' => time().'_'.uniqid(),
            'amount' => (float) $price['total_price'],
            'currency' => 'SAR',
            'status' => 'pending',
            'type' => 'deposit',
            'payment_gateway' => $data['payment_method_code'],
            'payment_gateway_response' => null,
        ]);
    }


    public function handleCallBack($method , $data){
        $paymentMethod = $this->getMethod($method);

        $response = $paymentMethod->handlePayment($data);

        return $response;



    }


}
