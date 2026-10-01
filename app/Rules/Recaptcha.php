<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Log;
use Illuminate\Translation\PotentiallyTranslatedString;
use TimeHunter\LaravelGoogleReCaptchaV3\Facades\GoogleReCaptchaV3;

/**
 * Verifies a reCAPTCHA v3 token against Google for the given action. Score thresholds
 * per action live in config/googlerecaptchav3.php; when the service is disabled
 * (e.g. in tests) every request passes.
 */
class Recaptcha implements ValidationRule
{
    /**
     * Run even when the token is missing, so a request without one is rejected.
     */
    public bool $implicit = true;

    public function __construct(private readonly string $action) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $response = GoogleReCaptchaV3::setAction($this->action)
            ->verifyResponse((string) $value, request()->getClientIp());

        if ($response->isSuccess()) {
            return;
        }

        Log::warning('reCAPTCHA verification failed', [
            'action' => $this->action,
            'reason' => $response->getMessage(),
            'score' => $response->getScore(),
            'ip' => request()->getClientIp(),
        ]);

        $fail(__('site.recaptcha_failed'));
    }
}
