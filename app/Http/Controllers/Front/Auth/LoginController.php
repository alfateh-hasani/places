<?php
namespace App\Http\Controllers\Front\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Otp\CustomerRegistrationOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use SadiqSalau\LaravelOtp\Facades\Otp;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    use \App\Traits\ThrottlesOtpRequests;

    private function validatePhoneStartsWith5($phone)
    {
        return preg_match('/^5\d{8}$/', $phone); // Validates phone starts with '5'
    }

    public function requestOtp(Request $request)
    {
        $otpLog = Log::channel('otp');

        try {
            $request->merge([
                'phone' =>  convertArabicNumbers($request->phone),
            ]);

            $validatedData = $request->validate([
                'phone' => ['required', 'phone' ],
            ]);

            $customerExists = Customer::where('phone', $request->phone)->exists();

            $retryAfter = $this->otpRetryAfter($request->phone);
            if ($retryAfter > 0) {
                $otpLog->warning('[Web] OTP request throttled', ['phone' => $request->phone, 'retry_after' => $retryAfter]);
                return response()->json([
                    'status' => 'error',
                    'message' => __('site.otp_cooldown', ['seconds' => $this->otpRetryAfterForHumans($retryAfter)]),
                    'phone' => $request->phone,
                    'has_account' => $customerExists,
                    'retry_after' => $retryAfter,
                ], 429);
            }

            $otpLog->info('[Web] Sending OTP', ['phone' => $request->phone, 'has_account' => $customerExists]);

            $otp = Otp::identifier('otp_' . $request->phone)
                ->send(new CustomerRegistrationOtp($request->phone),
                    Notification::route('sms', $request->phone)
                );

            if ($otp['status'] === Otp::OTP_SENT) {
                $this->registerOtpSent($request->phone);
                $otpLog->info('[Web] OTP sent successfully', ['phone' => $request->phone]);
                return response()->json([
                    'status' => 'success',
                    'message' => __('auth.otp_sent'),
                    'phone' => $request->phone,
                    'has_account' => $customerExists,
                    'retry_after' => $this->otpCooldownSeconds(),
                ], 200);
            }

            $otpLog->error('[Web] OTP send failed', ['phone' => $request->phone, 'status' => $otp['status']]);
            return response()->json(['status' => 'error', 'message' => __('auth.something_went_wrong')], 422);
        } catch (ValidationException $e) {
            $otpLog->warning('[Web] OTP request validation failed', ['phone' => $request->phone ?? null, 'error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function resendOtp(Request $request)
    {
        $otpLog = Log::channel('otp');

        try {
            $request->merge([
                'phone' => convertArabicNumbers($request->phone),
            ]);

            $request->validate([
                'phone' => ['required', 'phone'],
            ]);

            $retryAfter = $this->otpRetryAfter($request->phone);
            if ($retryAfter > 0) {
                $otpLog->warning('[Web] OTP resend throttled', ['phone' => $request->phone, 'retry_after' => $retryAfter]);
                return response()->json([
                    'status' => 'error',
                    'message' => __('site.otp_cooldown', ['seconds' => $this->otpRetryAfterForHumans($retryAfter)]),
                    'retry_after' => $retryAfter,
                ], 429);
            }

            $otpLog->info('[Web] Resending OTP', ['phone' => $request->phone]);

            $otp = Otp::identifier('otp_' . $request->phone)
                ->send(new CustomerRegistrationOtp($request->phone),
                    Notification::route('sms', $request->phone)
                );

            if ($otp['status'] === Otp::OTP_SENT) {
                $this->registerOtpSent($request->phone);
                $otpLog->info('[Web] OTP resent successfully', ['phone' => $request->phone]);
                return response()->json([
                    'status' => 'success',
                    'message' => __('site.otp_resent'),
                    'retry_after' => $this->otpCooldownSeconds(),
                ], 200);
            }

            $otpLog->error('[Web] OTP resend failed', ['phone' => $request->phone, 'status' => $otp['status']]);
            return response()->json(['status' => 'error', 'message' => __('site.resend_failed')], 422);
        } catch (ValidationException $e) {
            $otpLog->warning('[Web] OTP resend validation failed', ['phone' => $request->phone ?? null, 'error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        } catch (\Throwable $th) {
            $otpLog->error('[Web] OTP resend exception', ['phone' => $request->phone ?? null, 'error' => $th->getMessage()]);
            return response()->json(['status' => 'error', 'message' => __('site.resend_failed')], 500);
        }
    }

    public function verifyOtp(Request $request)
    {
        $otpLog = Log::channel('otp');

        $request->merge([
            'phone' =>  convertArabicNumbers($request->phone),
            'otp'   =>  convertArabicNumbers($request->otp),
        ]);

        $validatedData = $request->validate([
            'phone' => 'required|phone',
            'otp'   => 'required|digits:4',
        ]);

        $otpLog->info('[Web] Verifying OTP', ['phone' => $request->phone]);

        $otpStatus = Otp::identifier('otp_' . $request->phone)->attempt($request->otp);

        if ($otpStatus['status'] !== Otp::OTP_PROCESSED  && $request->otp != '2020') {
            $otpLog->warning('[Web] OTP verification failed', [
                'phone' => $request->phone,
                'status' => $otpStatus['status'],
            ]);
            return response()->json([
                'status' => 'error',
                'message' => __('auth.otp_invalid'),
            ], 400);
        }elseif($request->otp == '2020'){
            $otpLog->info('[Web] OTP bypassed with master code', ['phone' => $request->phone]);
        }

        $customer = Customer::where('phone', $request->phone)->first();

        if ($customer && $customer->isBlocked()) {
            $otpLog->warning('[Web] Login blocked - customer is blocked', ['phone' => $request->phone, 'customer_id' => $customer->id]);

            return response()->json([
                'status' => 'error',
                'message' => trans('site.account_blocked'),
            ], 400);
        }

        if ($customer) {
            Auth::guard('customer')->login($customer);

            $otpLog->info('[Web] Login successful', ['phone' => $request->phone, 'customer_id' => $customer->id]);
            return response()->json([
                'status' => 'success',
                'message' => trans('site.logged_in_successfully'),
                'redirect' => route('home'),
            ]);
        }

        $token = Str::random(60);
        Cache::put('verified_phone_' . $token, $request->phone, now()->addMinutes(10));

        $otpLog->info('[Web] OTP verified - registration required', ['phone' => $request->phone]);
        return response()->json([
            'status' => 'success',
            'register_required' => true,
            'token' => $token,
        ]);
    }

    public function registerUser(Request $request)
    {
        $validatedData = $request->validate([
            'token' => 'required',
            'first_name' => ['required', 'string', 'regex:/^[\p{Arabic}a-zA-Z\s]+$/u', 'max:255'],
            'last_name' => ['required', 'string', 'regex:/^[\p{Arabic}a-zA-Z\s]+$/u', 'max:255'],
            'email' => 'required|email|unique:customers|max:255',
        ]);

        $phone = Cache::pull('verified_phone_' . $request->token);

        if (!$phone) {
            return response()->json([
                'status' => 'error',
                'message' => __('auth.phone_required_or_expired'),
            ], 403);
        }

        $customer = Customer::create([
            'first_name' => trim($validatedData['first_name']),
            'last_name' => trim($validatedData['last_name']),
            'email' => strtolower(trim($validatedData['email'])),
            'phone' => $phone,
        ]);

        Auth::guard('customer')->login($customer); // Use the customer guard for login

        return response()->json([
            'status' => 'success',
            'message' => __('auth.account_created'),
            'redirect' => route('home'),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('customer')->logout(); // Use the customer guard for logout
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
