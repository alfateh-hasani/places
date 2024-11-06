<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\NotificationSeen;
use App\Otp\CustomerRegistrationOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use SadiqSalau\LaravelOtp\Facades\Otp;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class AuthController extends Controller
{
    public function requestOtp(Request $request)
    {
        try {
            $request->merge([
                'phone' => convertArabicNumbers($request->phone),
            ]);
            $validatedData = $request->validate([
                'phone'=> 'required|phone:SA',
            ]);
        } catch (ValidationException $e) {
            return $this->errorResponse([], $e->getMessage());
        }
        $customer = Customer::where('phone', $request->phone)->exists();
        
        try {
            $otp = Otp::identifier('otp_'.$request->phone)->send(
                new CustomerRegistrationOtp(
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
            $request->merge([
                'phone' => convertArabicNumbers($request->phone),
                'otp' => convertArabicNumbers($request->otp),
            ]);

            $rules = [
                'phone' => 'required|phone:SA',
                'otp'   => 'required|digits:4',
                'fcm_token'=>'nullable'
            ];

            $validatedData = $request->validate($rules);

            $otpStatus = Otp::identifier('otp_' . $request->phone)->attempt($request->otp);

            if ($otpStatus['status'] !== Otp::OTP_PROCESSED) {
                return $this->errorResponse([], trans($otpStatus['status']));
            }


            $customer = Customer::where('phone', $request->phone)->first();

            if ($customer) {
                $customer->fcm_token = $request->fcm_token;
                $customer->save();
                
                $data['customer'] = new CustomerResource($customer);
                $data['token'] =$customer->createToken('Places_APP')->plainTextToken;
                $data['register_required'] = false;

                return $this->successResponse($data, trans($otpStatus['status']), 200);
            }

            $token = Str::random(60);
            Cache::put('verified_api_phone_' . $token, $request->phone, now()->addMinutes(10));
            $data['token'] = $token; 
            $data['register_required'] = true;
            return $this->successResponse($data, trans($otpStatus['status']), 200);

           
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), trans('api.validation_exception'));
        }catch (\Throwable $th) {
            return $this->errorResponse([], $th->getMessage(), 500);
        }
        // try {
        //     $otp = Otp::identifier('otp_'.$request->phone)->attempt($request->otp);
        //     Log::info('OTP Verified', ['otp' => $otp]);
        //     if ($otp['status'] == Otp::OTP_MISMATCHED || $otp['status'] == Otp::OTP_EMPTY) {
        //         return $this->errorResponse([], trans($otp['status']));
        //     }

        //     if ($otp['status'] == Otp::OTP_PROCESSED) {
        //         if (!$customer) {
        //             $customer = Customer::create([
        //                 'first_name' => $request->first_name,
        //                 'last_name' => $request->last_name,
        //                 'email' => $request->email,
        //                 'phone' => $request->phone,
        //                 'fcm_token' => $request->fcm_token,
        //             ]);
        //         }
        //         $data['customer'] = new CustomerResource($customer);
        //         $data['token'] =$customer->createToken('Places_APP')->plainTextToken;
        //         return $this->successResponse($data, trans($otp['status']), 200);
        //     }

        //     return $this->errorResponse([], trans('api.error_happened'));
        // } catch (\Throwable $th) {
        //     return $this->errorResponse([], $th->getMessage(), 500);
        // }
    }


    public function registerUser(Request $request)
    {
        $validatedData = $request->validate([
            'token' => 'required',
            'first_name' => ['required', 'string', 'regex:/^[\p{Arabic}a-zA-Z\s]+$/u', 'max:255'],
            'last_name' => ['required', 'string', 'regex:/^[\p{Arabic}a-zA-Z\s]+$/u', 'max:255'],
            'email' => 'required|email|unique:customers|max:255',
            'fcm_token'=>'nullable'
        ]);

        $phone = Cache::pull('verified_api_phone_' . $request->token);

        if (!$phone) {
            return $this->errorResponse([], __('auth.phone_required_or_expired'), 422);
        }

        try {
            $customer = Customer::create([
                'first_name' => trim($validatedData['first_name']),
                'last_name' => trim($validatedData['last_name']),
                'email' => strtolower(trim($validatedData['email'])),
                'phone' => $phone,
                'fcm_token' => $request->fcm_token,
            ]);

            $data['customer'] = new CustomerResource($customer);
            $data['token'] =$customer->createToken('Places_APP')->plainTextToken;
            return $this->successResponse($data,  'success', 200); 
        } catch (\Throwable $th) {
                  return $this->errorResponse([], $th->getMessage(), 500);
        }

    }
 



}
