<?php

namespace App\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class SanitizeContextProcessor implements ProcessorInterface
{
    /**
     * Keys that should always be fully redacted.
     */
    protected array $redactKeys = [
        'password',
        'password_confirmation',
        'token',
        'secret',
        'api_key',
        'access_token',
        'refresh_token',
        'authorization',
        'cookie',
        'credit_card',
        'card_number',
        'cvv',
        'ssn',
    ];

    /**
     * Keys that should be partially masked (show first/last few chars).
     */
    protected array $maskKeys = [
        'phone',
        'email',
        'ip',
        'ip_address',
    ];

    public function __invoke(LogRecord $record): LogRecord
    {
        if (empty($record->context)) {
            return $record;
        }

        return $record->with(context: $this->sanitize($record->context));
    }

    protected function sanitize(array $data): array
    {
        foreach ($data as $key => $value) {
            $lowerKey = strtolower((string) $key);

            if (is_array($value)) {
                $data[$key] = $this->sanitize($value);
                continue;
            }

            if ($this->shouldRedact($lowerKey)) {
                $data[$key] = '***REDACTED***';
                continue;
            }

            if ($this->shouldMask($lowerKey) && is_string($value)) {
                $data[$key] = $this->mask($value);
            }
        }

        return $data;
    }

    protected function shouldRedact(string $key): bool
    {
        foreach ($this->redactKeys as $redactKey) {
            if (str_contains($key, $redactKey)) {
                return true;
            }
        }

        return false;
    }

    protected function shouldMask(string $key): bool
    {
        foreach ($this->maskKeys as $maskKey) {
            if (str_contains($key, $maskKey)) {
                return true;
            }
        }

        return false;
    }

    protected function mask(string $value): string
    {
        $length = mb_strlen($value);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        if (str_contains($value, '@')) {
            $parts = explode('@', $value, 2);
            $local = $parts[0];
            $domain = $parts[1] ?? '';
            $maskedLocal = mb_substr($local, 0, 2) . str_repeat('*', max(mb_strlen($local) - 2, 0));

            return $maskedLocal . '@' . $domain;
        }

        return mb_substr($value, 0, 3) . str_repeat('*', $length - 5) . mb_substr($value, -2);
    }
}
