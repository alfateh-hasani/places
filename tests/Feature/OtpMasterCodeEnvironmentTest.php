<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The '2020' OTP master code is a dev/test convenience only. It must work in
 * local/testing (so tests don't depend on real OTP delivery) but be rejected
 * in production, where a real code is required.
 *
 * Follows this project's convention: no RefreshDatabase, cleans up its own rows.
 */
class OtpMasterCodeEnvironmentTest extends TestCase
{
    private array $customerIds = [];

    protected function tearDown(): void
    {
        if ($this->customerIds !== []) {
            DB::table('customers')->whereIn('id', $this->customerIds)->delete();
        }

        parent::tearDown();
    }

    private function makeCustomer(string $phone): Customer
    {
        $customer = Customer::create([
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'phone' => $phone,
        ]);

        $this->customerIds[] = $customer->id;

        return $customer;
    }

    public function test_api_master_code_is_rejected_in_production(): void
    {
        $this->app['env'] = 'production';
        $customer = $this->makeCustomer('+966500000201');

        $response = $this->withHeader('x-secret-key', config('app.api_secret_key'))
            ->postJson('/api/otp/verify', [
                'phone' => $customer->phone,
                'otp' => '2020',
            ]);

        $response->assertStatus(400);
        $response->assertJsonPath('success', false);
        $this->assertSame(0, $customer->tokens()->count());
    }

    public function test_api_master_code_is_accepted_in_testing(): void
    {
        // APP_ENV is 'testing' for the suite (phpunit.xml).
        $customer = $this->makeCustomer('+966500000202');

        $response = $this->withHeader('x-secret-key', config('app.api_secret_key'))
            ->postJson('/api/otp/verify', [
                'phone' => $customer->phone,
                'otp' => '2020',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
    }

    public function test_web_master_code_is_rejected_in_production(): void
    {
        $this->app['env'] = 'production';
        $customer = $this->makeCustomer('+966500000203');

        $response = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->postJson('/verify-otp', [
                'phone' => $customer->phone,
                'otp' => '2020',
            ]);

        $response->assertStatus(400);
        $response->assertJsonPath('status', 'error');
        $this->assertFalse(Auth::guard('customer')->check());
    }
}
