<?php

namespace App\Http\Controllers\Front\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Otp\CustomerRegistrationOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use SadiqSalau\LaravelOtp\Facades\Otp;
use Illuminate\Validation\ValidationException;


class LoginController extends Controller
{
    public function requestOtp(Request $request)
    {
        try {
            $request->merge([
                'phone' => $this->convertArabicNumbers($request->phone),
            ]);

            $validatedData = $request->validate([
                'phone' => 'required|regex:/^5[0-9]{8}$/',
            ]);

            $customer = Customer::where('phone', $request->phone)->exists();

            $otp = Otp::identifier('otp_' . $request->phone)
                ->send(new CustomerRegistrationOtp($request->phone),
                    Notification::route('sms', $request->phone)
                );

            Log::info('OTP Requested', ['otp' => $otp]);

            if ($otp['status'] === Otp::OTP_SENT) {
                return response()->json([
                    'status' => 'success',
                    'message' => __('OTP sent successfully!'),
                    'has_account' => (bool) $customer
                ], 200);
            }

            return response()->json(['status' => 'error', 'message' => __('Something went wrong!')], 422);

        } catch (ValidationException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }



    private function convertArabicNumbers($input) {
        $arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $englishNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        return str_replace($arabicNumbers, $englishNumbers, $input);
    }
}
