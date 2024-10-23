<?php

namespace App\Services\PaymentMethods;

interface PaymentMethodInterface {

    public function process($data);
    public function createCharge($data);

}
