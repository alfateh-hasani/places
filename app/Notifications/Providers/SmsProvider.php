<?php
namespace App\Notifications\Providers;
use Illuminate\Support\Facades\Log;

class SmsProvider
{
    public static function sendSms($phoneNumber, $otp)
    {
        Log::info("Hello {$phoneNumber}! Your registration OTP is: {$otp}");
        return true;
    }
}
