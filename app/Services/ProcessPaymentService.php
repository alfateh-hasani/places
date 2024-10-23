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

    protected $customer;

    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    public function processPayment($data, $method)
    {

        switch ($method) {
            case 'tabby':
                return (new TabbyPayment())->process($this->customer, $data);
            case 'tap':
                return (new TapPayment())->process($data);
                //tap
                //tamara
                //cardit
                //for calass
            default:
        }

    }

    //add transactions
    public function addTransaction($data,$total_price)
    {
        $apartment_data =[
            'apartment_id' => $data['apartment_id'],
            'check_in' => $data['check_in'],
            'check_out' => $data['check_out'],
        ];
        $user = Auth::guard('api')->user();
        return Transaction::create([
            'customer_id' => $user->id,
            'apartment_id' => $data['apartment_id'],
            'apartment_data' =>  json_encode($apartment_data),
            'transaction_reference' => time().'_'.uniqid(),
            'amount' => (float) $total_price,
            'currency' => 'SAR',
            'status' => 'pending',
            'type' => 'deposit',
            'payment_gateway' => $data['payment_method_code'],
            'payment_gateway_response' => null,
        ]);
    }


}
