<?php

namespace App\Services\PaymentMethods;

interface PaymentMethodInterface {

    public function process($customer , $data);
    public function createCharge($customer , $data);

}
