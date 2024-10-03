<?php

namespace App\Otp;

use App\Models\Customer;
use SadiqSalau\LaravelOtp\Contracts\OtpInterface as Otp;
class CustomerRegistrationOtp implements Otp
{
    protected string $first_name;
    protected string $last_name;
    protected string $phone;

    public function __construct($first_name, $last_name, $phone)
    {
        $this->first_name = $first_name;
        $this->last_name = $last_name;
        $this->phone = $phone;
    }

    /**
     * Processes the Otp
     *
     * @return mixed
     */
    public function process()
    {
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

