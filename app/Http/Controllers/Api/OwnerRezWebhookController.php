<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\OwnerRez\ProcessWebhookJob;
use App\Services\OwnerRez\OwnerRezSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class OwnerRezWebhookController extends Controller
{
    public function __construct(
        protected OwnerRezSyncService $syncService
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $this->guardWebhook($request);

        // Support both old and new webhook formats
        $payload = [
            'event' => $request->input('entity_type') ?? $request->input('type') ?? $request->input('Type'),
            'action' => $request->input('action') ?? $request->input('Action'),
            'data' => $request->input('entity') ?? $request->input('data') ?? $request->all(),
            'entity_id' => $request->input('entity_id'),
            'categories' => $request->input('categories', []),
            'received_at' => now()->toIso8601String(),
            'raw' => $request->all(),
        ];

        // Store in cache for debugging
        Cache::put($this->cacheKey(), $payload, now()->addSeconds($this->cacheTtl()));

        // Process webhook asynchronously if auto sync is enabled
        if (config('ownerrez.sync.auto_sync_inbound')) {
            try {
                ProcessWebhookJob::dispatch($payload);

                Log::info('OwnerRez webhook queued for processing', [
                    'event' => $payload['event'],
                    'action' => $payload['action'],
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to queue OwnerRez webhook', [
                    'error' => $e->getMessage(),
                    'payload' => $payload,
                ]);

                // Fallback to synchronous processing
                try {
                    $this->syncService->syncBookingFromWebhook($payload);
                } catch (\Exception $syncError) {
                    Log::error('Failed to process OwnerRez webhook synchronously', [
                        'error' => $syncError->getMessage(),
                    ]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Webhook received and queued for processing',
            'expires_in_seconds' => $this->cacheTtl(),
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        $this->ensureClientSecretAuthorized($request);

        $payload = Cache::get($this->cacheKey());

        if ($payload === null) {
            return response()->json([
                'message' => 'No cached webhook payload found.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json($payload);
    }

    public function oauthCallback(Request $request)
    {
        $code = $request->query('code');
        $state = $request->query('state');
        $error = $request->query('error');

        // Handle error from OwnerRez
        if ($error) {
            Log::error('OwnerRez OAuth error', [
                'error' => $error,
                'error_description' => $request->query('error_description'),
            ]);

            return view('ownerrez.oauth-error', [
                'error' => $error,
                'description' => $request->query('error_description'),
            ]);
        }

        // Validate code
        if (! $code) {
            return view('ownerrez.oauth-error', [
                'error' => 'missing_code',
                'description' => 'Authorization code is missing',
            ]);
        }

        try {
            // Exchange code for access token
            $tokenData = $this->exchangeCodeForToken($code);

            // Store the access token
            $this->storeAccessToken($tokenData);

            Log::info('OwnerRez OAuth successful', [
                'user_id' => $tokenData['user_id'] ?? null,
            ]);

            return view('ownerrez.oauth-success', [
                'user_id' => $tokenData['user_id'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to exchange OAuth code', [
                'error' => $e->getMessage(),
            ]);

            return view('ownerrez.oauth-error', [
                'error' => 'exchange_failed',
                'description' => $e->getMessage(),
            ]);
        }
    }

    protected function exchangeCodeForToken(string $code): array
    {
        $clientId = config('ownerrez.oauth.client_id');
        $clientSecret = config('ownerrez.oauth.client_secret');
        $redirectUri = config('ownerrez.oauth.redirect_uri');

        if (! $clientId || ! $clientSecret) {
            throw new \Exception('OAuth credentials not configured');
        }

        $response = \Illuminate\Support\Facades\Http::withBasicAuth($clientId, $clientSecret)
            ->asForm()
            ->post('https://api.ownerrez.com/oauth/access_token', [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $redirectUri,
            ]);

        if (! $response->successful()) {
            $error = $response->json('error', 'unknown_error');
            $description = $response->json('error_description', 'Failed to exchange code for token');
            throw new \Exception("{$error}: {$description}");
        }

        return $response->json();
    }

    protected function storeAccessToken(array $tokenData): void
    {
        // Store in config file or database
        $accessToken = $tokenData['access_token'];
        $userId = $tokenData['user_id'];

        // For now, store in cache (you can change this to database)
        Cache::forever('ownerrez_access_token', $accessToken);
        Cache::forever('ownerrez_user_id', $userId);

        // Also update .env file (optional)
        $this->updateEnvFile([
            'OWNERREZ_ACCESS_TOKEN' => $accessToken,
            'OWNERREZ_USER_ID' => $userId,
        ]);

        Log::info('Stored OwnerRez access token', [
            'user_id' => $userId,
        ]);
    }

    protected function updateEnvFile(array $data): void
    {
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            return;
        }

        $envContent = file_get_contents($envPath);

        foreach ($data as $key => $value) {
            // Check if key exists
            if (preg_match("/^{$key}=/m", $envContent)) {
                // Update existing
                $envContent = preg_replace(
                    "/^{$key}=.*/m",
                    "{$key}={$value}",
                    $envContent
                );
            } else {
                // Add new
                $envContent .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $envContent);
    }

    protected function guardWebhook(Request $request): void
    {
        $expectedUser = (string) config('ownerrez.webhook.user');
        $expectedPassword = (string) config('ownerrez.webhook.password');

        $providedUser = (string) $request->getUser();
        $providedPassword = (string) $request->getPassword();

        $isValidUser = $expectedUser !== '' && hash_equals($expectedUser, $providedUser);
        $isValidPassword = $expectedPassword !== '' && hash_equals($expectedPassword, $providedPassword);

        if (! $isValidUser || ! $isValidPassword) {
            Log::warning('Unauthorized OwnerRez webhook request', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            abort(Response::HTTP_UNAUTHORIZED, 'Unauthorized webhook request.');
        }

        Log::info('OwnerRez webhook authenticated successfully');
    }

    protected function cacheKey(): string
    {
        return 'ownerrez:webhook:latest';
    }

    protected function cacheTtl(): int
    {
        return (int) config('ownerrez.webhook.cache_ttl', 180);
    }
}
