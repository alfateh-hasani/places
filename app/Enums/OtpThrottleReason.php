<?php

namespace App\Enums;

/**
 * Why an OTP send request was (or wasn't) allowed by {@see \App\Services\Otp\OtpRequestThrottle}.
 *
 *   None     → the request is allowed
 *   Cooldown → too soon after the previous send (short, seconds-scale wait)
 *   Blocked  → the send quota was exceeded; the phone is locked out for a long
 *              period and only staff (or the timeout) can lift it
 */
enum OtpThrottleReason
{
    case None;
    case Cooldown;
    case Blocked;

    /**
     * The `otp_*` translation key (within any channel namespace, e.g. `api.` or
     * `site.`) that describes this reason to the end user.
     */
    public function messageKey(): string
    {
        return match ($this) {
            self::Blocked => 'otp_blocked',
            default => 'otp_cooldown',
        };
    }
}
