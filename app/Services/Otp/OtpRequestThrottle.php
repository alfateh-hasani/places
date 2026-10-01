<?php

namespace App\Services\Otp;

use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * Server-side gate for OTP send requests, layering three protections that are
 * all configured in config/otp.php:
 *
 *   1. Cooldown  — a minimum spacing (seconds) between two sends.
 *   2. Quota     — at most `max_attempts` sends within `attempts_window` seconds.
 *   3. Lockout   — exceeding the quota locks the phone for `block_duration`
 *                  seconds; only the timeout or staff (support) lifts it.
 *
 * Enforced identically for the API and web login flows so a single phone number
 * can't be spammed with SMS from either channel. Backed by the cache (the same
 * store the OTP codes live in), so it needs no schema and expires on its own.
 */
class OtpRequestThrottle
{
    public function __construct(
        private readonly RateLimiter $limiter,
        private readonly Cache $cache,
    ) {}

    /**
     * Decide whether a fresh OTP may be sent to this phone. This is a command,
     * not a pure query: attempting a send once the quota is already exhausted is
     * itself the violation that escalates the phone into a full lockout.
     */
    public function attempt(string $phone): OtpThrottleDecision
    {
        if (($blocked = $this->blockedSecondsRemaining($phone)) > 0) {
            return OtpThrottleDecision::blocked($blocked);
        }

        if ($this->limiter->tooManyAttempts($this->attemptsKey($phone), $this->maxAttempts())) {
            return OtpThrottleDecision::blocked($this->lockOut($phone));
        }

        if (($cooldown = $this->cooldownSecondsRemaining($phone)) > 0) {
            return OtpThrottleDecision::cooldown($cooldown);
        }

        return OtpThrottleDecision::allow();
    }

    /**
     * Record a successful send: start the cooldown and consume one unit of the
     * windowed quota. Only successful sends count, so a failed SMS never burns
     * the customer's allowance.
     */
    public function recordSent(string $phone): void
    {
        $this->limiter->hit($this->cooldownKey($phone), $this->cooldownSeconds());
        $this->limiter->hit($this->attemptsKey($phone), $this->windowSeconds());
    }

    /**
     * Lift every restriction for this phone — cooldown, quota and lockout.
     * Used by staff when a customer contacts support.
     */
    public function reset(string $phone): void
    {
        $this->limiter->clear($this->cooldownKey($phone));
        $this->limiter->clear($this->attemptsKey($phone));
        $this->cache->forget($this->blockKey($phone));
    }

    public function isBlocked(string $phone): bool
    {
        return $this->blockedSecondsRemaining($phone) > 0;
    }

    public function blockedSecondsRemaining(string $phone): int
    {
        $expiresAt = (int) $this->cache->get($this->blockKey($phone), 0);

        return max(0, $expiresAt - now()->getTimestamp());
    }

    public function cooldownSeconds(): int
    {
        return (int) config('otp.request_cooldown', 90);
    }

    private function cooldownSecondsRemaining(string $phone): int
    {
        $key = $this->cooldownKey($phone);

        return $this->limiter->tooManyAttempts($key, 1)
            ? $this->limiter->availableIn($key)
            : 0;
    }

    /**
     * Escalate an over-quota phone into a lockout and return its duration.
     * Idempotent for an already-blocked phone: callers check the block first,
     * so a repeated over-quota attempt does not keep extending the window.
     */
    private function lockOut(string $phone): int
    {
        $duration = $this->blockSeconds();

        $this->cache->put(
            $this->blockKey($phone),
            now()->addSeconds($duration)->getTimestamp(),
            $duration,
        );

        return $duration;
    }

    private function maxAttempts(): int
    {
        return (int) config('otp.max_attempts', 3);
    }

    private function windowSeconds(): int
    {
        return (int) config('otp.attempts_window', 600);
    }

    private function blockSeconds(): int
    {
        return (int) config('otp.block_duration', 86400);
    }

    private function cooldownKey(string $phone): string
    {
        return 'otp-request:' . $phone;
    }

    private function attemptsKey(string $phone): string
    {
        return 'otp-attempts:' . $phone;
    }

    private function blockKey(string $phone): string
    {
        return 'otp-block:' . $phone;
    }
}
