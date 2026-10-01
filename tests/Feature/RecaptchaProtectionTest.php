<?php

namespace Tests\Feature;

use App\Rules\Recaptcha;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;
use TimeHunter\LaravelGoogleReCaptchaV3\Core\GoogleReCaptchaV3Response;
use TimeHunter\LaravelGoogleReCaptchaV3\Facades\GoogleReCaptchaV3;

/**
 * Covers reCAPTCHA v3 protection on the public contact form and the web OTP
 * endpoints (each OTP request sends a paid SMS). Google is never called: requests
 * without a token are rejected before any HTTP call, and verification results are
 * mocked on the facade.
 */
class RecaptchaProtectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        config()->set('googlerecaptchav3.is_service_enabled', true);
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    private function mockVerification(bool $success): void
    {
        $response = new GoogleReCaptchaV3Response(['success' => $success, 'score' => $success ? 0.9 : 0.1], '127.0.0.1');
        $response->setSuccess($success);

        GoogleReCaptchaV3::shouldReceive('setAction')->andReturnSelf();
        GoogleReCaptchaV3::shouldReceive('verifyResponse')->andReturn($response);
    }

    public function test_otp_request_without_token_is_rejected_and_no_sms_is_sent(): void
    {
        $response = $this->postJson('/request-otp', ['phone' => '+966500000201']);

        $response->assertStatus(422);
        $response->assertJsonPath('message', __('site.recaptcha_failed'));
        Notification::assertNothingSent();
    }

    public function test_otp_resend_without_token_is_rejected_and_no_sms_is_sent(): void
    {
        $response = $this->postJson('/resend-otp', ['phone' => '+966500000202']);

        $response->assertStatus(422);
        $response->assertJsonPath('message', __('site.recaptcha_failed'));
        Notification::assertNothingSent();
    }

    public function test_contact_form_without_token_is_rejected(): void
    {
        $response = $this->postJson('/contact-us', [
            'name' => 'Test',
            'email' => 'test@example.com',
            'phone' => '0500000000',
            'message' => 'Hello',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['g-recaptcha-response' => __('site.recaptcha_failed')]);
    }

    public function test_rule_passes_when_google_verifies_the_token(): void
    {
        $this->mockVerification(true);

        $validator = Validator::make(['g-recaptcha-response' => 'valid-token'], ['g-recaptcha-response' => [new Recaptcha('login')]]);

        $this->assertTrue($validator->passes());
    }

    public function test_rule_fails_when_google_rejects_the_token(): void
    {
        $this->mockVerification(false);

        $validator = Validator::make(['g-recaptcha-response' => 'low-score-token'], ['g-recaptcha-response' => [new Recaptcha('login')]]);

        $this->assertTrue($validator->fails());
        $this->assertSame(__('site.recaptcha_failed'), $validator->errors()->first('g-recaptcha-response'));
    }

    public function test_rule_passes_without_token_when_service_is_disabled(): void
    {
        config()->set('googlerecaptchav3.is_service_enabled', false);

        $validator = Validator::make([], ['g-recaptcha-response' => [new Recaptcha('login')]]);

        $this->assertTrue($validator->passes());
    }
}
