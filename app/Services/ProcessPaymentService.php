<?php

namespace App\Services;

use App\Models\Customer;
use App\Services\Payments\TabbyPayment;
use App\Services\Payments\TapPayment;

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
                return (new TapPayment())->createCharge($this->customer, $data);
                //tap
                //tamara
                //cardit
                //for calass
            default:
        }

    }


}
