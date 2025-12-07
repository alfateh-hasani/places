<?php

namespace App\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GuestyService
{
    private const TOKEN_EXPIRY_BUFFER_SECONDS = 300;

    private string $baseUrl;

    private string $clientId;

    private string $clientSecret;

    private string $tokenUrl;

    private string $tokenCacheKey;

    private int $timeout;

    public function __construct(private CacheRepository $cache)
    {
        $this->baseUrl = rtrim((string) config('services.guesty.base_url', 'https://open-api.guesty.com'), '/');
        $this->clientId = (string) config('services.guesty.client_id');
        $this->clientSecret = (string) config('services.guesty.client_secret');
        $this->tokenUrl = (string) config('services.guesty.token_url', 'https://open-api.guesty.com/oauth2/token');
        $this->tokenCacheKey = (string) config('services.guesty.token_cache_key', 'guesty:access-token');
        $this->timeout = (int) config('services.guesty.timeout', 10);

        if ($this->clientId === '' || $this->clientSecret === '') {
            throw new RuntimeException('Guesty credentials are not configured.');
        }
    }

    public function authenticate(bool $forceRefresh = false): array
    {
        if (! $forceRefresh) {
            $cachedToken = $this->getTokenFromCache();

            if ($cachedToken !== null) {
                return $cachedToken;
            }
        }

        $response = Http::timeout($this->timeout)
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->post($this->tokenUrl, [
                'clientId' => $this->clientId,
                'clientSecret' => $this->clientSecret,
            ])
            ->throw();

        $tokenData = $this->formatTokenResponse($response);

        $this->cache->put($this->tokenCacheKey, $tokenData, Carbon::parse($tokenData['expires_at']));

        return $tokenData;
    }

    public function listings(array $filters = []): array
    {
        return $this->authorizedRequest()
            ->get('/v1/listings', $this->buildQuery($filters, [
                'limit',
                'skip',
                'cursor',
                'sort',
                'query',
                'status',
                'fields',
            ]))
            ->throw()
            ->json();
    }

    public function reservations(array $filters = []): array
    {
        return $this->authorizedRequest()
            ->get('/v1/reservations', $this->buildQuery($filters, [
                'limit',
                'skip',
                'cursor',
                'sort',
                'query',
                'from',
                'to',
                'listingId',
                'status',
            ]))
            ->throw()
            ->json();
    }

    public function reservation(string $reservationId, array $params = []): array
    {
        return $this->authorizedRequest()
            ->get("/v1/reservations/{$reservationId}", $this->buildQuery($params, ['fields']))
            ->throw()
            ->json();
    }

    private function formatTokenResponse(Response $response): array
    {
        $payload = $response->json();
        $accessToken = (string) ($payload['access_token'] ?? $payload['accessToken'] ?? '');

        if ($accessToken === '') {
            throw new RuntimeException('Guesty API did not return an access token.');
        }

        $tokenType = (string) ($payload['token_type'] ?? $payload['tokenType'] ?? 'Bearer');
        $expiresIn = (int) ($payload['expires_in'] ?? $payload['expiresIn'] ?? 86400);
        $effectiveTtl = max(60, $expiresIn - self::TOKEN_EXPIRY_BUFFER_SECONDS);
        $expiresAt = now()->addSeconds($effectiveTtl);

        return [
            'access_token' => $accessToken,
            'token_type' => $tokenType,
            'expires_in' => $expiresIn,
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }

    private function authorizedRequest(): PendingRequest
    {
        $tokenData = $this->authenticate();

        return $this->http()
            ->withToken($tokenData['access_token'], $tokenData['token_type'] ?? 'Bearer');
    }

    private function buildQuery(array $params, array $allowedKeys): array
    {
        $allowed = array_flip($allowedKeys);
        $filtered = array_intersect_key($params, $allowed);

        return array_filter($filtered, static fn ($value) => $value !== null && $value !== '');
    }

    private function getTokenFromCache(): ?array
    {
        $cached = $this->cache->get($this->tokenCacheKey);

        if (! is_array($cached) || ! isset($cached['expires_at'])) {
            return null;
        }

        if (Carbon::parse($cached['expires_at'])->isPast()) {
            return null;
        }

        return $cached;
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->timeout($this->timeout);
    }
}
