<?php

namespace App\Services\OwnerRez;

use App\Enums\CustomerSource;
use App\Exceptions\OwnerRez\BookingConflictException;
use App\Exceptions\OwnerRez\OwnerRezApiException;
use App\Models\Apartment;
use App\Models\Booking;
use App\Models\BookingChannelConflict;
use App\Models\Customer;
use App\Models\OwnerRezBooking;
use App\Models\OwnerRezPropertyMapping;
use App\Models\User;
use App\Mail\BlockedCustomerBookingSynced;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OwnerRezSyncService
{
    public function __construct(
        protected OwnerRezApiService $apiService
    ) {}

    /**
     * Check availability for a property in OwnerRez (active bookings and blocks)
     */
    /**
     * @param  int|string|null  $excludeOwnerRezBookingId  Ignore this OwnerRez reservation
     *                                                      (used when re-checking a booking's own new range).
     * @param  bool  $liveCheck  Skip the 5-minute cache and query OwnerRez fresh (single attempt,
     *                           no retry — fails fast rather than extending how long the apartment
     *                           lock is held). Reserved for the authoritative check at the moment a
     *                           booking is actually committed (BookingService::reserveApartment).
     *                           Browsing/quote checks should leave this false and use the cache.
     */
    public function checkAvailability(int|string $propertyId, string $from, string $to, int|string|null $excludeOwnerRezBookingId = null, bool $liveCheck = false): bool
    {
        try {
            $bookings = $this->getActiveBookings($propertyId, $from, $to, $excludeOwnerRezBookingId, $liveCheck);

            return $bookings->isEmpty();
        } catch (OwnerRezApiException $e) {
            Log::error('OwnerRez availability check failed', [
                'property_id' => $propertyId,
                'from' => $from,
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            // If strict mode is enabled, throw exception
            if (config('ownerrez.availability.strict_mode')) {
                throw $e;
            }

            // Otherwise, assume available (fail open)
            return true;
        }
    }

    /**
     * Get calendar bookings from permanent cache (stale-while-revalidate).
     * Returns stale data if available; only populates cache on first miss.
     */
    public function getCalendarBookings(int|string $propertyId): Collection
    {
        $cacheKey = "ownerrez:calendar:v1:{$propertyId}";
        $cached = Cache::get($cacheKey);

        if ($cached === null) {
            $this->refreshCalendarCache($propertyId);
            $cached = Cache::get($cacheKey, []);
        }

        return collect($cached)->filter(
            fn ($booking) => Carbon::parse($booking['departure'])->gte(now()->startOfDay())
        )->values();
    }

    /**
     * Refresh calendar cache from OwnerRez API.
     * Fetches active bookings and calendar blocks (is_block=true) in one request.
     * Only updates the cache on success — never clears old data on failure.
     */
    public function refreshCalendarCache(int|string $propertyId): bool
    {
        $from = now()->format('Y-m-d');
        $to = now()->addYear()->format('Y-m-d');
        $cacheKey = "ownerrez:calendar:v1:{$propertyId}";

        try {
            $response = $this->apiService->getBookings([
                'property_ids' => $propertyId,
                'from' => $from,
                'to' => $to,
                'include_guest' => 'true',
            ]);

            // Include active bookings and calendar blocks (is_block=true) from the same endpoint
            $bookings = collect($response['items'] ?? [])->filter(function ($item) {
                return ! empty($item['is_block']) || strtolower($item['status'] ?? '') === 'active';
            })->values();

            Cache::forever($cacheKey, $bookings->toArray());

            Log::info('OwnerRez calendar cache refreshed', [
                'property_id' => $propertyId,
                'count' => $bookings->count(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::warning('OwnerRez calendar cache refresh failed, keeping old cache', [
                'property_id' => $propertyId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get active bookings and blocks for a property.
     * Fetches all entries without status filter so calendar blocks (is_block=true)
     * are included alongside active bookings.
     */
    /**
     * @param  int|string|null  $excludeOwnerRezBookingId  Ignore this OwnerRez reservation
     *                                                      (filtered post-cache so the cache stays shared).
     * @param  bool  $liveCheck  Bypass the cache and fetch fresh from OwnerRez, then refresh the
     *                           cache with the result so other/subsequent cached readers benefit
     *                           too. No retry on failure — this runs while the apartment row lock
     *                           is held, so a slow/failing OwnerRez must fail fast rather than
     *                           stack extra timeouts onto the lock (and onto anyone else waiting
     *                           on the same apartment).
     */
    public function getActiveBookings(int|string $propertyId, string $from, string $to, int|string|null $excludeOwnerRezBookingId = null, bool $liveCheck = false): Collection
    {
        $cacheKey = "ownerrez:availability:v3:{$propertyId}:{$from}:{$to}";
        $cacheTtl = config('ownerrez.availability.cache_ttl', 300);

        $fetch = function () use ($propertyId, $from, $to) {
            $response = $this->apiService->getBookings([
                'property_ids' => $propertyId,
                'from' => $from,
                'to' => $to,
                'include_guest' => 'true',
            ]);

            // Include active bookings and calendar blocks (is_block=true)
            return collect($response['items'] ?? [])->filter(function ($item) {
                return ! empty($item['is_block']) || strtolower($item['status'] ?? '') === 'active';
            })->values();
        };

        if ($liveCheck) {
            $bookings = $fetch();
            Cache::put($cacheKey, $bookings->toArray(), $cacheTtl);
        } else {
            $bookings = Cache::remember($cacheKey, $cacheTtl, $fetch);
        }

        // The liveCheck branch caches an array (->toArray()), so a subsequent non-liveCheck
        // read of the same key returns a plain array. Normalize to a Collection before filtering.
        $bookings = collect($bookings);

        // Filter entries that overlap with requested dates, excluding the booking's own reservation.
        return $bookings->filter(function ($booking) use ($from, $to, $excludeOwnerRezBookingId) {
            if ($excludeOwnerRezBookingId !== null && (string) ($booking['id'] ?? '') === (string) $excludeOwnerRezBookingId) {
                return false;
            }

            return $this->datesOverlap(
                $from,
                $to,
                $booking['arrival'],
                $booking['departure']
            );
        });
    }

    /**
     * Check if two date ranges overlap
     */
    protected function datesOverlap(string $start1, string $end1, string $start2, string $end2): bool
    {
        $start1 = Carbon::parse($start1);
        $end1 = Carbon::parse($end1);
        $start2 = Carbon::parse($start2);
        $end2 = Carbon::parse($end2);

        return $start1->lt($end2) && $end1->gt($start2);
    }

    /**
     * Sync booking from webhook data
     */
    public function syncBookingFromWebhook(array $webhookData): void
    {
        $action = $webhookData['action'] ?? null;
        $bookingData = $webhookData['data'] ?? [];

        // `entity_id` is the authoritative OwnerRez booking/block id for EVERY action.
        // On entity_delete the entity body is empty ({}) and `data` instead carries the
        // webhook EVENT id in `id` (the wrong value), so we must always trust `entity_id`
        // to resolve the local booking via the ownerrez_bookings link. For create/update
        // entity_id equals entity.id, so this override is a no-op there.
        if (! empty($webhookData['entity_id'])) {
            $bookingData['id'] = $webhookData['entity_id'];
        }

        Log::channel('ownerrez_webhook')->info('OwnerRez webhook processing started', [
            'action' => $action,
            'entity_id' => $webhookData['entity_id'] ?? null,
            'categories' => $webhookData['categories'] ?? [],
            'is_block' => $bookingData['is_block'] ?? null,
            'status' => $bookingData['status'] ?? null,
            'property_id' => $bookingData['property_id'] ?? null,
        ]);

        match ($action) {
            'entity_create' => $this->handleBookingCreated($bookingData),
            'entity_update' => $this->handleBookingUpdated($bookingData),
            'entity_delete' => $this->handleBookingDeleted($bookingData),
            default => Log::channel('ownerrez_webhook')->warning('Unknown webhook action', ['action' => $action]),
        };

        // Invalidate cache for this property
        if (isset($bookingData['property_id'])) {
            $this->invalidatePropertyCache($bookingData['property_id']);
        }
    }

    /**
     * Handle booking created from webhook
     */
    protected function handleBookingCreated(array $bookingData): void
    {
        $logger = Log::channel('ownerrez_webhook');
        $ownerrezBookingId = $bookingData['id'] ?? null;
        $propertyId = $bookingData['property_id'] ?? null;

        $logger->info('OwnerRez booking storage started', [
            'ownerrez_booking_id' => $ownerrezBookingId,
            'property_id' => $propertyId,
            'arrival' => $bookingData['arrival'] ?? null,
            'departure' => $bookingData['departure'] ?? null,
        ]);

        if (! $ownerrezBookingId || ! $propertyId) {
            $logger->warning('OwnerRez booking storage skipped', [
                'reason' => 'missing_booking_id_or_property_id',
                'ownerrez_booking_id' => $ownerrezBookingId,
                'property_id' => $propertyId,
            ]);
            Log::warning('Invalid booking data from webhook', $bookingData);

            return;
        }

        // Skip blocks - they are not real bookings
        if (! empty($bookingData['is_block'])) {
            $logger->info('OwnerRez booking storage skipped', [
                'reason' => 'is_block',
                'ownerrez_booking_id' => $ownerrezBookingId,
                'property_id' => $propertyId,
            ]);

            return;
        }

        // Handle bookings that arrive already canceled
        if (($bookingData['status'] ?? null) === 'canceled') {
            $existingOwnerrezBooking = OwnerRezBooking::where('ownerrez_booking_id', $ownerrezBookingId)->first();
            if ($existingOwnerrezBooking?->localBooking) {
                $this->cancelLocalBookingFromOwnerRez($existingOwnerrezBooking->localBooking);
                $logger->info('OwnerRez booking canceled on entity_create', [
                    'ownerrez_booking_id' => $ownerrezBookingId,
                    'local_booking_id' => $existingOwnerrezBooking->local_booking_id,
                ]);
            } else {
                $logger->info('OwnerRez booking creation skipped - arrived already canceled and no local record', [
                    'ownerrez_booking_id' => $ownerrezBookingId,
                ]);
            }

            return;
        }

        // Find apartment mapping
        $mapping = OwnerRezPropertyMapping::where('ownerrez_property_id', $propertyId)->first();
        if (! $mapping || ! $mapping->sync_enabled) {
            $logger->warning('OwnerRez booking storage skipped', [
                'reason' => 'property_not_mapped_or_sync_disabled',
                'ownerrez_booking_id' => $ownerrezBookingId,
                'property_id' => $propertyId,
                'mapping_found' => (bool) $mapping,
                'sync_enabled' => $mapping?->sync_enabled,
            ]);
            Log::info('Property not mapped or sync disabled', ['property_id' => $propertyId]);

            return;
        }

        // Check if booking already exists
        $existingOwnerrezBooking = OwnerRezBooking::where('ownerrez_booking_id', $ownerrezBookingId)->first();
        if ($existingOwnerrezBooking) {
            $logger->info('OwnerRez booking storage skipped', [
                'reason' => 'booking_already_exists',
                'ownerrez_booking_id' => $ownerrezBookingId,
                'local_booking_id' => $existingOwnerrezBooking->local_booking_id,
                'ownerrez_bookings_row_id' => $existingOwnerrezBooking->id,
            ]);
            Log::info('OwnerRez booking already exists', ['ownerrez_booking_id' => $ownerrezBookingId]);

            return;
        }

        // Fetch full booking data from API before creating
        try {
            $fullBookingData = $this->apiService->getBooking($ownerrezBookingId);
            Log::info('Full booking data from API', ['full_booking_data' => $fullBookingData]);
            if (! empty($fullBookingData)) {
                // Merge API data with webhook data (API takes precedence)
                $bookingData = $fullBookingData;
                Log::info('F full booking data from API', [
                    'booking_id' => $ownerrezBookingId,
                    'booking_data' => $bookingData,
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch full booking before creation, using webhook data', [
                'booking_id' => $ownerrezBookingId,
                'error' => $e->getMessage(),
            ]);
        }

        // Create local booking
        $storageStage = 'transaction_not_started';
        try {
            DB::beginTransaction();
            $storageStage = 'transaction_started';

            $localBooking = $this->createLocalBookingFromOwnerRez($bookingData, $mapping->apartment_id);
            $storageStage = 'local_booking_created';

            // Create OwnerRez booking record with full data
            OwnerRezBooking::create([
                'ownerrez_booking_id' => $ownerrezBookingId,
                'local_booking_id' => $localBooking->id,
                'apartment_id' => $mapping->apartment_id,
                'raw_data' => $bookingData,
                'sync_status' => 'synced',
                'sync_direction' => 'inbound',
                'synced_at' => now(),
            ]);
            $storageStage = 'ownerrez_booking_link_created';

            DB::commit();
            $storageStage = 'transaction_committed';

            $logger->info('OwnerRez booking storage success', [
                'ownerrez_booking_id' => $ownerrezBookingId,
                'property_id' => $propertyId,
                'apartment_id' => $mapping->apartment_id,
                'local_booking_id' => $localBooking->id,
                'stage' => $storageStage,
            ]);
            Log::info('Successfully created booking from OwnerRez', [
                'ownerrez_booking_id' => $ownerrezBookingId,
                'local_booking_id' => $localBooking->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            $logger->error('OwnerRez booking storage failed', [
                'ownerrez_booking_id' => $ownerrezBookingId,
                'property_id' => $propertyId,
                'apartment_id' => $mapping->apartment_id,
                'arrival' => $bookingData['arrival'] ?? null,
                'departure' => $bookingData['departure'] ?? null,
                'failed_stage' => $storageStage,
                'exception_class' => get_class($e),
                'error' => $e->getMessage(),
            ]);
            Log::error('Failed to create booking from OwnerRez', [
                'ownerrez_booking_id' => $ownerrezBookingId,
                'error' => $e->getMessage(),
            ]);

            // Log conflict if it's a date conflict
            if (str_contains($e->getMessage(), 'available')) {
                $this->logConflict([
                    'apartment_id' => $mapping->apartment_id,
                    'channel' => 'ownerrez',
                    'external_reservation_id' => (string) $ownerrezBookingId,
                    'ext_check_in' => $bookingData['arrival'],
                    'ext_check_out' => $bookingData['departure'],
                    'context' => json_encode([
                        'conflict_type' => 'date_overlap',
                        'details' => $e->getMessage(),
                    ]),
                ]);
            }

            throw $e;
        }
    }

    /**
     * Handle booking updated from webhook
     */
    protected function handleBookingUpdated(array $bookingData): void
    {
        $logger = Log::channel('ownerrez_webhook');
        $ownerrezBookingId = $bookingData['id'] ?? null;

        if (! $ownerrezBookingId) {
            return;
        }

        $ownerrezBooking = OwnerRezBooking::where('ownerrez_booking_id', $ownerrezBookingId)->first();

        if (! $ownerrezBooking) {
            // Block-to-booking transition: booking was skipped as block during entity_create
            $isBlockToBookingTransition = empty($bookingData['is_block'])
                && ($bookingData['status'] ?? null) === 'active'
                && isset($bookingData['guest_id']);

            if ($isBlockToBookingTransition) {
                $logger->info('OwnerRez block-to-booking transition detected', [
                    'ownerrez_booking_id' => $ownerrezBookingId,
                    'property_id' => $bookingData['property_id'] ?? null,
                    'platform_reservation_number' => $bookingData['platform_reservation_number'] ?? null,
                ]);
                $this->handleBookingCreated($bookingData);
            } else {
                $logger->info('OwnerRez booking not found locally on update', [
                    'ownerrez_booking_id' => $ownerrezBookingId,
                    'is_block' => $bookingData['is_block'] ?? null,
                    'status' => $bookingData['status'] ?? null,
                ]);
            }

            return;
        }

        if (! $ownerrezBooking->localBooking) {
            $logger->warning('OwnerRez booking has no local booking linked', [
                'ownerrez_booking_id' => $ownerrezBookingId,
                'ownerrez_bookings_row_id' => $ownerrezBooking->id,
            ]);

            return;
        }

        try {
            $status = $bookingData['status'] ?? null;
            $isOutboundBooking = $ownerrezBooking->sync_direction === 'outbound';

            // Skip updates where data is still a block — can happen due to queue processing order
            if (! empty($bookingData['is_block'])) {
                $logger->info('OwnerRez booking update skipped - incoming data is still a block', [
                    'ownerrez_booking_id' => $ownerrezBookingId,
                    'local_booking_id' => $ownerrezBooking->local_booking_id,
                ]);
                $ownerrezBooking->update(['raw_data' => $bookingData]);

                return;
            }

            if ($status === 'canceled') {
                // Always process cancellations, even for locally-created bookings
                $this->cancelLocalBookingFromOwnerRez($ownerrezBooking->localBooking);
                $logger->info('OwnerRez booking canceled via update webhook', [
                    'ownerrez_booking_id' => $ownerrezBookingId,
                    'local_booking_id' => $ownerrezBooking->local_booking_id,
                    'sync_direction' => $ownerrezBooking->sync_direction,
                ]);
            } elseif ($isOutboundBooking) {
                // Skip data updates for bookings created in our system — OwnerRez should not overwrite local data
                $logger->info('OwnerRez booking update skipped - outbound booking protected from overwrite', [
                    'ownerrez_booking_id' => $ownerrezBookingId,
                    'local_booking_id' => $ownerrezBooking->local_booking_id,
                ]);
            } else {
                $this->updateLocalBookingFromOwnerRez($ownerrezBooking->localBooking, $bookingData);
                $logger->info('OwnerRez booking updated successfully', [
                    'ownerrez_booking_id' => $ownerrezBookingId,
                    'local_booking_id' => $ownerrezBooking->local_booking_id,
                    'arrival' => $bookingData['arrival'] ?? null,
                    'departure' => $bookingData['departure'] ?? null,
                ]);
            }

            // Always update raw_data so we have the latest OwnerRez state for reference
            $ownerrezBooking->update([
                'raw_data' => $bookingData,
                'sync_status' => 'synced',
                'synced_at' => now(),
            ]);
        } catch (\Exception $e) {
            $logger->error('OwnerRez booking update failed', [
                'ownerrez_booking_id' => $ownerrezBookingId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle booking deleted from webhook
     */
    protected function handleBookingDeleted(array $bookingData): void
    {
        $logger = Log::channel('ownerrez_webhook');
        $ownerrezBookingId = $bookingData['id'] ?? null;

        if (! $ownerrezBookingId) {
            return;
        }

        $ownerrezBooking = OwnerRezBooking::where('ownerrez_booking_id', $ownerrezBookingId)->first();
        if (! $ownerrezBooking || ! $ownerrezBooking->localBooking) {
            $logger->info('OwnerRez booking not found locally on delete', [
                'ownerrez_booking_id' => $ownerrezBookingId,
            ]);

            return;
        }

        try {
            $this->cancelLocalBookingFromOwnerRez($ownerrezBooking->localBooking);
            $logger->info('OwnerRez booking deleted and canceled locally', [
                'ownerrez_booking_id' => $ownerrezBookingId,
                'local_booking_id' => $ownerrezBooking->local_booking_id,
            ]);
        } catch (\Exception $e) {
            $logger->error('OwnerRez booking delete failed', [
                'ownerrez_booking_id' => $ownerrezBookingId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create local booking from OwnerRez data
     */
    public function createLocalBookingFromOwnerRez(array $ownerrezBooking, int $apartmentId): Booking
    {
        $apartment = Apartment::findOrFail($apartmentId);
        $localData = $this->mapOwnerRezToLocal($ownerrezBooking);

        // Check for conflicts
        $existingBooking = Booking::where('apartment_id', $apartmentId)
            ->where('status', '!=', 'canceled')
            ->where(function ($query) use ($localData) {
                $query->whereBetween('check_in', [$localData['check_in'], $localData['check_out']])
                    ->orWhereBetween('check_out', [$localData['check_in'], $localData['check_out']])
                    ->orWhere(function ($q) use ($localData) {
                        $q->where('check_in', '<=', $localData['check_in'])
                            ->where('check_out', '>=', $localData['check_out']);
                    });
            })
            ->exists();

        if ($existingBooking) {
            throw new BookingConflictException(
                'Apartment not available for these dates',
                [
                    'check_in' => $localData['check_in'],
                    'check_out' => $localData['check_out'],
                    'source' => 'ownerrez',
                ]
            );
        }

        $booking = Booking::create($localData);

        $this->alertIfCustomerBlocked($booking);

        return $booking;
    }

    /**
     * عميل محظور عندنا قد يحجز عبر قناة خارجية (Airbnb/Booking.com) لا نتحكم بها — الحجز يُزامَن دائماً
     * بلا استثناء (OwnerRez يبقى مصدر الحقيقة لتوفر الوحدة)، وهذه مجرد رسالة مراجعة بشرية، بلا أي إجراء آلي.
     */
    private function alertIfCustomerBlocked(Booking $booking): void
    {
        $customer = $booking->customer;

        if (! $customer || ! $customer->isBlocked()) {
            return;
        }

        Log::warning('Inbound OwnerRez booking synced for a blocked customer', [
            'booking_id' => $booking->id,
            'customer_id' => $customer->id,
            'booking_source' => $booking->booking_source,
        ]);

        try {
            $recipients = User::query()
                ->whereHas('roles', fn ($q) => $q->whereIn('name', Config::array('mail.blocked_customer_booking_alert.roles')))
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->orderBy('id')
                ->get(['id', 'email']);

            $onlyIds = Config::array('mail.blocked_customer_booking_alert.only_user_ids');
            if (! empty($onlyIds)) {
                $recipients = $recipients->whereIn('id', $onlyIds);
            }

            if ($recipients->isEmpty()) {
                Log::warning('No blocked-customer-booking reviewers resolved — alert email skipped', [
                    'booking_id' => $booking->id,
                    'roles' => Config::array('mail.blocked_customer_booking_alert.roles'),
                ]);

                return;
            }

            foreach ($recipients as $recipient) {
                Mail::to($recipient->email)->send(new BlockedCustomerBookingSynced($booking, $customer));
            }
        } catch (\Throwable $e) {
            Log::error('Failed to notify reviewers of inbound booking for blocked customer', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update local booking from OwnerRez data
     */
    public function updateLocalBookingFromOwnerRez(Booking $booking, array $ownerrezBooking): void
    {
        $localData = $this->mapOwnerRezToLocal($ownerrezBooking);

        // Never overwrite pricing fields with OwnerRez data — prices are managed locally
        unset(
            $localData['total_price'],
            $localData['final_price'],
            $localData['one_night_price'],
            $localData['tax'],
            $localData['discount'],
        );

        $booking->update($localData);
    }

    /**
     * Cancel local booking from OwnerRez
     */
    public function cancelLocalBookingFromOwnerRez(Booking $booking): void
    {
        // إلغاء محلي فقط (يحرّر الوحدة). الاسترداد أصبح خطوة منفصلة يُنفّذها الموظف يدوياً
        // من شاشة "الحجوزات الملغاة" بعد الإلغاء، مع تحديد المبلغ (كامل أو جزئي).
        // refund_status يبقى كما هو (pending لطلبات العملاء) لتظهر خطوة الاسترداد.
        $booking->update([
            'status' => 'canceled',
        ]);
    }

    /**
     * Send booking to OwnerRez
     */
    public function sendBookingToOwnerRez(Booking $booking): array
    {

        $mapping = $booking->apartment->ownerrezMapping;
        if (! $mapping || ! $mapping->sync_enabled) {
            throw new \Exception('Property not mapped to OwnerRez or sync disabled');
        }

        $ownerrezData = $this->mapLocalToOwnerRez($booking);

        // Create guest first if needed
        if (config('ownerrez.sync.sync_guest_data') && $booking->customer) {

            $guestId = $this->createOwnerRezGuest($booking->customer);
            $ownerrezData['guest_id'] = $guestId;

        }

        // Create booking in OwnerRez
        $response = $this->apiService->createBooking($ownerrezData);

        // Set custom field to identify this booking as from our platform
        $this->ensureBookingCustomField($response['id']);

        // Update local booking with OwnerRez ID and site
        $booking->update([
            'ownerrez_booking_id' => $response['id'],
            'site' => config('ownerrez.sync.custom_field_value', 'places'),
        ]);

        // Create OwnerRez booking record
        OwnerRezBooking::create([
            'ownerrez_booking_id' => $response['id'],
            'local_booking_id' => $booking->id,
            'apartment_id' => $booking->apartment_id,
            'raw_data' => $response,
            'sync_status' => 'synced',
            'sync_direction' => 'outbound',
            'synced_at' => now(),
        ]);

        // Invalidate cache
        $this->invalidatePropertyCache($mapping->ownerrez_property_id);

        return $response;
    }

    /**
     * Create or get guest in OwnerRez
     */
    public function createOwnerRezGuest(Customer $customer): int
    {

        // Check if customer already has OwnerRez guest ID
        if ($customer->ownerrez_guest_id) {
            Log::info('Customer already has OwnerRez guest ID', [
                'customer_id' => $customer->id,
                'ownerrez_guest_id' => $customer->ownerrez_guest_id,
            ]);

            return (int) $customer->ownerrez_guest_id;
        }

        // Search for existing guest in OwnerRez by email

        $existingGuest = $this->searchOwnerRezGuest($customer->email);

        if ($existingGuest) {
            Log::info('Found existing guest in OwnerRez', [
                'customer_id' => $customer->id,
                'ownerrez_guest_id' => $existingGuest['id'],
            ]);

            // Save the guest ID to customer
            $customer->update(['ownerrez_guest_id' => $existingGuest['id']]);

            return $existingGuest['id'];
        }

        // Create new guest in OwnerRez
        $guestData = [
            'first_name' => $customer->first_name ?? '',
            'last_name' => $customer->last_name ?? '',
            'email_addresses' => [
                [
                    'address' => $customer->email,
                    'is_default' => true,
                    'type' => 'home',
                ],
            ],
            'phones' => $customer->phone ? [
                [
                    'number' => $customer->phone,
                    'is_default' => true,
                    'type' => 'mobile',
                ],
            ] : [],
        ];

        $response = $this->apiService->createGuest($guestData);
        $guestId = $response['id'];

        // Save the guest ID to customer
        $customer->update(['ownerrez_guest_id' => $guestId]);

        Log::info('Created new guest in OwnerRez', [
            'customer_id' => $customer->id,
            'ownerrez_guest_id' => $guestId,
        ]);

        return $guestId;
    }

    /**
     * Search for guest in OwnerRez by email
     */
    public function searchOwnerRezGuest(string $email): ?array
    {
        try {
            $response = $this->apiService->searchGuests(['email' => $email]);

            if (isset($response['items']) && count($response['items']) > 0) {
                return $response['items'][0];
            }

            return null;
        } catch (\Exception $e) {
            Log::warning('Failed to search for guest in OwnerRez', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Update booking in OwnerRez
     */
    public function updateOwnerRezBooking(Booking $booking): void
    {
        if (! $booking->ownerrez_booking_id) {
            return;
        }

        $ownerrezData = $this->mapLocalToOwnerRez($booking);
        // The date PATCH is the critical sync — a failure here must bubble up so callers roll back.
        $this->apiService->updateBooking($booking->ownerrez_booking_id, $ownerrezData);

        // Invalidate this property's cached calendar/availability so freed days become available
        // immediately (the calendar is Cache::forever; otherwise it stays stale until cache:clear).
        $this->invalidatePropertyCache($ownerrezData['property_id']);

        // The custom field is secondary/best-effort: a failure here must NOT undo a successful
        // date sync (otherwise local rolls back while OwnerRez already holds the new dates).
        try {
            $this->ensureBookingCustomField((int) $booking->ownerrez_booking_id);
        } catch (\Throwable $e) {
            Log::warning('OwnerRez custom field update failed after date sync (non-fatal)', [
                'ownerrez_booking_id' => $booking->ownerrez_booking_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Detect conflict for a booking
     */
    public function detectConflict(Booking $booking): ?BookingChannelConflict
    {
        $mapping = $booking->apartment->ownerrezMapping;
        if (! $mapping) {
            return null;
        }

        $activeBookings = $this->getActiveBookings(
            $mapping->ownerrez_property_id,
            $booking->check_in->format('Y-m-d'),
            $booking->check_out->format('Y-m-d')
        );

        if ($activeBookings->isNotEmpty()) {
            $conflictingBooking = $activeBookings->first();

            return $this->logConflict([
                'apartment_id' => $booking->apartment_id,
                'conflicting_booking_id' => $booking->id,
                'channel' => 'ownerrez',
                'external_reservation_id' => (string) $conflictingBooking['id'],
                'ext_check_in' => $conflictingBooking['arrival'],
                'ext_check_out' => $conflictingBooking['departure'],
                'context' => json_encode([
                    'conflict_type' => 'date_overlap',
                    'details' => $conflictingBooking,
                ]),
            ]);
        }

        return null;
    }

    /**
     * Log a conflict
     */
    public function logConflict(array $conflictData): BookingChannelConflict
    {
        return BookingChannelConflict::create($conflictData);
    }

    /**
     * Map OwnerRez booking data to local format
     */
    protected function mapOwnerRezToLocal(array $data): array
    {
        // Handle both old and new webhook formats
        $totalAmount = $data['total_amount'] ?? $data['amount'] ?? 0;
        $totalPaid = $data['total_paid'] ?? $data['paid'] ?? 0;

        // Determine payment status
        $paymentStatus = 'pending';
        if ($totalAmount > 0 && $totalPaid >= $totalAmount) {
            $paymentStatus = 'paid';
        } elseif ($totalPaid > 0) {
            $paymentStatus = 'paid';
        }

        // If no amount info, assume paid for active bookings
        if ($totalAmount == 0 && ($data['status'] ?? '') === 'active') {
            $paymentStatus = 'paid';
        }

        // Calculate number of nights
        $checkIn = \Carbon\Carbon::parse($data['arrival']);
        $checkOut = \Carbon\Carbon::parse($data['departure']);
        $numberOfNights = $checkIn->diffInDays($checkOut);

        // Calculate one night price
        $oneNightPrice = $numberOfNights > 0 ? $totalAmount / $numberOfNights : 0;

        // Map OwnerRez property_id to local apartment_id
        $apartmentId = null;
        if (isset($data['property_id'])) {
            $mapping = OwnerRezPropertyMapping::where('ownerrez_property_id', $data['property_id'])->first();
            $apartmentId = $mapping?->apartment_id;
        }

        // Note: Full booking data should already be fetched in handleBookingCreated()
        // This method just maps the data to local format

        // Extract customer email from guest data
        $customerEmail = null;
        if (isset($data['guest']['email'])) {
            $customerEmail = $data['guest']['email'];
        } elseif (isset($data['guest']['email_addresses']) && is_array($data['guest']['email_addresses'])) {
            foreach ($data['guest']['email_addresses'] as $emailData) {
                if (isset($emailData['address'])) {
                    $customerEmail = $emailData['address'];
                    if ($emailData['is_default'] ?? false) {
                        break;
                    }
                }
            }
        }

        $mappedData = [
            'apartment_id' => $apartmentId ?? $data['apartment_id'] ?? null,
            'ownerrez_booking_id' => $data['id'] ?? null,
            'check_in' => $data['arrival'],
            'check_out' => $data['departure'],
            'number_of_nights' => $numberOfNights,
            'adults_count' => $data['adults'] ?? 1,
            'children_count' => $data['children'] ?? 0,
            'customer_full_name' => trim(($data['guest']['first_name'] ?? 'Guest').' '.($data['guest']['last_name'] ?? '')),
            'customer_email' => $customerEmail,
            'total_price' => $totalAmount,
            'final_price' => $totalAmount,
            'one_night_price' => $oneNightPrice,
            'tax' => 0, // OwnerRez doesn't provide tax separately
            'status' => $this->mapOwnerRezStatus($data['status'] ?? 'pending'),
            'payment_status' => $paymentStatus,
            'booking_source' => 'ownerrez',
            'channel_name' => $data['listing_site'] ?? 'ownerrez',
            'site' => $data['listing_site']
                ?? (collect($data['fields'] ?? [])->firstWhere('code', 'BXSOURCEDOMAIN')['value'] ?? null),
            'external_reference' => $data['platform_reservation_number'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_airbnb_booking' => 0,
        ];

        // Find or create customer - booking cannot be created without a customer
        $guestId = $data['guest_id'] ?? ($data['guest']['id'] ?? null);
        $guestData = $data['guest'] ?? null;

        $customer = $this->findOrCreateCustomerFromOwnerRez($guestId, $guestData);

        if (! $customer) {
            throw new \RuntimeException(
                "Cannot create booking: failed to find or create customer for OwnerRez guest_id {$guestId}"
            );
        }

        $mappedData['customer_id'] = $customer->id;

        return $mappedData;
    }

    /**
     * Find or create customer from OwnerRez guest data
     *
     * @param  int|null  $guestId  OwnerRez guest_id from booking data
     * @param  array|null  $guestData  Guest object from full booking data (if available)
     */
    public function findOrCreateCustomerFromOwnerRez(?int $guestId, ?array $guestData = null): ?Customer
    {
        $logger = Log::channel('ownerrez');

        if (! $guestId) {
            $logger->warning('No guest_id provided for customer lookup');

            return null;
        }

        // Step 1: Search customer by ownerrez_guest_id
        $customer = Customer::where('ownerrez_guest_id', $guestId)->first();
        if ($customer) {
            $logger->info('Found existing customer by ownerrez_guest_id', [
                'customer_id' => $customer->id,
                'ownerrez_guest_id' => $guestId,
            ]);

            return $customer;
        }

        // Step 2: Fetch full guest details from OwnerRez API
        $apiGuestData = null;
        try {
            $apiGuestData = $this->apiService->getGuest($guestId);
            $logger->info('Fetched guest data from OwnerRez API', [
                'ownerrez_guest_id' => $guestId,
                'guest_data' => $apiGuestData,
            ]);
        } catch (\Exception $e) {
            $logger->warning('Failed to fetch guest from OwnerRez API', [
                'ownerrez_guest_id' => $guestId,
                'error' => $e->getMessage(),
            ]);
        }

        // Merge API data with any existing guest data (API takes precedence)
        $mergedData = array_merge($guestData ?? [], $apiGuestData ?? []);

        if (empty($mergedData)) {
            $logger->error('No guest data available to create customer', [
                'ownerrez_guest_id' => $guestId,
            ]);

            return null;
        }

        $phone = $this->extractOwnerRezPhoneNumber($mergedData);

        if (! $phone) {
            $logger->error('No phone number available to create customer', [
                'ownerrez_guest_id' => $guestId,
            ]);

            return null;
        }

        // Step 3: Try to find by phone
        $customer = Customer::where('phone', $phone)->first();
        if ($customer) {
            $customer->update(['ownerrez_guest_id' => $guestId]);
            $logger->info('Found existing customer by phone, linked ownerrez_guest_id', [
                'customer_id' => $customer->id,
                'ownerrez_guest_id' => $guestId,
            ]);

            return $customer;
        }

        // Step 5: Create new customer
        try {
            $customer = Customer::create([
                'first_name' => $mergedData['first_name'] ?? '',
                'last_name' => $mergedData['last_name'] ?? '',
                'email' => null,
                'phone' => $phone,
                'ownerrez_guest_id' => $guestId,
                'account_verified' => false,
                'source' => CustomerSource::OwnerRez,
            ]);

            $logger->info('Created new customer from OwnerRez guest', [
                'customer_id' => $customer->id,
                'ownerrez_guest_id' => $guestId,
            ]);

            return $customer;
        } catch (\Exception $e) {
            $logger->error('Failed to create customer from OwnerRez guest', [
                'ownerrez_guest_id' => $guestId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function extractOwnerRezPhoneNumber(array $guestData): ?string
    {
        $phones = $guestData['phones'] ?? [];

        if (! is_array($phones) || $phones === []) {
            return null;
        }

        $defaultPhone = collect($phones)->firstWhere('is_default', true);
        $phone = $defaultPhone['number'] ?? $phones[0]['number'] ?? null;

        if (! is_string($phone) || trim($phone) === '') {
            return null;
        }

        return preg_replace('/\s+/', '', trim($phone));
    }

    /**
     * Ensure custom field exists on an OwnerRez booking to identify it as from our platform
     */
    public function ensureBookingCustomField(int $ownerrezBookingId): void
    {
        $fieldDefinitionId = (int) config('ownerrez.sync.custom_field_definition_id', 294966319);
        $fieldValue = config('ownerrez.sync.custom_field_value', 'places');

        $bookingData = $this->apiService->getBooking($ownerrezBookingId);
        $existingField = collect($bookingData['fields'] ?? [])
            ->firstWhere('code', 'BXSOURCEDOMAIN');

        if ($existingField && $existingField['value'] === $fieldValue) {
            // Update local booking site if not already set
            Booking::where('ownerrez_booking_id', $ownerrezBookingId)
                ->whereNull('site')
                ->update(['site' => $fieldValue]);

            return;
        }

        $this->apiService->createCustomField(
            entityId: $ownerrezBookingId,
            entityType: 'booking',
            fieldDefinitionId: $fieldDefinitionId,
            value: $fieldValue
        );

        // Update local booking site
        Booking::where('ownerrez_booking_id', $ownerrezBookingId)
            ->update(['site' => $fieldValue]);

        Log::info('Custom field set on OwnerRez booking', [
            'ownerrez_booking_id' => $ownerrezBookingId,
            'value' => $fieldValue,
        ]);
    }

    /**
     * Map local booking data to OwnerRez format
     */
    protected function mapLocalToOwnerRez(Booking $booking): array
    {
        $mapping = $booking->apartment->ownerrezMapping;

        return [
            'property_id' => $mapping->ownerrez_property_id,
            'arrival' => $booking->check_in->format('Y-m-d'),
            'departure' => $booking->check_out->format('Y-m-d'),
            'check_in' => $booking->check_in_time ? $booking->check_in_time->format('H:i') : '15:00',
            'check_out' => $booking->check_out_time ? $booking->check_out_time->format('H:i') : '11:00',
            'is_block' => false,
            'notes' => $booking->notes ?? '',
            'title' => "Booking #{$booking->number_of_booking}",
        ];
    }

    /**
     * Map OwnerRez status to local status
     */
    protected function mapOwnerRezStatus(string $ownerrezStatus): string
    {
        return match (strtolower($ownerrezStatus)) {
            'active' => 'approved',
            'pending' => 'approved',
            'canceled' => 'canceled',
            default => 'approved',
        };
    }

    /**
     * Invalidate property cache and schedule a calendar cache refresh
     */
    protected function invalidatePropertyCache(int|string $propertyId): void
    {
        Cache::forget("ownerrez:bookings:{$propertyId}");
        Cache::forget("ownerrez:property:{$propertyId}");

        // مسح كاش الـ availability (blocks + bookings) بحيث يُعاد جلبها
        Cache::forget("ownerrez:calendar:v1:{$propertyId}");

        \App\Jobs\OwnerRez\RefreshCalendarCacheJob::dispatch($propertyId);
    }
}
