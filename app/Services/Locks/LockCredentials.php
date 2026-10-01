<?php

namespace App\Services\Locks;

final class LockCredentials
{
    public function __construct(
        public readonly string $lockId,
        public readonly ?string $username,
        public readonly ?string $password,
    ) {}
}
