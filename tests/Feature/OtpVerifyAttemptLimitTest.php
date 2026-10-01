<?php

namespace Tests\Feature;

use App\Services\Otp\OtpVerificationGuard;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\RateLimiter;
use SadiqSalau\LaravelOtp\Facades\Otp;
use Tests\TestCase;

/**
 * A 4-digit OTP must not be brute-forceable: after `otp.verify_max_attempts` wrong
 * codes the phone is refused (even with the right code) until a new OTP is sent.
 * Covers both the web and the mobile API login flows.
 */
class OtpVerifyAttemptLimitTest extends TestCase
{
    private const PHONE = '+966500000301';

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('otp.verify_max_attempts', 5);
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->withHeader('x-secret-key', config('app.api_secret_key'));
    }

    protected function tearDown(): void
    {
        RateLimiter::clear('otp-verify:'.self::PHONE);

        parent::tearDown();
    }

    private function failAttempts(string $uri, int $times): void
    {
        for ($i = 0; $i < $times; $i++) {
            $this->postJson($uri, ['phone' => self::PHONE, 'otp' => '0000']);
        }
    }

    public function test_web_verify_is_locked_after_max_wrong_codes(): void
    {
        $this->failAttempts('/verify-otp', 5);

        $response = $this->postJson('/verify-otp', ['phone' => self::PHONE, 'otp' => '1234']);

        $response->assertStatus(429);
        $response->assertJsonPath('message', __('site.otp_too_many_attempts'));
    }

    public function test_web_verify_still_answers_normally_below_the_limit(): void
    {
        $this->failAttempts('/verify-otp', 4);

        $response = $this->postJson('/verify-otp', ['phone' => self::PHONE, 'otp' => '0000']);

        $response->assertStatus(400);
        $response->assertJsonPath('message', __('site.otp_invalid'));
    }

    public function test_api_verify_is_locked_after_max_wrong_codes(): void
    {
        $this->failAttempts('/api/otp/verify', 5);

        $response = $this->postJson('/api/otp/verify', ['phone' => self::PHONE, 'otp' => '1234']);

        $response->assertStatus(429);
        $response->assertJsonPath('message', __('api.otp_too_many_attempts'));
    }

    public function test_lockout_discards_the_current_otp(): void
    {
        Otp::shouldReceive('identifier')->once()->with('otp_'.self::PHONE)->andReturnSelf();
        Otp::shouldReceive('clear')->once();

        $guard = app(OtpVerificationGuard::class);

        for ($i = 0; $i < 5; $i++) {
            $guard->recordFailure(self::PHONE);
        }

        $this->assertTrue($guard->isLockedOut(self::PHONE));
    }

    public function test_reset_lifts_the_lockout_for_a_new_code(): void
    {
        $this->failAttempts('/verify-otp', 5);

        app(OtpVerificationGuard::class)->reset(self::PHONE);

        $this->assertFalse(app(OtpVerificationGuard::class)->isLockedOut(self::PHONE));
    }
}
