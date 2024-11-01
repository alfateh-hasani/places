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
    
    private function validatePhoneStartsWith5($phone)
    {
        return preg_match('/^5\d{8}$/', $phone); // Validates phone starts with '5'
    }

    public function requestOtp(Request $request)
    {
        try {
            $request->merge([
                'phone' =>  convertArabicNumbers($request->phone),
            ]);

            $validatedData = $request->validate([
                'phone' => ['required', 'phone:SA', function ($attribute, $value, $fail) {
                    if (!$this->validatePhoneStartsWith5($value)) {
                        $fail(__('auth.phone_required_or_expired'));
                    }
                }],
            ]);

            $customerExists = Customer::where('phone', $request->phone)->exists();

            $otp = Otp::identifier('otp_' . $request->phone)
                ->send(new CustomerRegistrationOtp($request->phone),
                    Notification::route('sms', $request->phone)
                );

            if ($otp['status'] === Otp::OTP_SENT) {
                return response()->json([
                    'status' => 'success',
                    'message' => __('auth.otp_sent'),
                    'phone' => $request->phone,
                    'has_account' => $customerExists,
                ], 200);
            }

            return response()->json(['status' => 'error', 'message' => __('auth.something_went_wrong')], 422);
        } catch (ValidationException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function verifyOtp(Request $request)
    {
        $request->merge([
            'phone' =>  convertArabicNumbers($request->phone),
            'otp'   =>  convertArabicNumbers($request->otp),
        ]);

        $validatedData = $request->validate([
            'phone' => 'required|phone:SA',
            'otp'   => 'required|digits:4',
        ]);

        $otpStatus = Otp::identifier('otp_' . $request->phone)->attempt($request->otp);

        if ($otpStatus['status'] !== Otp::OTP_PROCESSED) {
            return response()->json([
                'status' => 'error',
                'message' => __('auth.otp_invalid'),
            ], 400);
        }

        $customer = Customer::where('phone', $request->phone)->first();

        if ($customer) {
            Auth::guard('customer')->login($customer); // Use the customer guard for login

            return response()->json([
                'status' => 'success',
                'message' => 'Logged in successfully.',
                'redirect' => route('home'),
            ]);
        }

        $token = Str::random(60);
        Cache::put('verified_phone_' . $token, $request->phone, now()->addMinutes(10));

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
