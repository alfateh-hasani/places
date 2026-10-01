<?php

namespace App\Services\Locks\Providers;

use App\Exceptions\Locks\LockOperationException;
use App\Services\Locks\Contracts\LockProviderInterface;
use App\Services\Locks\LockConnectionResult;
use App\Services\Locks\LockCredentials;
use App\Services\Locks\ScienerErrorCode;
use App\Services\ScienerLockService;
use Carbon\CarbonInterface;

class ScienerLockProvider implements LockProviderInterface
{
    public function createPasscode(LockCredentials $credentials, string $passcode, CarbonInterface $start, CarbonInterface $end, string $name): string
    {
        $service = $this->client($credentials);
        $response = $service->addCustomPasscode($credentials->lockId, $passcode, $start, $end, $name);

        if (! $response || ! isset($response['keyboardPwdId'])) {
            throw $this->exceptionFor($service, "Sciener API failed to create a passcode for lock {$credentials->lockId}");
        }

        return (string) $response['keyboardPwdId'];
    }

    public function deletePasscode(LockCredentials $credentials, string $vendorPasscodeId): void
    {
        $service = $this->client($credentials);
        $response = $service->deletePasscode($credentials->lockId, $vendorPasscodeId);

        if (! $response) {
            throw $this->exceptionFor($service, "Sciener API failed to delete passcode {$vendorPasscodeId} for lock {$credentials->lockId}");
        }
    }

    public function testConnection(LockCredentials $credentials): LockConnectionResult
    {
        $service = $this->client($credentials);
        $token = $service->getValidAccessToken();

        if ($token) {
            return new LockConnectionResult(ok: true);
        }

        $error = $service->getLastError();

        return new LockConnectionResult(
            ok: false,
            vendorErrorCode: $error['errcode'] ?? null,
            message: $error['errmsg'] ?? 'Unable to obtain an access token',
        );
    }

    public function listManagedLockIds(LockCredentials $credentials): array
    {
        return $this->client($credentials)->listLockIds();
    }

    private function exceptionFor(ScienerLockService $service, string $message): LockOperationException
    {
        $error = $service->getLastError();
        $errcode = $error['errcode'] ?? null;
        $errmsg = $error['errmsg'] ?? null;

        return new LockOperationException(
            $errmsg ? "{$message}: {$errmsg}" : "{$message}.",
            vendorErrorCode: $errcode,
            retryable: ScienerErrorCode::isRetryable($errcode),
        );
    }

    private function client(LockCredentials $credentials): ScienerLockService
    {
        return new ScienerLockService($credentials->username, $credentials->password);
    }
}
