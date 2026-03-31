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
        $this->publicKey = config('payments.gateways.geidea.public_key');
        $this->apiPassword = config('payments.gateways.geidea.api_password');
        $this->apiBase = rtrim(config('payments.gateways.geidea.api_base'), '/');
        $this->hppBase = rtrim(config('payments.gateways.geidea.hpp_base'), '/');
    }

    /* -----------------------------------------------------------------
     |  متطلَّبات الـ Interface
     |-----------------------------------------------------------------*/
    /** Tap كانت تستخدم هذا الاسم؛ هنا نعيد توجيهه لإنشاء Session */
    public function createCharge($payload)
    {
        $url = $this->apiBase.'/payment-intent/api/v2/direct/session';
        $response = Http::
        // withoutVerifying()
        // ->
        withBasicAuth($this->publicKey, $this->apiPassword)
            ->acceptJson()
            ->post($url, $payload);

        Log::channel('geidea')->info('Geidea createSession', [
            'url' => $url,
            'payload' => $payload,
            'status' => $response->status(),
            'response' => $response->json(),
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        return false;
    }

    /** جلب حالة طلب/مدفوعات من Geidea */
    public function verifyPayment($orderId)
    {
        $url = $this->apiBase."/pgw/api/v1/direct/order/{$orderId}";
        $response = Http::withBasicAuth($this->publicKey, $this->apiPassword)
            ->acceptJson()
            ->get($url);

        Log::channel('geidea')
            ->info('Geidea retrievePayment', [
                'orderId' => $orderId,
                'response' => $response->json(),
                'status' => $response->status(),
            ]);

        return $response->successful() ? $response->json() : false;
    }

    /* -----------------------------------------------------------------
     |  تحضير البيانات ثم استدعاء createCharge
     |-----------------------------------------------------------------*/
    public function process($transaction)
    {
        // returnUrl: يُعيد توجيه المتصفح فقط لعرض النتيجة للعميل
        $returnUrl = route(
            $transaction->platform === 'api'
                ? 'paymentMethodCallBack'
                : 'web-booking.paymentMethodCallBack',
            [$transaction->payment_gateway, $transaction->id]
        );

        // callbackUrl: server-to-server webhook من Geidea - يُأكد الحجز
        $webhookUrl = config('payments.gateways.geidea.webhook_url') ?: route('geidea.webhook');

        $timestamp = now()->format('Y/m/d H:i:s');

        $payload = [
            'amount' => $this->fmt($transaction->amount),
            'currency' => $transaction->currency,
            'merchantReferenceId' => $transaction->transaction_reference,
            'timestamp' => $timestamp,
            'signature' => $this->signature(
                $transaction->amount,
                $transaction->currency,
                $transaction->transaction_reference,
                $timestamp
            ),
            'language' => 'en',
            'callbackUrl' => $webhookUrl,
            'returnUrl' => $returnUrl,
            'customer' => [
                'email' => $transaction->customer?->email,
                'phoneNumber' => $transaction->customer?->phone,
                'phonecountrycode' => '+966',
                'firstName' => $transaction->customer?->first_name,
                'lastName' => $transaction->customer?->last_name,
            ],
            'order' => [
                'items' => [[
                    'merchantItemId' => "BOOK-{$transaction->id}",
                    'name' => 'Apartment Booking',
                    'description' => "Reservation {$transaction->transaction_reference}",
                    'categories' => 'real-estate',
                    'count' => 1,
                    'price' => $this->fmt($transaction->amount),
                    'sku' => "APT-{$transaction->id}",
                ]],
            ],
        ];

        $session = $this->createCharge($payload);

        if (isset($session['session']['id'])) {
            $transaction->refresh();
            $array = [
                'session_id' => $session['session']['id'],
                'transaction' => [
                    'url' => $this->hppBase.'/?'.$session['session']['id'],
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

        if (! $transaction) {
            return ['status' => false, 'message' => 'Transaction not found'];
        }

        $callbackSuccess = ($data['responseCode'] ?? null) === '000';
        $orderId = $data['orderId'] ?? null;
        $isSuccess = false;

        // التحقق من حالة الدفع الفعلية من Geidea API
        if ($callbackSuccess && $orderId) {
            $orderData = $this->verifyPayment($orderId);

            if ($orderData && ($orderData['order']['detailedStatus'] ?? null) === 'Paid') {
                $isSuccess = true;
            } else {
                Log::channel('geidea')->warning('Geidea payment verification failed', [
                    'transaction_id' => $transaction->id,
                    'order_id' => $orderId,
                    'callback_response_code' => $data['responseCode'] ?? null,
                    'api_detailed_status' => $orderData['order']['detailedStatus'] ?? null,
                ]);
            }
        }

        $transaction->status = $isSuccess ? 'completed' : 'failed';
        $transaction->payment_gateway_response = json_encode($data);

        if ($orderId) {
            $transaction->order_id = $orderId;
        }

        $transaction->save();

        return [
            'status' => $isSuccess,
            'transaction_id' => $transaction->id,
            'order_id' => $orderId,
            'reference' => $data['reference'] ?? null,
        ];
    }

    /* -----------------------------------------------------------------
     |  Refund - استرداد المبلغ
     |-----------------------------------------------------------------*/
    public function refund($orderId, $amount)
    {
        $url = $this->apiBase.'/pgw/api/v1/direct/refund';

        $payload = [
            'orderId' => $orderId,
            'amount' => $this->fmt($amount),
        ];

        $response = Http::withBasicAuth($this->publicKey, $this->apiPassword)
            ->acceptJson()
            ->post($url, $payload);

        Log::channel('geidea')->info('Geidea Refund', [
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
                    'message' => 'تم استرداد المبلغ بنجاح',
                ];
            }
        }

        return [
            'success' => false,
            'message' => 'فشل في استرداد المبلغ',
            'error' => $response->json(),
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
