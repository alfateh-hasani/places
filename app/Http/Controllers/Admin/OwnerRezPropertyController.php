<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OwnerRezPropertyController
{
    public static function options(): array
    {
        $user = (string) (config('services.ownerrez.webhook_user')
            ?? config('services.ownerrez.username')
            ?? config('ownerrez.username')
            ?? '');
        $password = (string) (config('services.ownerrez.webhook_password')
            ?? config('services.ownerrez.password')
            ?? config('ownerrez.password')
            ?? '');
        $baseUrl = rtrim((string) (config('ownerrez.api_url') ?? 'https://api.ownerrez.com'), '/');
        $fallback = ['469630' => 'unit 1 (#469630)'];

        if ($user === '' || $password === '') {
            return $fallback;
        }

        try {
            $response = Http::withBasicAuth($user, $password)
                ->acceptJson()
                ->get("{$baseUrl}/v2/properties", [
                    'page' => 1,
                    'page_size' => 200,
                    'embed' => 'units',
                ]);

            if ($response->failed()) {
                Log::warning('ownerrez.properties.fetch_failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return $fallback;
            }

            $data = $response->json() ?? [];
            $rawItems = $data['items'] ?? $data['data'] ?? $data;

            $items = collect(is_array($rawItems) ? $rawItems : [])
                ->filter(fn ($item) => isset($item['id']))
                ->mapWithKeys(fn ($item) => [
                    (string) $item['id'] => trim(($item['name'] ?? 'Property').' (#'.($item['id'] ?? '').')'),
                ])
                ->all();

            Log::info('ownerrez.properties.fetch_ok', ['count' => count($items)]);

            return $items !== [] ? $items : $fallback;
        } catch (\Throwable $e) {
            Log::error('ownerrez.properties.fetch_exception', ['error' => $e->getMessage()]);

            return $fallback;
        }
    }

    public function index(Request $request): JsonResponse
    {
        $user = (string) (config('services.ownerrez.webhook_user')
            ?? config('services.ownerrez.username')
            ?? config('ownerrez.username')
            ?? '');
        $password = (string) (config('services.ownerrez.webhook_password')
            ?? config('services.ownerrez.password')
            ?? config('ownerrez.password')
            ?? '');
        $baseUrl = rtrim((string) (config('ownerrez.api_url') ?? 'https://api.ownerrez.com'), '/');

        if ($user === '' || $password === '') {
            return response()->json(['results' => [], 'pagination' => ['more' => false]]);
        }

        try {
            $response = Http::withBasicAuth($user, $password)
                ->acceptJson()
                ->get("{$baseUrl}/v2/properties", [
                    'page' => 1,
                    'page_size' => 200,
                    'embed' => 'units',
                ]);

            if ($response->failed()) {
                Log::warning('ownerrez.properties.fetch_failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return response()->json(['results' => [], 'pagination' => ['more' => false]], $response->status());
            }

            $data = $response->json() ?? [];
            $rawItems = $data['items'] ?? $data['data'] ?? $data;

            $search = trim((string) ($request->get('term') ?? $request->get('q') ?? ''));

            $items = collect(is_array($rawItems) ? $rawItems : [])
                ->filter(fn ($item) => isset($item['id']))
                ->when($search !== '', function ($collection) use ($search) {
                    $needle = strtolower($search);

                    return $collection->filter(function ($item) use ($needle) {
                        $name = strtolower((string) ($item['name'] ?? ''));
                        $id = (string) ($item['id'] ?? '');

                        return str_contains($name, $needle) || str_contains($id, $needle);
                    });
                })
                ->map(fn ($item) => [
                    'id' => (string) $item['id'],
                    'text' => trim(($item['name'] ?? 'Property').' (#'.($item['id'] ?? '').')'),
                ])
                ->values()
                ->all();

            Log::info('ownerrez.properties.fetch_ok', ['count' => count($items)]);

            return response()->json([
                'results' => $items,
                'pagination' => ['more' => false],
            ]);
        } catch (\Throwable $e) {
            Log::error('ownerrez.properties.fetch_exception', ['error' => $e->getMessage()]);

            return response()->json(['results' => [], 'pagination' => ['more' => false]], 500);
        }
    }
}
