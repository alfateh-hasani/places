<?php

namespace App\Services\PaymentMethods;
use App\Models\Transaction;
use App\Services\PaymentMethods\PaymentMethodInterface;


use Illuminate\Support\Facades\Http;

class TapPayment implements PaymentMethodInterface
{
    protected $secretKey;
    protected $publicKey;
    protected $isTestMode;

    public function __construct()
    {
        $this->isTestMode = config('payments.gateways.tap.test_mode');

        $this->publicKey= $this->isTestMode
            ?  config('payments.gateways.tap.sandbox_public_key')
            :   config('payments.gateways.tap.public_key');

        $this->secretKey = $this->isTestMode
            ?  config('payments.gateways.tap.sandbox_secret_key')
            :  config('payments.gateways.tap.secret_key');
    }


    public function createCharge($data)
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

    public function process($transaction)
    {
        $data = [
            'amount' => $transaction->amount,
            'currency' => $transaction->currency,
            'threeDsecure' => true,
            'save_card' => false,
            'description' => "Booking Apartment - {$transaction->transaction_reference}",
            'statement_descriptor' => 'Sample',
            'metadata' => [

            ],
            'reference' => [
                'transaction' => $transaction->transaction_reference,
                'order' => $transaction->id,
            ],
            'customer' => [
                'first_name' => $transaction->customer?->first_name,
                'last_name'  => $transaction->customer?->last_name,
                'email'      =>  $transaction->customer?->email,
                'phone' => [
                    'country_code' => '966',
                    'number' => $transaction->customer?->phone,
                ]
            ],
            'source' => ['id' => 'src_all'],
            'redirect' => ['url' => route('getCallbackPayments',$transaction->id)],
        ];

        return $this->createCharge($data);

    }
}
