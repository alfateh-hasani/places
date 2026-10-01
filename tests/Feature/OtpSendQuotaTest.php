<?php

namespace Tests\Feature;

use App\Enums\OtpThrottleReason;
use App\Services\Otp\OtpRequestThrottle;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Covers the OTP send quota + lockout policy (config/otp.php):
 *   - at most `max_attempts` sends per `attempts_window`;
 *   - exceeding that locks the phone for `block_duration` seconds;
 *   - staff can lift the lock (customer support).
 *
 * The mobile/API contract is unchanged: a locked-out request returns the exact
 * same ApiResponse error envelope, only with a different message. SMS is faked.
 */
class OtpSendQuotaTest extends TestCase
{
    private array $phones = [];

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        config()->set('otp.max_attempts', 3);
        config()->set('otp.attempts_window', 600);
        config()->set('otp.block_duration', 86400);
        config()->set('otp.request_cooldown', 90);
    }

    protected function tearDown(): void
    {
        $throttle = app(OtpRequestThrottle::class);
        foreach ($this->phones as $phone) {
            $throttle->reset($phone);
        }

        parent::tearDown();
    }

    private function throttle(): OtpRequestThrottle
    {
        return app(OtpRequestThrottle::class);
    }

    private function phone(string $phone): string
    {
        $this->phones[] = $phone;

        return $phone;
    }

    public function test_exceeding_the_quota_locks_the_phone(): void
    {
        $phone = $this->phone('+966500000301');
        $throttle = $this->throttle();

        // First send is allowed; after it the cooldown is the active gate.
        $this->assertTrue($throttle->attempt($phone)->allowed);
        $throttle->recordSent($phone);
        $this->assertSame(OtpThrottleReason::Cooldown, $throttle->attempt($phone)->reason);

        // Simulate reaching the quota (3 sends in the window).
        $throttle->recordSent($phone);
        $throttle->recordSent($phone);

        // The 4th request is the violation → full lockout.
        $decision = $throttle->attempt($phone);
        $this->assertTrue($decision->denied());
        $this->assertSame(OtpThrottleReason::Blocked, $decision->reason);
        $this->assertGreaterThan(3600, $decision->retryAfter);
        $this->assertTrue($throttle->isBlocked($phone));
    }

    public function test_staff_reset_lifts_the_lock(): void
    {
        $phone = $this->phone('+966500000302');
        $throttle = $this->throttle();

        $throttle->recordSent($phone);
        $throttle->recordSent($phone);
        $throttle->recordSent($phone);
        $this->assertTrue($throttle->attempt($phone)->denied());

        $throttle->reset($phone);

        $this->assertTrue($throttle->attempt($phone)->allowed);
        $this->assertFalse($throttle->isBlocked($phone));
    }

    public function test_api_lockout_returns_the_same_envelope_with_block_message(): void
    {
        $phone = $this->phone('+966500000303');
        $throttle = $this->throttle();

        // Reach the quota via prior sends, then the next API request is blocked.
        $throttle->recordSent($phone);
        $throttle->recordSent($phone);
        $throttle->recordSent($phone);

        $response = $this->withHeader('x-secret-key', config('app.api_secret_key'))
            ->postJson('/api/otp/request', ['phone' => $phone]);

        $response->assertStatus(400);
        $response->assertJsonStructure(['success', 'errors', 'message', 'data']);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('data', null);
        $this->assertSame(trans('api.otp_blocked'), $response->json('message'));
    }

    public function test_web_lockout_returns_blocked_reason(): void
    {
        $phone = $this->phone('+966500000304');
        $throttle = $this->throttle();

        $throttle->recordSent($phone);
        $throttle->recordSent($phone);
        $throttle->recordSent($phone);

        $response = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->postJson('/request-otp', ['phone' => $phone]);

        $response->assertStatus(429);
        $response->assertJsonPath('status', 'error');
        $response->assertJsonPath('reason', 'otp_blocked');
    }
}
