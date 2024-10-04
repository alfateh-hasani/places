<?php

namespace App\Otp;

use App\Models\Customer;
use Illuminate\Container\Attributes\Log;
use SadiqSalau\LaravelOtp\Contracts\OtpInterface as Otp;
class CustomerRegistrationOtp implements Otp
{
    protected string $phone;


    public function __construct($phone)
    {
        $this->phone = $phone;
    }


    /**
     * Processes the Otp
     *
     * @return mixed
     */
    public function process()
    {
        \Illuminate\Support\Facades\Log::info('Processing OTP');
        return true;
    }


}

