<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Covers the server-side OTP request cooldown: a phone may only trigger one OTP
 * within config('otp.request_cooldown') seconds.
 *
 * The mobile (API) path must keep returning the existing ApiResponse envelope so
 * the app — whose source we don't control — doesn't break; the web path returns
 * 429 + retry_after so the front-end timer can sync to the server.
 *
 * SMS delivery is faked so the test doesn't depend on a real provider.
 */
class OtpRequestThrottleTest extends TestCase
{
    private array $phones = [];

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        config()->set('otp.request_cooldown', 60);
    }

    protected function tearDown(): void
    {
        foreach ($this->phones as $phone) {
            RateLimiter::clear('otp-request:' . $phone);
        }

        parent::tearDown();
    }

    private function trackedPhone(string $phone): string
    {
        $this->phones[] = $phone;

        return $phone;
    }

    public function test_api_first_request_sends_and_second_is_throttled_with_same_envelope(): void
    {
        $phone = $this->trackedPhone('+966500000101');

        $first = $this->withHeader('x-secret-key', config('app.api_secret_key'))
            ->postJson('/api/otp/request', ['phone' => $phone]);

        $first->assertStatus(200);
        $first->assertJsonPath('success', true);

        $second = $this->withHeader('x-secret-key', config('app.api_secret_key'))
            ->postJson('/api/otp/request', ['phone' => $phone]);

        $second->assertStatus(400);
        $second->assertJsonStructure(['success', 'errors', 'message', 'data']);
        $second->assertJsonPath('success', false);
        $second->assertJsonPath('data', null);
        $this->assertNotEmpty($second->json('message'));
    }

    public function test_web_first_request_sends_and_second_is_throttled_with_retry_after(): void
    {
        $phone = $this->trackedPhone('+966500000102');

        $first = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->postJson('/request-otp', ['phone' => $phone]);

        $first->assertStatus(200);
        $first->assertJsonPath('status', 'success');
        $this->assertIsInt($first->json('retry_after'));

        $second = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->postJson('/request-otp', ['phone' => $phone]);

        $second->assertStatus(429);
        $second->assertJsonPath('status', 'error');
        $this->assertGreaterThan(0, $second->json('retry_after'));
    }

    public function test_web_resend_is_throttled_right_after_a_request(): void
    {
        $phone = $this->trackedPhone('+966500000103');

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->postJson('/request-otp', ['phone' => $phone])
            ->assertStatus(200);

        $resend = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->postJson('/resend-otp', ['phone' => $phone]);

        $resend->assertStatus(429);
        $resend->assertJsonPath('status', 'error');
        $this->assertGreaterThan(0, $resend->json('retry_after'));
    }

    public function test_web_resend_succeeds_when_no_cooldown_active(): void
    {
        $phone = $this->trackedPhone('+966500000104');

        $resend = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->postJson('/resend-otp', ['phone' => $phone]);

        $resend->assertStatus(200);
        $resend->assertJsonPath('status', 'success');
        $this->assertIsInt($resend->json('retry_after'));
    }
}
