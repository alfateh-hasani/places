<?php

namespace App\Http\Controllers\Api;

use App\Enums\CustomerSource;
use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Otp\CustomerRegistrationOtp;
use App\Services\Otp\OtpRequestThrottle;
use App\Services\Otp\OtpVerificationGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use SadiqSalau\LaravelOtp\Facades\Otp;

class AuthController extends Controller
{
    public function __construct(
        private readonly OtpRequestThrottle $throttle,
        private readonly OtpVerificationGuard $verificationGuard,
    ) {}

    public function requestOtp(Request $request)
    {
        $otpLog = Log::channel('otp');

        try {
            $request->merge([
                'phone' => convertArabicNumbers($request->phone),
            ]);
            $validatedData = $request->validate([
                'phone' => 'required|phone',
            ]);
        } catch (ValidationException $e) {
            $otpLog->warning('[API] OTP request validation failed', [
                'phone' => $request->phone,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse([], $e->getMessage());
        }
        $customer = Customer::where('phone', $request->phone)->exists();

        $decision = $this->throttle->attempt($request->phone);
        if ($decision->denied()) {
            $otpLog->warning('[API] OTP request throttled', [
                'phone' => $request->phone,
                'reason' => $decision->reason->name,
                'retry_after' => $decision->retryAfter,
            ]);

            return $this->errorResponse([], trans('api.'.$decision->reason->messageKey(), ['seconds' => $decision->retryAfterForHumans(), 'hours' => $decision->retryAfterInHours()]));
        }

        try {
            $otpLog->info('[API] Sending OTP', ['phone' => $request->phone, 'has_account' => $customer]);

            $otp = Otp::identifier('otp_'.$request->phone)->send(
                new CustomerRegistrationOtp(
                    phone: $request->phone),
                Notification::route('sms', $request->phone)
            );

            if ($otp['status'] == Otp::OTP_SENT) {
                $this->throttle->recordSent($request->phone);
                $this->verificationGuard->reset($request->phone);
                $otpLog->info('[API] OTP sent successfully', ['phone' => $request->phone]);
                $data = [
                    'has_account' => (bool) $customer,
                ];

                return $this->successResponse($data, trans($otp['status']), 200);
            }

            $otpLog->error('[API] OTP send failed', ['phone' => $request->phone, 'status' => $otp['status']]);

            return $this->errorResponse([], trans('api.error_happened'));
        } catch (\Throwable $th) {
            $otpLog->error('[API] OTP send exception', ['phone' => $request->phone, 'error' => $th->getMessage()]);

            return $this->errorResponse([], $th->getMessage(), 500);
        }
    }

    public function verifyOtp(Request $request)
    {
        $otpLog = Log::channel('otp');

        try {
            $request->merge([
                'phone' => convertArabicNumbers($request->phone),
                'otp' => convertArabicNumbers($request->otp),
            ]);

            $rules = [
                'phone' => 'required|phone',
                'otp' => 'required|digits:4',
                'fcm_token' => 'nullable',
            ];

            $validatedData = $request->validate($rules);

            $otpLog->info('[API] Verifying OTP', ['phone' => $request->phone]);

            if ($this->verificationGuard->isLockedOut($request->phone)) {
                $otpLog->warning('[API] OTP verification locked - too many wrong codes', ['phone' => $request->phone]);

                return $this->errorResponse([], trans('api.otp_too_many_attempts'), 429);
            }

            $otpStatus = Otp::identifier('otp_'.$request->phone)->attempt($request->otp);

            $masterCodeAllowed = app()->environment(['local', 'testing']) && $request->otp === '2020';

            if ($otpStatus['status'] !== Otp::OTP_PROCESSED && ! $masterCodeAllowed) {
                $this->verificationGuard->recordFailure($request->phone);
                $otpLog->warning('[API] OTP verification failed', [
                    'phone' => $request->phone,
                    'status' => $otpStatus['status'],
                ]);

                return $this->errorResponse([], trans($otpStatus['status']));
            } elseif ($masterCodeAllowed) {
                $otpLog->info('[API] OTP bypassed with master code', ['phone' => $request->phone]);
            }

            $this->verificationGuard->reset($request->phone);

            $customer = Customer::where('phone', $request->phone)->first();

            if ($customer && $customer->isBlocked()) {
                $otpLog->warning('[API] Login blocked - customer is blocked', ['phone' => $request->phone, 'customer_id' => $customer->id]);

                return $this->errorResponse([], trans('api.account_blocked'));
            }

            if ($customer) {
                $customer->fcm_token = $request->fcm_token;
                $customer->save();

                $data['customer'] = new CustomerResource($customer);
                $data['token'] = $customer->createToken('Places_APP')->plainTextToken;
                $data['register_required'] = false;

                $otpLog->info('[API] Login successful', ['phone' => $request->phone, 'customer_id' => $customer->id]);

                return $this->successResponse($data, trans($otpStatus['status']), 200);
            }

            $token = Str::random(60);
            Cache::put('verified_api_phone_'.$token, $request->phone, now()->addMinutes(10));
            $data['token'] = $token;
            $data['register_required'] = true;

            $otpLog->info('[API] OTP verified - registration required', ['phone' => $request->phone]);

            return $this->successResponse($data, trans($otpStatus['status']), 200);

        } catch (ValidationException $e) {
            $otpLog->warning('[API] OTP verify validation failed', ['phone' => $request->phone ?? null, 'error' => $e->getMessage()]);

            return $this->errorResponse($e->errors(), trans('api.validation_exception'));
        } catch (\Throwable $th) {
            $otpLog->error('[API] OTP verify exception', ['phone' => $request->phone ?? null, 'error' => $th->getMessage()]);

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
            'email' => Customer::emailValidationRules(),
            'fcm_token' => 'nullable',
        ]);

        $phone = Cache::pull('verified_api_phone_'.$request->token);

        if (! $phone) {
            return $this->errorResponse([], __('auth.phone_required_or_expired'), 422);
        }

        try {
            $customer = Customer::create([
                'first_name' => trim($validatedData['first_name']),
                'last_name' => trim($validatedData['last_name']),
                'email' => strtolower(trim($validatedData['email'])),
                'phone' => $phone,
                'fcm_token' => $request->fcm_token,
                'source' => CustomerSource::Local,
            ]);

            $data['customer'] = new CustomerResource($customer);
            $data['token'] = $customer->createToken('Places_APP')->plainTextToken;

            return $this->successResponse($data, 'success', 200);
        } catch (\Throwable $th) {
            return $this->errorResponse([], $th->getMessage(), 500);
        }

    }
}
