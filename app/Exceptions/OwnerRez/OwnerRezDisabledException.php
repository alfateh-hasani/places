<?php

namespace App\Exceptions\OwnerRez;

/**
 * Thrown by the hard backstop in OwnerRezApiService when the integration is
 * globally disabled (config('ownerrez.enabled') === false). It extends
 * OwnerRezApiException so existing `catch (OwnerRezApiException)` blocks keep
 * their fail-open behavior — this is a last-resort guard; every OwnerRez entry
 * point should already short-circuit before any API call is attempted.
 */
class OwnerRezDisabledException extends OwnerRezApiException
{
    public function __construct(string $endpoint = '')
    {
        parent::__construct('OwnerRez integration is disabled.', $endpoint, [], 503);
    }
}
