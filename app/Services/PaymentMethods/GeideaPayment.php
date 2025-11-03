<?php

namespace App\Services\PaymentMethods;

use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeideaPayment implements PaymentMethodInterface
{
    /* ======== مفاتيح الاعتماديات ======== */
    private string $publicKey;
    private string $apiPassword;
    private string $apiBase;         // https://api.ksamerchant.geidea.net مثلاً
    private string $hppBase;         // https://www.ksamerchant.geidea.net/hpp/checkout

    public function __construct()
    {
        $this->publicKey   = config('payments.gateways.geidea.public_key');
        $this->apiPassword = config('payments.gateways.geidea.api_password');
        $this->apiBase     = rtrim(config('payments.gateways.geidea.api_base'), '/');
        $this->hppBase     = rtrim(config('payments.gateways.geidea.hpp_base'), '/');
    }

    /* -----------------------------------------------------------------
     |  متطلَّبات الـ Interface
     |-----------------------------------------------------------------*/
    /** Tap كانت تستخدم هذا الاسم؛ هنا نعيد توجيهه لإنشاء Session */
    public function createCharge($payload)
    {
        $url      = $this->apiBase . '/payment-intent/api/v2/direct/session';
        $response = Http::withBasicAuth($this->publicKey, $this->apiPassword)
            ->acceptJson()
            ->post($url, $payload);


        if (
            $response->successful()
        ) {
            return $response->json();
        }

        return false;
    }

    /** جلب حالة طلب/مدفوعات من Geidea */
    private function retrievePayment($orderId)
    {
        $url      = $this->apiBase . "/payment-intent/api/v2/direct/orders/{$orderId}";
        $response = Http::withBasicAuth($this->publicKey, $this->apiPassword)
            ->acceptJson()
            ->get($url);

        Log::channel('payments')
            ->info('Geidea retrievePayment', [
                'orderId'  => $orderId,
                'response' => $response->json(),
                'status'   => $response->status(),
            ]);

        return $response->successful() ? $response->json() : false;
    }

    /* -----------------------------------------------------------------
     |  تحضير البيانات ثم استدعاء createCharge
     |-----------------------------------------------------------------*/
    public function process($transaction)
    {
        $callbackUrl = route(
            $transaction->platform === 'api'
                ? 'paymentMethodCallBack'
                : 'web-booking.paymentMethodCallBack',
            [$transaction->payment_gateway, $transaction->id]
        );

        $timestamp = now()->format('Y/m/d H:i:s');

        $payload = [
            'amount'            => $this->fmt($transaction->amount),
            'currency'          => $transaction->currency,
            'merchantReferenceId' => $transaction->transaction_reference,
            'timestamp'         => $timestamp,
            'signature'         => $this->signature(
                $transaction->amount,
                $transaction->currency,
                $transaction->transaction_reference,
                $timestamp
            ),
            'language'          => 'en',
            'callbackUrl'       => $callbackUrl,
            'returnUrl'         => $callbackUrl,
            'customer'          => [
                'email'            => $transaction->customer?->email,
                'phoneNumber'      => $transaction->customer?->phone,
                'phonecountrycode' => '+966',
                'firstName'        => $transaction->customer?->first_name,
                'lastName'         => $transaction->customer?->last_name,
            ],
            'order' => [
                'items' => [[
                    'merchantItemId' => "BOOK-{$transaction->id}",
                    'name'           => 'Apartment Booking',
                    'description'    => "Reservation {$transaction->transaction_reference}",
                    'categories'     => 'real-estate',
                    'count'          => 1,
                    'price'          => $this->fmt($transaction->amount),
                    'sku'            => "APT-{$transaction->id}",
                ]],
            ],
        ];

        $session = $this->createCharge($payload);



        if (isset($session['session']['id'])) {
            $transaction->refresh();
            $array =  [
                'session_id'   => $session['session']['id'],
                'transaction' => [
                    'url' => "https://www.ksamerchant.geidea.net/hpp/checkout/?" . $session['session']['id']
                ],
                'booking_id' => $transaction->booking_id,
            ];

            \Log::info($array);
            return $array;
        }

        return false;
    }

    /* -----------------------------------------------------------------
     |  callback → update Transaction
     |-----------------------------------------------------------------*/
    public function handlePayment($data)
    {
        $transaction = Transaction::find($data['transaction_id'] ?? null);

        if (!$transaction) {
            return ['status' => false, 'message' => 'Transaction not found'];
        }

        $isSuccess = ($data['responseCode'] ?? null) === '000';

        $transaction->status = $isSuccess ? 'completed' : 'failed';
        $transaction->payment_gateway_response = json_encode($data);
        
        // حفظ order_id من response في Transaction
        if (isset($data['orderId'])) {
            $transaction->order_id = $data['orderId'];
        }
        
        $transaction->save();

        return [
            'status'         => $isSuccess,
            'transaction_id' => $transaction->id,
            'order_id'       => $data['orderId']   ?? null,
            'reference'      => $data['reference'] ?? null,
        ];
    }

    /* -----------------------------------------------------------------
     |  Refund - استرداد المبلغ
     |-----------------------------------------------------------------*/
    public function refund($orderId, $amount)
    {
        $url = $this->apiBase . "/payment-intent/api/v2/direct/refund";
        
        $payload = [
            'orderId' => $orderId,
            'amount' => $this->fmt($amount),
        ];

        $response = Http::withBasicAuth($this->publicKey, $this->apiPassword)
            ->acceptJson()
            ->post($url, $payload);

        Log::channel('payments')->info('Geidea Refund', [
            'orderId' => $orderId,
            'amount' => $amount,
            'response' => $response->json(),
            'status' => $response->status(),
        ]);

        if ($response->successful()) {
            $data = $response->json();
            // التحقق من نجاح الاسترداد
            if (isset($data['responseCode']) && $data['responseCode'] === '000') {
                return [
                    'success' => true,
                    'data' => $data,
                    'message' => 'تم استرداد المبلغ بنجاح'
                ];
            }
        }

        return [
            'success' => false,
            'message' => 'فشل في استرداد المبلغ',
            'error' => $response->json()
        ];
    }

    /* -----------------------------------------------------------------
     |  أدوات مساعدة
     |-----------------------------------------------------------------*/
    private function signature(float $amount, string $currency, string $ref, string $ts): string
    {
        $plain = "{$this->publicKey}{$this->fmt($amount)}{$currency}{$ref}{$ts}";
        return base64_encode(hash_hmac('sha256', $plain, $this->apiPassword, true));
    }

    private function fmt(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}
