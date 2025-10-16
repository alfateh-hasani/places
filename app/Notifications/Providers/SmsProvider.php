<?php

namespace App\Notifications\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SmsProvider
{
    public static function sendSms($phoneNumber, $otp)
    {
        Log::info("Sending SMS to {$phoneNumber} with OTP: {$otp}");

        $token = '20cb5a2c8e08f04c5f67791bac035ef5';
        $sender = 'Ad.Dyafa';
        $payload = [
            'recipients' => [$phoneNumber],
            'body'       => $otp,
            'sender'     => $sender,
        ];

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->asJson()
                ->post('https://api.taqnyat.sa/v1/messages', $payload);

            Log::info('Taqnyat response', $response->json());

            return $response->successful();  // true إذا 2xx/201
        } catch (\Throwable $e) {
            // يُفضَّل رفع استثناء مخصّص أو إعادة false حسب هندسة مشروعك
            Log::error('Taqnyat sendSms failed', [
                'error'   => $e->getMessage(),
                'line'    => $e->getLine(),
            ]);

            return false;
        }
    }
}
