<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Covers the customer-blocking decision: a blocked customer can no longer log in
 * (API or web), via the '2020' OTP master-code path so the test doesn't depend on
 * real OTP delivery/cache state. A non-blocked customer is used as a control to
 * prove the check doesn't false-positive.
 *
 * Follows this project's convention: no RefreshDatabase, runs against the configured
 * DB, cleans up its own rows in tearDown (see BookingRaceAndGuardsTest).
 */
class CustomerBlockingLoginTest extends TestCase
{
    private array $customerIds = [];

    protected function tearDown(): void
    {
        if ($this->customerIds !== []) {
            DB::table('customers')->whereIn('id', $this->customerIds)->delete();
        }

        parent::tearDown();
    }

    private function makeCustomer(string $phone, bool $blocked): Customer
    {
        $customer = Customer::create([
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'phone' => $phone,
            'blocked_at' => $blocked ? now() : null,
            'block_reason' => $blocked ? 'test block reason' : null,
        ]);

        $this->customerIds[] = $customer->id;

        return $customer;
    }

    public function test_api_login_rejects_blocked_customer_with_same_response_shape_as_existing_errors(): void
    {
        $customer = $this->makeCustomer('+966500000001', blocked: true);

        $response = $this->withHeader('x-secret-key', config('app.api_secret_key'))
            ->postJson('/api/otp/verify', [
                'phone' => $customer->phone,
                'otp' => '2020',
            ]);

        $response->assertStatus(400);
        $response->assertJsonStructure(['success', 'errors', 'message', 'data']);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('data', null);

        $this->assertSame(0, $customer->tokens()->count());
    }

    public function test_api_login_succeeds_for_non_blocked_customer(): void
    {
        $customer = $this->makeCustomer('+966500000002', blocked: false);

        $response = $this->withHeader('x-secret-key', config('app.api_secret_key'))
            ->postJson('/api/otp/verify', [
                'phone' => $customer->phone,
                'otp' => '2020',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.register_required', false);
        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_web_login_rejects_blocked_customer_with_same_response_shape_as_existing_errors(): void
    {
        $customer = $this->makeCustomer('+966500000003', blocked: true);

        $response = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->postJson('/verify-otp', [
            'phone' => $customer->phone,
            'otp' => '2020',
        ]);

        $response->assertStatus(400);
        $response->assertJsonStructure(['status', 'message']);
        $response->assertJsonPath('status', 'error');

        $this->assertFalse(Auth::guard('customer')->check());
    }

    public function test_web_login_succeeds_for_non_blocked_customer(): void
    {
        $customer = $this->makeCustomer('+966500000004', blocked: false);

        $response = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->postJson('/verify-otp', [
            'phone' => $customer->phone,
            'otp' => '2020',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $this->assertTrue(Auth::guard('customer')->check());
        $this->assertSame($customer->id, Auth::guard('customer')->id());
    }

    /**
     * Blocking only gated fresh logins at first — an already-logged-in customer kept full
     * access until their session expired. EnsureCustomerNotBlocked closes that gap: it
     * re-checks on every 'auth:customer' request and force-logs-out mid-session.
     */
    public function test_web_middleware_force_logs_out_customer_blocked_mid_session(): void
    {
        $customer = $this->makeCustomer('+966500000006', blocked: false);

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->postJson('/verify-otp', [
                'phone' => $customer->phone,
                'otp' => '2020',
            ])->assertStatus(200);

        $this->assertTrue(Auth::guard('customer')->check());

        // Mirrors what Admin\CustomerController::block() writes — blocked mid-session.
        $customer->update(['blocked_at' => now(), 'block_reason' => 'test']);

        // AuthManager caches resolved guards/users in-memory; a real second HTTP request is a
        // fresh process and re-resolves from DB naturally. Force that here too, or this test
        // would wrongly see the guard's first-request user object cached from the login call.
        Auth::forgetGuards();

        $response = $this->get('/customer/get-booking');

        $response->assertRedirect(route('home'));
        $this->assertFalse(Auth::guard('customer')->check());
    }

    /**
     * Blocking only revoked new logins at first — an already-issued Sanctum token kept
     * working for the mobile app indefinitely. Admin\CustomerController::block() now also
     * deletes all of the customer's tokens, so the very next API call is unauthenticated.
     */
    public function test_blocking_revokes_existing_api_tokens(): void
    {
        $customer = $this->makeCustomer('+966500000007', blocked: false);
        $token = $customer->createToken('Places_APP')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('x-secret-key', config('app.api_secret_key'))
            ->getJson('/api/customer/my-profile')
            ->assertStatus(200);

        // Mirrors Admin\CustomerController::block(): set blocked_at + revoke tokens.
        $customer->update(['blocked_at' => now(), 'block_reason' => 'test']);
        $customer->tokens()->delete();

        $this->assertSame(0, $customer->tokens()->count());

        // See comment in the web middleware test above — same guard-caching artifact applies here.
        Auth::forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('x-secret-key', config('app.api_secret_key'))
            ->getJson('/api/customer/my-profile')
            ->assertStatus(401);
    }
}
