<?php

namespace Tests\Feature;

use App\Enums\CustomerSource;
use App\Models\Customer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Covers the new Customer::source column: registerUser() (API + web) must tag new
 * accounts as CustomerSource::Local, and — critically — the mobile API's response
 * shape must stay byte-for-byte identical (no mobile app code can be changed, so
 * `source` must never leak into CustomerResource).
 *
 * Follows this project's convention: no RefreshDatabase, runs against the configured
 * DB, cleans up its own rows in tearDown (see BookingRaceAndGuardsTest).
 */
class CustomerSourceTest extends TestCase
{
    private array $customerIds = [];

    protected function tearDown(): void
    {
        if ($this->customerIds !== []) {
            DB::table('customers')->whereIn('id', $this->customerIds)->delete();
        }

        parent::tearDown();
    }

    public function test_api_register_tags_local_source_without_changing_response_shape(): void
    {
        $phone = '+966500000020';
        $token = 'test-api-register-'.uniqid();
        Cache::put('verified_api_phone_'.$token, $phone, now()->addMinutes(10));

        $response = $this->withHeader('x-secret-key', config('app.api_secret_key'))
            ->postJson('/api/customer/register', [
                'token' => $token,
                'first_name' => 'Test',
                'last_name' => 'Source',
                'email' => 'test-source-api-'.uniqid().'@example.com',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // The exact response contract the mobile app already relies on — must never
        // change. Any accidental leak of a new field (like `source`) fails this.
        $this->assertEqualsCanonicalizing(
            ['id', 'first_name', 'last_name', 'email', 'phone', 'emergency_phone', 'job_title', 'image'],
            array_keys($response->json('data.customer'))
        );
        $this->assertEqualsCanonicalizing(['customer', 'token'], array_keys($response->json('data')));

        $customer = Customer::where('phone', $phone)->first();
        $this->customerIds[] = $customer->id;
        $this->assertSame(CustomerSource::Local, $customer->source);
    }

    public function test_web_register_tags_local_source(): void
    {
        $phone = '+966500000021';
        $token = 'test-web-register-'.uniqid();
        Cache::put('verified_phone_'.$token, $phone, now()->addMinutes(10));

        $response = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->postJson('/register', [
                'token' => $token,
                'first_name' => 'Test',
                'last_name' => 'Source',
                'email' => 'test-source-web-'.uniqid().'@example.com',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');

        $customer = Customer::where('phone', $phone)->first();
        $this->customerIds[] = $customer->id;
        $this->assertSame(CustomerSource::Local, $customer->source);
    }
}
