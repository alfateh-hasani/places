<?php

namespace App\Services\Locks;

use App\Exceptions\Locks\LockOperationException;
use Throwable;

/**
 * Turns a smart-lock failure into clear, human-readable detail for admins:
 * the underlying message, the vendor (Sciener/TTLock) error code and what it
 * means, and whether it will self-heal on retry or needs a human to fix it.
 *
 * Used both when persisting the failure (bookings.passcode_error) and when
 * flashing the result of a manual "regenerate" back to the admin.
 */
class LockErrorPresenter
{
    /**
     * @return array{summary:string, vendor_code:?int, vendor_desc:?string, retryable:bool}
     */
    public static function describe(Throwable $e): array
    {
        $summary = trim($e->getMessage());
        $vendorCode = $e instanceof LockOperationException ? $e->vendorErrorCode : null;
        $retryable = $e instanceof LockOperationException ? $e->retryable : true;

        return [
            'summary' => $summary !== '' ? $summary : 'Unknown smart-lock error',
            'vendor_code' => $vendorCode,
            'vendor_desc' => ScienerErrorCode::describe($vendorCode),
            'retryable' => $retryable,
        ];
    }

    /**
     * A single self-describing line to persist in bookings.passcode_error, so the
     * stored value is meaningful on its own even without the original exception.
     */
    public static function storedMessage(Throwable $e): string
    {
        $d = self::describe($e);
        $parts = [$d['summary']];

        if ($d['vendor_code'] !== null) {
            $code = 'code '.$d['vendor_code'];
            if ($d['vendor_desc']) {
                $code .= ' — '.$d['vendor_desc'];
            }
            $parts[] = '['.$code.']';
        }

        $parts[] = $d['retryable'] ? '(retryable)' : '(permanent — needs manual fix)';

        return implode(' ', $parts);
    }
}
