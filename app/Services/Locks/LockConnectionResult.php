<?php

namespace App\Services\Locks;

final class LockConnectionResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly ?int $vendorErrorCode = null,
        public readonly ?string $message = null,
    ) {}
}
