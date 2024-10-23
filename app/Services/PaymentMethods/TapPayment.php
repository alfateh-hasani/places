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

    private function retrievePayment($tapId)
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
            'redirect' => ['url' => route('paymentMethodCallBack',['code'=>'tap', 'transaction_id'=>$transaction->id])],
        ];

        return $this->createCharge($data);

    }


    public function handlePayment($data){
         $transaction = Transaction::where('id',$data['transaction_id'])->first();
         if(!$transaction){
            return ['status'=>false,'transaction_id'=>null, 'message'=>'transaction Id not found'];
         }

         if($transaction->status != 'pending'){
            return ['status'=>false,'transaction_id'=>$transaction->id , 'message'=>'transaction status not pending, the status is '.$transaction->status];
         }

         $tad_id = $data['tap_id'];
        
         $paymentDetails = $this->retrievePayment($tad_id);
         if($paymentDetails['status'] == 'CAPTURED' and $paymentDetails['amount'] == $transaction->amount and $paymentDetails['currency'] == $transaction->currency){
            $transaction->payment_gateway_response = json_encode($paymentDetails);
            $transaction->status = 'completed';
            $transaction->save();
            return [
                    'status'=>true , 
                    'transaction_id'    =>$data['transaction_id'],
                    'payment_id'=> $paymentDetails['id'],
 
                ];
         }

         $transaction->payment_gateway_response = json_encode($paymentDetails);
         $transaction->status = 'faild';
         $transaction->save();

         return ['status'=>false,'transaction_id'=>null, 'message'=>'transaction Id not found'];
 

    }
}
