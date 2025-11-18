<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class OwnerRezWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $this->guardWebhook($request);

        $payload = [
            'event' => $request->input('type') ?? $request->input('Type'),
            'payload' => $request->all(),
            'received_at' => now()->toIso8601String(),
        ];

        Cache::put($this->cacheKey(), $payload, now()->addSeconds($this->cacheTtl()));

        return response()->json([
            'stored' => true,
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

    protected function ensureClientSecretAuthorized(Request $request): void
    {
        $providedSecret = $request->query('secret') ?? $request->header('X-OwnerRez-Secret');
        $expectedSecret = (string) Config::get('services.ownerrez.client_secret');

        if ($expectedSecret === '' || ! is_string($providedSecret) || ! hash_equals($expectedSecret, $providedSecret)) {
            abort(Response::HTTP_UNAUTHORIZED, 'Invalid secret.');
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
