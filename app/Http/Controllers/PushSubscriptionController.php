<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Stores/removes browser Web Push subscriptions for the currently authenticated
 * recipient — either a staff User (Backpack guard) or a web Customer (customer
 * guard). Untouched by the mobile FCM flow, which uses `Customer.fcm_token`.
 */
class PushSubscriptionController extends Controller
{
    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
            'contentEncoding' => ['nullable', 'string'],
        ]);

        $subscriber = $this->currentSubscriber();

        if ($subscriber === null) {
            return response()->json(['success' => false], 401);
        }

        $subscriber->updatePushSubscription(
            endpoint: $validated['endpoint'],
            key: $validated['keys']['p256dh'],
            token: $validated['keys']['auth'],
            contentEncoding: $validated['contentEncoding'] ?? null,
        );

        return response()->json(['success' => true]);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string'],
        ]);

        $subscriber = $this->currentSubscriber();

        if ($subscriber === null) {
            return response()->json(['success' => false], 401);
        }

        $subscriber->deletePushSubscription($validated['endpoint']);

        return response()->json(['success' => true]);
    }

    /**
     * Resolve the authenticated recipient across the staff (Backpack) and web
     * customer guards. Both models use the HasPushSubscriptions trait.
     */
    private function currentSubscriber(): ?Authenticatable
    {
        if (backpack_auth()->check()) {
            return backpack_auth()->user();
        }

        if (auth('customer')->check()) {
            return auth('customer')->user();
        }

        return null;
    }
}
