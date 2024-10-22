<?php

namespace App\Services\Payments;



use Illuminate\Support\Facades\Http;

class TapPayment
{
    protected $secretKey;
    protected $publicKey;
    protected $isTestMode;

    public function __construct()
    {
        $this->isTestMode = config('payments.gateways.tap.test_mode');

        $this->secretKey = $this->isTestMode
            ?  config('payments.gateways.tap.sandbox_public_key')
            :   config('payments.gateways.tap.public_key');

        $this->publicKey = $this->isTestMode
            ?  config('payments.gateways.tap.sandbox_secret_key')
            :  config('payments.gateways.tap.secret_key');
    }

    /**
     * Create a payment charge request to Tap Payment.
     *
     * @param array $data
     * @return array|bool
     */
    public function createCharge($customer_id, $data)
    {
        $requestUrl = 'https://api.tap.company/v2/charges';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json',
        ])->post($requestUrl, $data);


        if ($response->successful()) {
            return $response->json();
        }

        return false;
    }

    public function retrievePayment($tapId)
    {
        $requestUrl = "https://api.tap.company/v2/charges/$tapId";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
        ])->get($requestUrl);

        if ($response->successful()) {
            return $response->json();
        }

        return false;
    }

}
