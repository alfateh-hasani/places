<?php

namespace App\Services;

use App\Models\Apartment;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class HomeApartmentOrderingService
{
    public function paginateDiversified(int $perPage = 8, string $pageName = 'page', ?int $page = null): LengthAwarePaginator
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
}
