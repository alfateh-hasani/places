<?php

namespace App\Services\OwnerRez;

use App\Exceptions\OwnerRez\OwnerRezApiException;
use App\Models\OwnerRezApiLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OwnerRezApiService
{
    protected string $baseUrl;

    protected string $username;

    protected string $password;

    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = (string) (config('ownerrez.api_url') ?? '');
        $this->username = (string) (config('ownerrez.username') ?? '');
        $this->password = (string) (config('ownerrez.password') ?? '');
        $this->timeout = config('ownerrez.availability.timeout', 10);

        if ($this->username === '' || $this->password === '') {
            Log::warning('OwnerRez API credentials are missing.', [
                'username_set' => $this->username !== '',
                'password_set' => $this->password !== '',
            ]);
        }
    }

    /**
     * Get all properties
     */
    public function getProperties(): array
    {
        return $this->makeCurlRequest('/v2/properties');
    }

    /**
     * Get all properties across pages
     */
    public function getAllProperties(): array
    {
        $response = $this->getProperties();
        $items = $response['items'] ?? [];
        $nextPageUrl = $response['next_page_url'] ?? null;

        while (! empty($nextPageUrl)) {
            $response = $this->makeRequest('GET', $nextPageUrl);
            $items = array_merge($items, $response['items'] ?? []);
            $nextPageUrl = $response['next_page_url'] ?? null;
        }

        return $items;
    }

    /**
     * Get single property
     */
    public function getProperty(int $propertyId): array
    {
        return $this->makeRequest('GET', "/v2/properties/{$propertyId}");
    }

    /**
     * Get bookings with filters
     */
    public function getBookings(array $filters = []): array
    {
        $queryString = http_build_query($filters);

        return $this->makeRequest('GET', "/v2/bookings?{$queryString}");
    }

    /**
     * Get single booking
     */
    public function getBooking(int $bookingId): array
    {
        return $this->makeRequest('GET', "/v2/bookings/{$bookingId}");
    }

    /**
     * Create a new booking
     */
    public function createBooking(array $data): array
    {
        return $this->makeRequest('POST', '/v2/bookings', $data);
    }

    /**
     * Update a booking
     */
    public function updateBooking(int $bookingId, array $data): array
    {
        return $this->makeRequest('PUT', "/v2/bookings/{$bookingId}", $data);
    }

    /**
     * Cancel a booking
     */
    public function cancelBooking(int $bookingId): bool
    {
        try {
            $this->makeRequest('DELETE', "/v2/bookings/{$bookingId}");

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to cancel OwnerRez booking', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Create a new guest
     */
    public function createGuest(array $data): array
    {
        return $this->makeRequest('POST', '/v2/guests', $data);
    }

    /**
     * Get single guest
     */
    public function getGuest(int $guestId): array
    {
        return $this->makeRequest('GET', "/v2/guests/{$guestId}");
    }

    /**
     * Search for guests
     */
    public function searchGuests(array $filters = []): array
    {
        $queryString = http_build_query($filters);

        return $this->makeRequest('GET', "/v2/guests?{$queryString}");
    }

    /**
     * Register a webhook
     * Note: type should be a single value like 'booking', not an array
     * Note: action should be a single value like 'entity_create', not comma-separated
     */
    public function registerWebhook(string $url, string $type, string $action, ?string $category = null): array
    {
        $data = [
            'webhook_url' => $url,
            'type' => $type,
            'action' => $action,
        ];

        if ($category) {
            $data['category'] = $category;
        }

        return $this->makeRequest('POST', '/v2/webhooksubscriptions', $data);
    }

    /**
     * List all webhooks
     */
    public function listWebhooks(): array
    {
        return $this->makeRequest('GET', '/v2/webhooksubscriptions');
    }

    /**
     * Delete a webhook
     */
    public function deleteWebhook(int $webhookId): bool
    {
        try {
            $this->makeRequest('DELETE', "/v2/webhooksubscriptions/{$webhookId}");

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete OwnerRez webhook', [
                'webhook_id' => $webhookId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Create a custom field on an entity
     */
    public function createCustomField(int $entityId, string $entityType, int $fieldDefinitionId, string $value): array
    {
        return $this->makeRequest('POST', '/v2/fields', [
            'entity_id' => $entityId,
            'entity_type' => $entityType,
            'field_definition_id' => $fieldDefinitionId,
            'value' => $value,
        ]);
    }

    /**
     * Make HTTP request to OwnerRez API
     */
    protected function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        $startTime = microtime(true);
        $url = $this->baseUrl.$endpoint;

        try {
            $request = Http::timeout($this->timeout)
                ->withBasicAuth($this->username, $this->password)
                ->acceptJson();

            $response = match (strtoupper($method)) {
                'GET' => $request->get($url),
                'POST' => $request->post($url, $data),
                'PUT' => $request->put($url, $data),
                'DELETE' => $request->delete($url),
                'PATCH' => $request->patch($url, $data),
                default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
            };

            $duration = (int) ((microtime(true) - $startTime) * 1000);
            $responseData = $response->json();
            $statusCode = $response->status();

            // Log the request
            $this->logRequest($endpoint, $method, $data, $responseData, $statusCode, $duration);

            // Handle rate limiting
            if ($statusCode === 429) {
                $this->handleRateLimit();

                return $this->makeRequest($method, $endpoint, $data);
            }

            // Handle authentication errors
            if ($statusCode === 401) {
                $this->refreshToken();

                return $this->makeRequest($method, $endpoint, $data);
            }

            // Handle other errors
            if (! $response->successful()) {
                throw new OwnerRezApiException(
                    'OwnerRez API request failed: '.($responseData['message'] ?? 'Unknown error'),
                    $endpoint,
                    $responseData ?? [],
                    $statusCode
                );
            }

            return $responseData ?? [];
        } catch (OwnerRezApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            $duration = (int) ((microtime(true) - $startTime) * 1000);
            $this->logRequest($endpoint, $method, $data, ['error' => $e->getMessage()], 500, $duration);

            throw new OwnerRezApiException(
                'OwnerRez API request failed: '.$e->getMessage(),
                $endpoint,
                [],
                500,
                $e
            );
        }
    }

    /**
     * Make HTTP request using cURL (for properties list)
     */
    protected function makeCurlRequest(string $endpoint): array
    {
        if ($this->username === '' || $this->password === '') {
            throw new OwnerRezApiException('OwnerRez API credentials are missing.', $endpoint, [], 401);
        }

        $url = $this->baseUrl.$endpoint;
        $auth = base64_encode($this->username.':'.$this->password);

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Basic '.$auth,
            ],
        ]);

        $response = curl_exec($curl);
        $curlError = curl_error($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($response === false) {
            throw new OwnerRezApiException('OwnerRez API request failed: '.$curlError, $endpoint, [], 500);
        }

        $decoded = json_decode($response, true);
        if ($httpCode >= 400) {
            throw new OwnerRezApiException(
                'OwnerRez API request failed',
                $endpoint,
                $decoded ?? [],
                $httpCode
            );
        }

        return $decoded ?? [];
    }

    /**
     * Handle rate limiting
     */
    protected function handleRateLimit(): void
    {
        $retryAfter = config('ownerrez.rate_limit.retry_after_seconds', 60);
        Log::warning('OwnerRez API rate limit reached, waiting...', ['retry_after' => $retryAfter]);
        sleep($retryAfter);
    }

    /**
     * Log API request
     */
    protected function logRequest(
        string $endpoint,
        string $method,
        ?array $requestData,
        ?array $responseData,
        ?int $statusCode,
        ?int $durationMs
    ): void {
        OwnerRezApiLog::logRequest(
            $endpoint,
            $method,
            $requestData,
            $responseData,
            $statusCode,
            $durationMs
        );
    }
}
