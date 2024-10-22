<?php

namespace App\Services\PaymentMethods;
use App\Services\PaymentMethods\PaymentMethodInterface;
class TabbyPayment implements PaymentMethodInterface
{
    public function process($customer, $amount)
    {

        return "تمت معالجة الدفع بقيمة ";
    }

    public function retrievePayment($customer , $data){
        
    }
    
}
