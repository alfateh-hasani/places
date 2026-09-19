<?php

namespace App\Services\Otp;

use App\Enums\OtpThrottleReason;

/**
 * Immutable outcome of asking {@see OtpRequestThrottle} whether a phone may be
 * sent an OTP right now. Presentation-neutral: it carries the seconds to wait
 * and the reason, but never decides which channel (API/web) or wording is used.
 */
final class OtpThrottleDecision
{
    private function __construct(
        public readonly bool $allowed,
        public readonly int $retryAfter,
        public readonly OtpThrottleReason $reason,
    ) {}

    public static function allow(): self
    {
        return new self(true, 0, OtpThrottleReason::None);
    }

    public static function cooldown(int $retryAfter): self
    {
        return new self(false, max(0, $retryAfter), OtpThrottleReason::Cooldown);
    }

    public static function blocked(int $retryAfter): self
    {
        return new self(false, max(0, $retryAfter), OtpThrottleReason::Blocked);
    }

    public function denied(): bool
    {
        return ! $this->allowed;
    }

    /**
     * Short waits rendered as a m:ss clock (e.g. 109 -> "01:49"). Only meaningful
     * for the cooldown reason; long lockouts don't surface a countdown.
     */
    public function retryAfterForHumans(): string
    {
        return sprintf('%02d:%02d', intdiv($this->retryAfter, 60), $this->retryAfter % 60);
    }

    /**
     * Remaining wait rounded up to whole hours — used for long lockout messages
     * (e.g. "try again after 24 hours") where a seconds clock is meaningless.
     */
    public function retryAfterInHours(): int
    {
        return (int) ceil($this->retryAfter / 3600);
    }
}
