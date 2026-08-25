<?php

namespace Tests\Feature;

use App\Services\PaymentMethods\GeideaPayment;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Pins the Geidea REFUND wire contract (no DB needed):
 *  - v2 endpoint, refundAmount (not amount), timestamp, refund-specific signature, no currency.
 */
class GeideaRefundSignatureTest extends TestCase
{
    public function test_refund_uses_v2_signed_contract(): void
    {
        Config::set('payments.gateways.geidea.public_key', 'public-key-test');
        Config::set('payments.gateways.geidea.api_password', 'secret-test');
        Config::set('payments.gateways.geidea.api_base', 'https://api.merchant.geidea.net');
        Config::set('payments.gateways.geidea.hpp_base', 'https://hpp.merchant.geidea.net/checkout');

        $this->travelTo(now()->setDate(2026, 4, 26)->setTime(15, 0, 0));

        try {
            Http::fake([
                '*/pgw/api/v2/direct/refund' => Http::response([
                    'responseCode' => '000',
                    'responseMessage' => 'Success',
                ]),
            ]);

            $orderId = '47bbd481-fdc0-44e2-5dda-08db15bcae4b';
            $timestamp = '2026/04/26 15:00:00';
            $signatureAmount = '125.50';
            $expectedSignature = base64_encode(hash_hmac(
                'sha256',
                $timestamp.'public-key-test'.$signatureAmount.$orderId,
                'secret-test',
                true,
            ));

            $result = app(GeideaPayment::class)->refund($orderId, 125.50);

            $this->assertTrue($result['success']);

            Http::assertSent(function (Request $request) use ($expectedSignature, $orderId, $timestamp): bool {
                $data = $request->data();

                return str_contains($request->url(), '/pgw/api/v2/direct/refund')
                    && $data['orderId'] === $orderId
                    && $data['refundAmount'] === '125.50'
                    && $data['timestamp'] === $timestamp
                    && $data['signature'] === $expectedSignature
                    && ! array_key_exists('currency', $data)
                    && ! array_key_exists('amount', $data);
            });

            Http::assertSentCount(1);
        } finally {
            $this->travelBack();
        }
    }
}
