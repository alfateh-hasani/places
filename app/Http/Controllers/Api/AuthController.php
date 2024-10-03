<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Otp\CustomerRegistrationOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use SadiqSalau\LaravelOtp\Facades\Otp;

class AuthController extends Controller
{
    public function requestOtp(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'phone'=> 'required|digits:9'
            ]);
        } catch (ValidationException $e) {
            return $this->errorResponse([], $e->getMessage());
        }
        $customer = Customer::where('phone', $request->phone)->first();
        try {
            $otp = Otp::identifier('otp_'.$request->phone)->send(
                new CustomerRegistrationOtp(
                    first_name:'',
                    last_name: '',
                    email: '',
                    phone: $request->phone),
                Notification::route('sms', $request->phone)
            );
            Log::info('OTP Requested', ['otp' => $otp]);
            if($otp['status'] == Otp::OTP_SENT){
                $data = [
                    'has_account' => (bool)$customer
                ];
                return $this->successResponse($data, trans($otp['status']), 200);
            }
            return $this->errorResponse([], trans('api.error_happened'));
        } catch (\Throwable $th) {
            return $this->errorResponse([], $th->getMessage(), 500);
        }
    }

    public function verifyOtp(Request $request)
    {
        try {
            $customer = Customer::where('phone', $request->phone)->first();
            $rules = [
                'phone' => 'required|digits:9',
                'otp'   => 'required|digits:6',
            ];
            if (!$customer) {
                $rules['first_name'] = 'required|string|max:255';
                $rules['last_name'] = 'required|string|max:255';
                $rules['email'] = 'required|email|max:255|unique:customers';
                $rules['phone'] = 'required|digits:9|unique:customers';
            }

            $validatedData = $request->validate($rules);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), trans('api.validation_exception'));
        }
        try {
            $otp = Otp::identifier('otp_'.$request->phone)->attempt($request->otp);
            Log::info('OTP Verified', ['otp' => $otp]);
            if ($otp['status'] == Otp::OTP_MISMATCHED || $otp['status'] == Otp::OTP_EMPTY) {
                return $this->errorResponse([], trans($otp['status']));
            }
            
            if ($otp['status'] == Otp::OTP_PROCESSED) {
                if (!$customer) {
                    $customer = Customer::create([
                        'first_name' => $request->first_name,
                        'last_name' => $request->last_name,
                        'email' => $request->email,
                        'phone' => $request->phone,
                    ]);
                }
                $data['customer'] = new CustomerResource($customer);
                return $this->successResponse($data, trans($otp['status']), 200);
            }

            return $this->errorResponse([], trans('api.error_happened'));
        } catch (\Throwable $th) {
            return $this->errorResponse([], $th->getMessage(), 500);
        }
    }

}
