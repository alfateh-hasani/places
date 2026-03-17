<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\OwnerRez\ProcessWebhookJob;
use App\Services\OwnerRez\OwnerRezSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

        // Validate Content-Type
        if (! $request->isJson() && ! $request->hasHeader('Content-Type')) {
            abort(Response::HTTP_UNSUPPORTED_MEDIA_TYPE, 'Content-Type must be application/json.');
        }

        // Deduplicate webhook events using Cache (fast) + DB (persistent)
        $webhookEventId = $request->input('id');
        if ($webhookEventId) {
            $dedupKey = "ownerrez:webhook:event:{$webhookEventId}";

            // Fast check via cache first
            if (Cache::has($dedupKey)) {
                Log::channel('ownerrez_webhook')->info('OwnerRez duplicate webhook event ignored', [
                    'webhook_event_id' => $webhookEventId,
                    'action' => $request->input('action'),
                    'entity_id' => $request->input('entity_id'),
                ]);

                return response()->json(['success' => true, 'message' => 'Duplicate webhook event ignored']);
            }

            // Persistent check via DB (survives cache flush)
            try {
                $existsInDb = DB::table('ownerrez_processed_webhooks')
                    ->where('webhook_event_id', $webhookEventId)
                    ->exists();

                if ($existsInDb) {
                    Cache::put($dedupKey, true, now()->addHours(2));

                    Log::channel('ownerrez_webhook')->info('OwnerRez duplicate webhook event ignored (DB)', [
                        'webhook_event_id' => $webhookEventId,
                    ]);

                    return response()->json(['success' => true, 'message' => 'Duplicate webhook event ignored']);
                }

                // Record in both cache and DB
                Cache::put($dedupKey, true, now()->addHours(2));
                DB::table('ownerrez_processed_webhooks')->insert([
                    'webhook_event_id' => $webhookEventId,
                    'action' => $request->input('action'),
                    'entity_id' => $request->input('entity_id'),
                    'processed_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Exception $e) {
                // DB unavailable - fall back to cache-only dedup
                Cache::put($dedupKey, true, now()->addHours(2));
                Log::channel('ownerrez_webhook')->warning('DB dedup check failed, using cache only', [
                    'webhook_event_id' => $webhookEventId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Deduplicate twin webhooks from OwnerRez (same entity_id + action + categories + timestamp, different UUID)
        $entityId = $request->input('entity_id');
        $action = $request->input('action');
        $categories = $request->input('categories', []);
        $entityTimestamp = $request->input('entity.updated_utc') ?? $request->input('entity.created_utc') ?? '';
        if ($entityId && $action) {
            sort($categories);
            $contentDedupKey = 'ownerrez:webhook:content:'.md5($entityId.':'.$action.':'.implode(',', $categories).':'.$entityTimestamp);
            if (Cache::has($contentDedupKey)) {
                Log::channel('ownerrez_webhook')->info('OwnerRez twin webhook ignored', [
                    'webhook_event_id' => $webhookEventId ?? null,
                    'entity_id' => $entityId,
                    'action' => $action,
                    'categories' => $categories,
                ]);

                return response()->json(['success' => true, 'message' => 'Twin webhook ignored']);
            }
            Cache::put($contentDedupKey, true, now()->addSeconds(30));
        }

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

        Log::channel('ownerrez_webhook')->info('OwnerRez webhook received', [
            'webhook_event_id' => $webhookEventId,
            'action' => $payload['action'],
            'entity_id' => $payload['entity_id'],
            'categories' => $payload['categories'],
            'is_block' => $request->input('entity.is_block'),
            'status' => $request->input('entity.status'),
        ]);

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
                    'event' => $payload['event'] ?? null,
                    'action' => $payload['action'] ?? null,
                    'entity_id' => $payload['entity_id'] ?? null,
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
        // Only allow in non-production environments
        if (app()->isProduction()) {
            abort(Response::HTTP_FORBIDDEN, 'Debug endpoint not available in production.');
        }

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

        // Validate state parameter to prevent CSRF
        $expectedState = Cache::pull('ownerrez:oauth:state');
        if (! $state || ! $expectedState || ! hash_equals($expectedState, $state)) {
            Log::warning('OwnerRez OAuth state mismatch - possible CSRF attempt', [
                'ip' => $request->ip(),
            ]);

            return view('ownerrez.oauth-error', [
                'error' => 'invalid_state',
                'description' => 'Invalid or expired OAuth state. Please try again.',
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
        $accessToken = $tokenData['access_token'];
        $userId = $tokenData['user_id'];

        Cache::forever('ownerrez_access_token', $accessToken);
        Cache::forever('ownerrez_user_id', $userId);

        Log::info('Stored OwnerRez access token', [
            'user_id' => $userId,
        ]);
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
