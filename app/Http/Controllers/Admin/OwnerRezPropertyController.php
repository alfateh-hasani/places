<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OwnerRezPropertyController
{
    /**
     * Get all OwnerRez properties as options array (for select2_from_array)
     */
    public static function options(): array
    {
        $user = (string) config('services.ownerrez.webhook_user');
        $password = (string) config('services.ownerrez.webhook_password');
        $baseUrl = rtrim((string) config('services.ownerrez.api_url', 'https://api.ownerrez.com'), '/');

        if ($user === '' || $password === '') {
            Log::warning('ownerrez.properties.no_credentials');

            return [];
        }

        try {
            $allItems = [];
            $limit = 100;
            $offset = 0;
            $maxPages = 20;
            $page = 0;

            do {
                $page++;
                $url = $baseUrl.'/v2/properties';

                $response = Http::withBasicAuth($user, $password)
                    ->acceptJson()
                    ->get($url, [
                        'limit' => $limit,
                        'offset' => $offset,
                    ]);

                if ($response->failed()) {
                    Log::warning('ownerrez.properties.fetch_failed', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    break;
                }

                $data = $response->json() ?? [];
                $pageItems = $data['items'] ?? [];
                $pageCount = count($pageItems);

                if ($pageCount === 0) {
                    break;
                }

                $allItems = array_merge($allItems, $pageItems);
                $offset += $pageCount;

                if ($pageCount < $limit) {
                    break;
                }

            } while ($page < $maxPages);

            $options = collect($allItems)
                ->filter(fn ($item) => isset($item['id']))
                ->mapWithKeys(fn ($item) => [
                    (string) $item['id'] => trim(($item['name'] ?? 'Property').' (#'.($item['id'] ?? '').')'),
                ])
                ->all();

            Log::info('ownerrez.properties.fetch_ok', ['total_count' => count($options)]);

            return $options;
        } catch (\Throwable $e) {
            Log::error('ownerrez.properties.fetch_exception', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * AJAX endpoint for select2
     */
    public function index(Request $request): JsonResponse
    {
        $options = self::options();

        $search = trim((string) ($request->get('term') ?? $request->get('q') ?? ''));

        $results = collect($options)
            ->when($search !== '', function ($collection) use ($search) {
                $needle = strtolower($search);

                return $collection->filter(function ($text, $id) use ($needle) {
                    return str_contains(strtolower($text), $needle) || str_contains((string) $id, $needle);
                });
            })
            ->map(fn ($text, $id) => [
                'id' => (string) $id,
                'text' => $text,
            ])
            ->values()
            ->all();

        return response()->json([
            'results' => $results,
            'pagination' => ['more' => false],
        ]);
    }
}
