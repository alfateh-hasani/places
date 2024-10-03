<?php

namespace App\Otp;

use App\Models\Customer;
use Illuminate\Container\Attributes\Log;
use SadiqSalau\LaravelOtp\Contracts\OtpInterface as Otp;
class CustomerRegistrationOtp implements Otp
{
    protected string $first_name;
    protected string $last_name;
    protected string $phone;
    protected string $email;

    public function __construct($first_name = null, $last_name = null, $email = null, $phone)
    {
        $this->first_name = $first_name;
        $this->last_name = $last_name;
        $this->email = $email;
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
        $customer = Customer::unguarded(function () {
            return Customer::create([
                'first_name'                  => $this->first_name,
                'last_name'                 => $this->last_name,
                'email'                 => $this->email,
                'phone'                 => $this->phone,


            ]);
        });
        $token = $customer->createToken('Places_APP');
        return [
            'customer' => [
                'first_name' =>$customer->first_name ,
                'last_name'=>$customer->last_name ,
                'email'=>$customer->email ,
                'phone'=>$customer->phone ,
            ],
            'token' => $token->plainTextToken
        ];
    }

}

