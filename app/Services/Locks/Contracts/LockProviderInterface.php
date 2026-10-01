<?php

namespace App\Services\Locks\Contracts;

use App\Services\Locks\LockConnectionResult;
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

    /**
     * Verify these credentials can authenticate right now. Never throws —
     * used by diagnostic tooling that needs to check many accounts without
     * aborting on the first failure.
     */
    public function testConnection(LockCredentials $credentials): LockConnectionResult;

    /**
     * List the vendor lock ids this account actually administers. Returns an
     * empty array if the credentials can't authenticate at all — diagnostic
     * tooling should run testConnection() first to tell the two apart.
     *
     * @return array<int, string>
     */
    public function listManagedLockIds(LockCredentials $credentials): array;
}
