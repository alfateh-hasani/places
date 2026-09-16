<?php

declare(strict_types=1);

namespace App\Services\OwnerRez;

use App\Exceptions\OwnerRez\OwnerRezApiException;
use App\Models\OwnerRezParkedBooking;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Single source of truth for "parking" OwnerRez bookings that cannot be deleted via the API
 * (v2 has no delete endpoint). Parking PATCH-moves a booking to a dead 1-day slot in the far
 * past, which frees the real future nights on the live calendar, and records it in
 * {@see OwnerRezParkedBooking} for the tech team to delete manually in the OwnerRez UI.
 *
 * OwnerRez accepts past dates; it only rejects a move whose target dates collide with another
 * booking ON THE SAME PROPERTY, so the search skips dates already used by other parked blocks
 * on that property and lets OwnerRez be the final arbiter (a rejected move → next earlier day).
 */
class OwnerRezBlockParkingService
{
    /**
     * The first (newest) date a released booking is parked at; the search walks one day
     * EARLIER at a time from here (2018-01-01, 2017-12-31, …) — into the infinite past, never
     * toward live dates.
     *
     * NOTE: this project uses 2018-01-01 deliberately. The sibling contracts project parks
     * backward from 2019-01-01, so both-backward with different base years keeps ~a full year
     * of separation even on a shared OwnerRez account.
     */
    public const PARK_BASE_DATE = '2018-01-01';

    /** How far back the sequential search may walk before giving up (~100 years). */
    private const PARK_SPAN_DAYS = 36500;

    /** Real OwnerRez move attempts before giving up (skipped known-taken dates don't count). */
    private const PARK_MAX_ATTEMPTS = 8;

    public function __construct(private readonly OwnerRezApiService $api) {}

    /**
     * Park a booking and register it. Idempotent: a booking already in the registry is treated
     * as parked and reported success without another move.
     *
     * @return bool true when parked (or already was); false when no free slot was found.
     */
    public function park(
        string $ownerrezBookingId,
        ?string $propertyId,
        string $sourceType,
        ?int $sourceId,
        string $reason,
    ): bool {
        if (OwnerRezParkedBooking::query()->where('ownerrez_booking_id', $ownerrezBookingId)->exists()) {
            return true;
        }

        $parked = $this->moveToFreePastSlot($ownerrezBookingId, $propertyId);

        if ($parked === null) {
            return false;
        }

        OwnerRezParkedBooking::query()->updateOrCreate(
            ['ownerrez_booking_id' => $ownerrezBookingId],
            [
                'ownerrez_property_id' => $propertyId ?: null,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'reason' => $reason,
                'parked_arrival' => $parked[0],
                'parked_departure' => $parked[1],
            ],
        );

        return true;
    }

    /**
     * Fetch a booking by id for the purge command.
     *
     * @return array<string, mixed>|false|null  array = still present, false = 404 (deleted,
     *                                           safe to purge), null = disabled / API error.
     */
    public function fetchBooking(string $ownerrezBookingId): array|false|null
    {
        if (! config('ownerrez.enabled', true) && ! config('services.ownerrez.enabled', true)) {
            return null;
        }

        try {
            return $this->api->getBooking((int) $ownerrezBookingId);
        } catch (OwnerRezApiException $e) {
            if ($e->getStatusCode() === 404) {
                return false; // deleted in OwnerRez → safe to purge locally
            }

            Log::warning('OwnerRez fetchBooking (purge) failed.', [
                'ownerrez_booking_id' => $ownerrezBookingId,
                'status' => $e->getStatusCode(),
            ]);

            return null; // uncertain → never purge
        } catch (\Throwable $e) {
            Log::warning('OwnerRez fetchBooking (purge) failed.', [
                'ownerrez_booking_id' => $ownerrezBookingId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Move a booking to the first free 1-day slot at or before PARK_BASE_DATE, searched
     * sequentially backward. Skips dates already taken by other parked bookings on the same
     * property (per-property conflicts) so we don't waste API calls; OwnerRez is the final
     * arbiter — a rejected move just advances to the next earlier slot.
     *
     * @return array{0: string, 1: string}|null  [arrival, departure] on success
     */
    private function moveToFreePastSlot(string $ownerrezBookingId, ?string $propertyId): ?array
    {
        $base = Carbon::parse(self::PARK_BASE_DATE);

        $usedArrivals = [];

        if ($propertyId !== null && $propertyId !== '') {
            $usedArrivals = OwnerRezParkedBooking::query()
                ->where('ownerrez_property_id', $propertyId)
                ->where('ownerrez_booking_id', '!=', $ownerrezBookingId)
                ->pluck('parked_arrival')
                ->filter()
                ->map(fn ($date): string => Carbon::parse($date)->toDateString())
                ->flip()
                ->all();
        }

        $apiAttempts = 0;

        for ($step = 0; $step < self::PARK_SPAN_DAYS && $apiAttempts < self::PARK_MAX_ATTEMPTS; $step++) {
            $arrival = $base->copy()->subDays($step);
            $arrivalString = $arrival->toDateString();

            if (isset($usedArrivals[$arrivalString])) {
                continue;
            }

            $apiAttempts++;
            $departure = $arrival->copy()->addDay();

            if ($this->attemptMove($ownerrezBookingId, $arrivalString, $departure->toDateString())) {
                return [$arrivalString, $departure->toDateString()];
            }
        }

        return null;
    }

    /** One PATCH move; a rejection (e.g. date collision) returns false so the caller tries the next slot. */
    private function attemptMove(string $ownerrezBookingId, string $arrival, string $departure): bool
    {
        try {
            $this->api->updateBooking((int) $ownerrezBookingId, [
                'arrival' => $arrival,
                'departure' => $departure,
            ]);

            return true;
        } catch (OwnerRezApiException $e) {
            $body = $e->getResponseData();
            $reason = data_get($body, 'message') ?? data_get($body, 'Message') ?? data_get($body, 'error')
                ?? (! empty($body) ? json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : $e->getMessage());

            Log::warning('OwnerRez park move rejected — trying an earlier slot.', [
                'ownerrez_booking_id' => $ownerrezBookingId,
                'arrival' => $arrival,
                'status' => $e->getStatusCode(),
                'reason' => \Illuminate\Support\Str::limit((string) $reason, 300),
            ]);

            return false;
        }
    }
}
