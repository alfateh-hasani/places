<?php

namespace App\Services\Locks\Contracts;

use App\Services\Locks\LockCredentials;
use Carbon\CarbonInterface;

interface LockProviderInterface
{
    /**
     * Create a passcode on the vendor lock and return the vendor-assigned passcode id.
     *
     * @throws \App\Exceptions\Locks\LockOperationException
     */
    public function createPasscode(LockCredentials $credentials, string $passcode, CarbonInterface $start, CarbonInterface $end, string $name): string;

    /**
     * Delete a passcode from the vendor lock.
     *
     * @throws \App\Exceptions\Locks\LockOperationException
     */
    public function deletePasscode(LockCredentials $credentials, string $vendorPasscodeId): void;
}
