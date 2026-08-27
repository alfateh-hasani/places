<?php

namespace App\Services\Locks\Providers;

use App\Exceptions\Locks\LockOperationException;
use App\Services\Locks\Contracts\LockProviderInterface;
use App\Services\Locks\LockCredentials;
use App\Services\ScienerLockService;
use Carbon\CarbonInterface;

class ScienerLockProvider implements LockProviderInterface
{
    public function createPasscode(LockCredentials $credentials, string $passcode, CarbonInterface $start, CarbonInterface $end, string $name): string
    {
        $response = $this->client($credentials)->addCustomPasscode(
            $credentials->lockId,
            $passcode,
            $start,
            $end,
            $name,
        );

        if (! $response || ! isset($response['keyboardPwdId'])) {
            throw new LockOperationException("Sciener API failed to create a passcode for lock {$credentials->lockId}.");
        }

        return (string) $response['keyboardPwdId'];
    }

    public function deletePasscode(LockCredentials $credentials, string $vendorPasscodeId): void
    {
        $response = $this->client($credentials)->deletePasscode($credentials->lockId, $vendorPasscodeId);

        if (! $response) {
            throw new LockOperationException("Sciener API failed to delete passcode {$vendorPasscodeId} for lock {$credentials->lockId}.");
        }
    }

    private function client(LockCredentials $credentials): ScienerLockService
    {
        return new ScienerLockService($credentials->username, $credentials->password);
    }
}
