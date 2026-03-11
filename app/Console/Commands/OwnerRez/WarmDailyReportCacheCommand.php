<?php

namespace App\Console\Commands\OwnerRez;

use App\Models\OwnerRezPropertyMapping;
use App\Services\OwnerRez\OwnerRezApiService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class WarmDailyReportCacheCommand extends Command
{
    protected $signature = 'ownerrez:warm-daily-report';

    protected $description = 'Pre-warm OwnerRez daily checkin/checkout report cache';

    public function handle(): int
    {
        $today = Carbon::today()->toDateString();

        $propertyIdsArray = OwnerRezPropertyMapping::pluck('ownerrez_property_id')
            ->filter()
            ->values()
            ->all();

        if (empty($propertyIdsArray)) {
            $this->warn('No OwnerRez properties found.');

            return self::SUCCESS;
        }

        $apiService = new OwnerRezApiService;
        $apiService->withoutLogging()->withTimeout(60);

        $propertyNameMap = Cache::remember('ownerrez_property_name_map', now()->addHours(1), function () {
            return OwnerRezPropertyMapping::with('apartment')
                ->get()
                ->mapWithKeys(fn ($m) => [
                    (string) $m->ownerrez_property_id => $m->apartment?->name_ar,
                ])
                ->filter()
                ->all();
        });

        // طلب مُجمَّع يخدم كلاً من تقرير الخروج والدخول
        $this->warmBookingsCache($apiService, $today, $propertyIdsArray, $propertyNameMap);

        // طلب مُجمَّع يخدم كلاً من صيانة الخروج والدخول
        $this->warmMaintenanceCache($apiService, $today, $propertyIdsArray, $propertyNameMap);

        return self::SUCCESS;
    }

    /**
     * @param  array<int>  $propertyIdsArray
     * @param  array<string, string>  $propertyNameMap
     */
    private function warmBookingsCache(OwnerRezApiService $apiService, string $today, array $propertyIdsArray, array $propertyNameMap): void
    {
        try {
            $items = collect($apiService->getBookingsBatched($propertyIdsArray, [
                'from' => $today,
                'to' => $today,
                'include_guest' => 'true',
                'limit' => 100,
            ]));

            $checkoutBookings = $items->filter(fn ($b) => ($b['departure'] ?? '') === $today && ($b['type'] ?? '') !== 'block')->values();
            $checkinBookings = $items->filter(fn ($b) => ($b['arrival'] ?? '') === $today && ($b['type'] ?? '') !== 'block')->values();

            $baseData = [
                'success' => true,
                'has_more' => false,
                'next_offset' => null,
                'date' => $today,
                'property_name_map' => $propertyNameMap,
            ];

            Cache::put(
                "ownerrez_checkout_{$today}_offset_0_b_all",
                array_merge($baseData, ['bookings' => $checkoutBookings]),
                now()->addMinutes(20)
            );

            Cache::put(
                "ownerrez_checkin_{$today}_offset_0_b_all",
                array_merge($baseData, ['bookings' => $checkinBookings]),
                now()->addMinutes(20)
            );

            $this->line("  ✓ bookings: {$checkoutBookings->count()} checkout / {$checkinBookings->count()} checkin");
        } catch (\Exception $e) {
            $this->warn('  ~ bookings API failed, old cache kept: '.$e->getMessage());
        }
    }

    /**
     * @param  array<int>  $propertyIdsArray
     * @param  array<string, string>  $propertyNameMap
     */
    private function warmMaintenanceCache(OwnerRezApiService $apiService, string $today, array $propertyIdsArray, array $propertyNameMap): void
    {
        try {
            $items = collect($apiService->getBookingsBatched($propertyIdsArray, [
                'from' => $today,
                'to' => $today,
                'limit' => 100,
            ]));

            $checkoutBlocks = $items->filter(fn ($b) => ($b['departure'] ?? '') === $today && ($b['type'] ?? '') === 'block')->values();
            $checkinBlocks = $items->filter(fn ($b) => ($b['arrival'] ?? '') === $today && ($b['type'] ?? '') === 'block')->values();

            $baseData = [
                'success' => true,
                'has_more' => false,
                'next_offset' => null,
                'date' => $today,
                'property_name_map' => $propertyNameMap,
            ];

            Cache::put(
                "ownerrez_maintenance_{$today}_offset_0",
                array_merge($baseData, ['blocks' => $checkoutBlocks]),
                now()->addMinutes(20)
            );

            Cache::put(
                "ownerrez_maintenance_checkin_{$today}_offset_0",
                array_merge($baseData, ['blocks' => $checkinBlocks]),
                now()->addMinutes(20)
            );

            $this->line("  ✓ maintenance: {$checkoutBlocks->count()} checkout / {$checkinBlocks->count()} checkin");
        } catch (\Exception $e) {
            $this->warn('  ~ maintenance API failed, old cache kept: '.$e->getMessage());
        }
    }
}
