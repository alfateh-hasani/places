<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Otp\CustomerRegistrationOtp;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use SadiqSalau\LaravelOtp\Facades\Otp;

class AuthController extends Controller
{
    public function requestOtp(Request $request){
        try {
            $validatedData = $request->validate([
                'phone'=> 'required|digits:9'
            ]);
        } catch (ValidationException $e) {
            return $this->errorResponse([], $e->getMessage());
        }
        $customer = Customer::where('phone', $request->phone)->first();

        if ($customer) {
            try {
                $otp = Otp::identifier('otp_'.$request->phone)->send(
                    new CustomerRegistrationOtp(
                        first_name: $customer->first_name,
                        last_name: $customer->last_name,
                        phone: $request->phone
                    ),
                    Notification::route('sms', $request->phone)
                );

                if($otp['status'] == Otp::OTP_SENT){
                    $data = [];
                    return $this->successResponse($data, trans($otp['status']), 200);
                }

                return $this->errorResponse([], trans('api.error_happened'));

            } catch (\Throwable $th) {
                return $this->errorResponse([], $th->getMessage(), 500);
            }
        } else {
            try {
                $customer = Customer::create([
                    'phone' => $request->phone,
                    'first_name' => 'Customer',
                    'last_name' => 'Customer',
                 ]);
                $otp = Otp::identifier('otp_'.$request->phone)->send(
                    new CustomerRegistrationOtp(  first_name: $customer->first_name,
                        last_name: $customer->last_name,
                        phone: $request->phone),
                    false
                );

                if($otp['status'] == Otp::OTP_SENT){
                    $data = [];
                    return $this->successResponse($data, trans($otp['status']), 200);
                }

                return $this->errorResponse([], trans('api.error_happened'));

            } catch (\Throwable $th) {
                return $this->errorResponse([], $th->getMessage(), 500);
            }
        }
    }

    public function verifyOtp(Request $request){
        try {
            $validatedData = $request->validate([
                'phone'=> 'required|digits:9',
                'otp'=>'required|digits:4'
            ]);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), trans('api.validation_exception'));
        }

        try {
            $otp = Otp::identifier('otp_'.$request->phone)->attempt($request->otp);
            if($otp['status'] == Otp::OTP_MISMATCHED){
                return $this->errorResponse([], trans($otp['status']));
            }
            if($otp['status'] == Otp::OTP_EMPTY){
                return $this->errorResponse([], trans($otp['status']));
            }

            if($otp['status'] == Otp::OTP_PROCESSED){
                $data = $otp['result'];
                return $this->successResponse($data, trans($otp['status']), 200);
            }

        } catch (\Throwable $th) {
            return  $this->errorResponse([], $th->getMessage(), 500);
        }
    }


}
