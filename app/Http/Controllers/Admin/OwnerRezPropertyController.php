<?php

namespace App\Http\Controllers\Admin;

use App\Services\OwnerRez\OwnerRezApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OwnerRezPropertyController
{
    /**
     * Get all OwnerRez properties as options array (for select2_from_array)
     */
    public static function options(): array
    {
        try {
            $items = app(OwnerRezApiService::class)
                ->withoutLogging()
                ->getAllProperties();

            $options = collect($items)
                ->filter(fn ($item) => isset($item['id']))
                ->mapWithKeys(fn ($item) => [
                    (string) $item['id'] => trim(($item['name'] ?? 'Property').' (#'.$item['id'].')'),
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
