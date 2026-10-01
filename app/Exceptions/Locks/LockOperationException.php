<?php

namespace App\Exceptions\Locks;

use RuntimeException;

class LockOperationException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $vendorErrorCode = null,
        public readonly bool $retryable = true,
    ) {
        parent::__construct($message);
    }
}
