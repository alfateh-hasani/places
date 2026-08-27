<?php

namespace App\Traits;

use Illuminate\Support\Facades\RateLimiter;

trait ThrottlesOtpRequests
{
    protected function otpCooldownSeconds(): int
    {
        return (int) config('otp.request_cooldown', 60);
    }

    protected function otpThrottleKey(string $phone): string
    {
        return 'otp-request:' . $phone;
    }

    /**
     * Number of seconds the phone must still wait before it may request a new
     * OTP. Returns 0 when a new request is allowed.
     */
    protected function otpRetryAfter(string $phone): int
    {
        $key = $this->otpThrottleKey($phone);

        return RateLimiter::tooManyAttempts($key, 1)
            ? RateLimiter::availableIn($key)
            : 0;
    }

    /**
     * Record that an OTP was just sent to this phone, starting the cooldown.
     */
    protected function registerOtpSent(string $phone): void
    {
        RateLimiter::hit($this->otpThrottleKey($phone), $this->otpCooldownSeconds());
    }

    /**
     * Format a number of seconds as a mm:ss clock (e.g. 109 -> "01:49").
     */
    protected function otpRetryAfterForHumans(int $seconds): string
    {
        return sprintf('%02d:%02d', intdiv($seconds, 60), $seconds % 60);
    }
}
