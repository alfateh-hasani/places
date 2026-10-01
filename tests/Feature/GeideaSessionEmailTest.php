<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Transaction;
use App\Services\PaymentMethods\GeideaPayment;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Geidea rejects the whole checkout session with "Invalid email address"
 * (responseCode 110) for a malformed email but accepts an empty one. The session
 * payload must therefore send '' for any address the gateway would refuse
 * (safety-net for pre-existing bad records) and pass valid ones through.
 */
class GeideaSessionEmailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('payments.gateways.geidea.public_key', 'public-key-test');
        Config::set('payments.gateways.geidea.api_password', 'secret-test');
        Config::set('payments.gateways.geidea.api_base', 'https://api.merchant.geidea.net');
        Config::set('payments.gateways.geidea.hpp_base', 'https://hpp.merchant.geidea.net/checkout');
        Config::set('payments.gateways.geidea.webhook_url', 'https://places.test/api/webhooks/geidea');

        // A failure response (no session id) keeps process() off the DB-touching
        // success branch — we only care about the payload that was sent.
        Http::fake([
            '*/payment-intent/api/v2/direct/session' => Http::response([
                'responseCode' => '110',
                'responseMessage' => 'HPP Integration error',
            ]),
        ]);
    }

    #[DataProvider('gatewayInvalidEmails')]
    public function test_gateway_invalid_email_is_sent_as_empty_string(string $email): void
    {
        app(GeideaPayment::class)->process($this->makeTransaction($email));

        Http::assertSent(function (Request $request): bool {
            $customer = $request->data()['customer'] ?? [];

            return ($customer['email'] ?? null) === ''
                && $customer['phoneNumber'] === '+967774814450';
        });
    }

    public function test_valid_customer_email_is_included_in_session_payload(): void
    {
        app(GeideaPayment::class)->process($this->makeTransaction('guest@example.com'));

        Http::assertSent(function (Request $request): bool {
            return ($request->data()['customer']['email'] ?? null) === 'guest@example.com';
        });
    }

    /**
     * @return array<string, array{string}>
     */
    public static function gatewayInvalidEmails(): array
    {
        return [
            'no TLD' => ['test@d'],
            'single-char TLD' => ['test@k.c'],
        ];
    }

    private function makeTransaction(?string $email): Transaction
    {
        $customer = new Customer;
        $customer->email = $email;
        $customer->phone = '+967774814450';
        $customer->first_name = 'Mohammed';
        $customer->last_name = 'Al';

        $transaction = new Transaction;
        $transaction->id = 2137;
        $transaction->amount = 450.00;
        $transaction->currency = 'SAR';
        $transaction->platform = 'web';
        $transaction->payment_gateway = 'geidea';
        $transaction->transaction_reference = 'ref-2137';
        $transaction->setRelation('customer', $customer);

        return $transaction;
    }
}
