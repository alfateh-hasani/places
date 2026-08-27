<?php

namespace App\Services\Locks;

/**
 * Known Sciener/TTLock errcodes that will NEVER succeed on retry. Everything
 * else defaults to retryable (fail open: better to retry an unknown code a
 * few times than to silently give up on a real transient failure).
 */
final class ScienerErrorCode
{
    private const NON_RETRYABLE = [
        10007 => 'Invalid account or password',
        10003 => 'Access token invalid',
        10011 => 'Refresh token invalid or expired',
        20002 => 'This account does not administer the lock',
        -2018 => 'Permission denied',
        -4043 => 'Feature not supported by this lock',
    ];

    public static function isRetryable(?int $errcode): bool
    {
        if ($errcode === null) {
            return true;
        }

        return ! array_key_exists($errcode, self::NON_RETRYABLE);
    }

    public static function describe(?int $errcode): ?string
    {
        return $errcode !== null ? (self::NON_RETRYABLE[$errcode] ?? null) : null;
    }
}
