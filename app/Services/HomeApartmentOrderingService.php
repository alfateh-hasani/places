<?php

namespace App\Services;

use App\Models\Apartment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class HomeApartmentOrderingService
{
    public function paginateDiversified(int $perPage = 8, string $pageName = 'page', ?int $page = null): LengthAwarePaginator
    {
        try {
            return $this->paginateWithDatabaseOrdering($perPage, $pageName, $page);
        } catch (QueryException $exception) {
            if (! $this->isWindowFunctionCompatibilityError($exception)) {
                throw $exception;
            }
        }

        return $this->paginateWithInMemoryOrdering($perPage, $pageName, $page);
    }

    public function interleaveApartmentIdsByBuilding(Collection $apartments): Collection
    {
        $apartmentIdsByBuilding = $apartments
            ->groupBy(fn (mixed $apartment): mixed => data_get($apartment, 'building_id'))
            ->map(fn (Collection $buildingApartments): array => $buildingApartments->pluck('id')->all())
            ->values()
            ->all();

        $interleavedApartmentIds = [];

        while ($apartmentIdsByBuilding !== []) {
            foreach ($apartmentIdsByBuilding as $index => &$buildingApartmentIds) {
                $nextApartmentId = array_shift($buildingApartmentIds);

                if ($nextApartmentId !== null) {
                    $interleavedApartmentIds[] = $nextApartmentId;
                }

                if ($buildingApartmentIds === []) {
                    unset($apartmentIdsByBuilding[$index]);
                }
            }

            unset($buildingApartmentIds);

            $apartmentIdsByBuilding = array_values($apartmentIdsByBuilding);
        }

        return collect($interleavedApartmentIds);
    }

    private function paginateWithDatabaseOrdering(int $perPage, string $pageName, ?int $page): LengthAwarePaginator
    {
        return $this->buildDiversifiedQuery()
            ->with('reviews')
            ->paginate($perPage, ['apartments.*'], $pageName, $page);
    }

    private function paginateWithInMemoryOrdering(int $perPage, string $pageName, ?int $page): LengthAwarePaginator
    {
        $orderedApartmentIds = $this->interleaveApartmentIdsByBuilding(
            Apartment::query()
                ->where('is_active', true)
                ->orderByDesc('id')
                ->get(['id', 'building_id'])
        );

        $currentPage = $page ?? Paginator::resolveCurrentPage($pageName);
        $pageApartmentIds = $orderedApartmentIds
            ->slice(($currentPage - 1) * $perPage, $perPage)
            ->values();

        $apartments = $this->getApartmentsForPage($pageApartmentIds);

        return new LengthAwarePaginator(
            $apartments,
            $orderedApartmentIds->count(),
            $perPage,
            $currentPage,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ]
        );
    }

    private function buildDiversifiedQuery(): Builder
    {
        $rankedApartments = Apartment::query()
            ->select('apartments.*')
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY building_id ORDER BY id DESC) as building_position')
            ->selectRaw('MAX(id) OVER (PARTITION BY building_id) as building_priority')
            ->where('is_active', true);

        return Apartment::query()
            ->fromSub($rankedApartments, 'apartments')
            ->orderBy('building_position')
            ->orderByDesc('building_priority')
            ->orderByDesc('id');
    }

    private function getApartmentsForPage(Collection $pageApartmentIds): Collection
    {
        if ($pageApartmentIds->isEmpty()) {
            return collect();
        }

        $positionsByApartmentId = array_flip($pageApartmentIds->all());

        return Apartment::query()
            ->with('reviews')
            ->whereIn('id', $pageApartmentIds->all())
            ->get()
            ->sortBy(fn (Apartment $apartment): int => $positionsByApartmentId[$apartment->id] ?? PHP_INT_MAX)
            ->values();
    }

    private function isWindowFunctionCompatibilityError(QueryException $exception): bool
    {
        $errorMessage = strtolower($exception->getMessage());

        return str_contains($errorMessage, 'row_number')
            || str_contains($errorMessage, 'window function')
            || str_contains($errorMessage, 'over (partition by')
            || str_contains($errorMessage, 'over(partition by');
    }
}
