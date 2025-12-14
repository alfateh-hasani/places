<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\OwnerRez\ProcessWebhookJob;
use App\Services\OwnerRez\OwnerRezSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
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

        $payload = [
            'event' => $request->input('type') ?? $request->input('Type'),
            'action' => $request->input('action') ?? $request->input('Action'),
            'data' => $request->input('data') ?? $request->all(),
            'received_at' => now()->toIso8601String(),
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

    public function oauthCallback(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'OwnerRez OAuth callback received.',
            'received_at' => now()->toIso8601String(),
            'state' => $request->query('state'),
        ]);
    }

    protected function guardWebhook(Request $request): void
    {
        $expectedUser = (string) Config::get('services.ownerrez.webhook_user');
        $expectedPassword = (string) Config::get('services.ownerrez.webhook_password');

        $providedUser = (string) $request->getUser();
        $providedPassword = (string) $request->getPassword();

        $isValidUser = $expectedUser !== '' && hash_equals($expectedUser, $providedUser);
        $isValidPassword = $expectedPassword !== '' && hash_equals($expectedPassword, $providedPassword);

        if (! $isValidUser || ! $isValidPassword) {
            abort(Response::HTTP_UNAUTHORIZED, 'Unauthorized webhook request.');
        }
    }

    protected function cacheKey(): string
    {
        return (string) Config::get('services.ownerrez.cache_key', 'ownerrez:webhook:latest');
    }

    protected function cacheTtl(): int
    {
        return (int) Config::get('services.ownerrez.cache_ttl', 180);
    }
}
