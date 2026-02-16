<?php

namespace App\Notifications\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SmsProvider
{
    public static function sendSms($phoneNumber, $otp)
    {
        $otpLog = Log::channel('otp');
        $otpLog->info('[SMS] Sending via Taqnyat', ['phone' => $phoneNumber, 'body_length' => strlen($otp)]);

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

            $responseData = $response->json();
            $otpLog->info('[SMS] Taqnyat response', [
                'phone' => $phoneNumber,
                'http_status' => $response->status(),
                'response' => $responseData,
                'successful' => $response->successful(),
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            $otpLog->error('[SMS] Taqnyat exception', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
                'line'  => $e->getLine(),
            ]);

            return false;
        }
    }
}
