<?php

namespace App\Services\Otp;

use Illuminate\Cache\RateLimiter;
use SadiqSalau\LaravelOtp\Facades\Otp;

/**
 * Caps wrong guesses against a phone's current OTP. A 4-digit code has only 10,000
 * values, so without a cap anyone could brute-force their way into any customer's
 * account (and its smart-lock passcodes) within the code's lifetime.
 *
 * After `verify_max_attempts` wrong codes the current OTP is discarded; the customer
 * must request a new one, which OtpRequestThrottle rate-limits and eventually locks.
 * Shared by the API and web login flows, configured in config/otp.php.
 */
class OtpVerificationGuard
{
    public function __construct(private readonly RateLimiter $limiter) {}

    public function isLockedOut(string $phone): bool
    {
        return $this->limiter->tooManyAttempts($this->key($phone), $this->maxAttempts());
    }

    /**
     * Count a wrong code; on the last allowed attempt the OTP itself is invalidated.
     */
    public function recordFailure(string $phone): void
    {
        $this->limiter->hit($this->key($phone), $this->windowSeconds());

        if ($this->isLockedOut($phone)) {
            Otp::identifier('otp_'.$phone)->clear();
        }
    }

    /**
     * Start a fresh allowance — after a successful login or when a new code is sent.
     */
    public function reset(string $phone): void
    {
        $this->limiter->clear($this->key($phone));
    }

    private function maxAttempts(): int
    {
        return (int) config('otp.verify_max_attempts', 5);
    }

    private function windowSeconds(): int
    {
        return (int) config('otp.expires', 15) * 60;
    }

    private function key(string $phone): string
    {
        return 'otp-verify:'.$phone;
    }
}
