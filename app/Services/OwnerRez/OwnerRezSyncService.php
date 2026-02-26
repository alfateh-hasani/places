<?php

namespace App\Services\OwnerRez;

use App\Exceptions\OwnerRez\BookingConflictException;
use App\Exceptions\OwnerRez\OwnerRezApiException;
use App\Models\Apartment;
use App\Models\Booking;
use App\Models\BookingChannelConflict;
use App\Models\Customer;
use App\Models\OwnerRezBooking;
use App\Models\OwnerRezPropertyMapping;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OwnerRezSyncService
{
    public function __construct(
        protected OwnerRezApiService $apiService
    ) {}

    /**
     * Check availability for a property in OwnerRez
     */
    public function checkAvailability(int|string $propertyId, string $from, string $to): bool
    {
        try {
            $bookings = $this->getActiveBookings($propertyId, $from, $to);

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
     * Get active bookings for a property
     */
    public function getActiveBookings(int|string $propertyId, string $from, string $to): Collection
    {
        $cacheKey = "ownerrez:availability:v2:{$propertyId}:{$from}:{$to}";
        $cacheTtl = config('ownerrez.availability.cache_ttl', 300);

        $bookings = Cache::remember($cacheKey, $cacheTtl, function () use ($propertyId, $from, $to) {
            $response = $this->apiService->getBookings([
                'property_ids' => $propertyId,
                'from' => $from,
                'to' => $to,
                'status' => 'Active',
                'include_guest' => 'true',
            ]);

            return collect($response['items'] ?? []);
        });

        // Filter bookings that overlap with requested dates
        return $bookings->filter(function ($booking) use ($from, $to) {
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

        match ($action) {
            'entity_create' => $this->handleBookingCreated($bookingData),
            'entity_update' => $this->handleBookingUpdated($bookingData),
            'entity_delete' => $this->handleBookingDeleted($bookingData),
            default => Log::warning('Unknown webhook action', ['action' => $action]),
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
        $logger = Log::channel('ownerrez');
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
        $ownerrezBookingId = $bookingData['id'] ?? null;

        if (! $ownerrezBookingId) {
            return;
        }

        $ownerrezBooking = OwnerRezBooking::where('ownerrez_booking_id', $ownerrezBookingId)->first();
        if (! $ownerrezBooking || ! $ownerrezBooking->localBooking) {
            Log::info('OwnerRez booking not found locally', ['ownerrez_booking_id' => $ownerrezBookingId]);

            return;
        }

        try {
            // Check if booking is canceled
            $status = $bookingData['status'] ?? null;
            if ($status === 'canceled') {
                $this->cancelLocalBookingFromOwnerRez($ownerrezBooking->localBooking);
                Log::info('Canceled booking from OwnerRez via update webhook', [
                    'ownerrez_booking_id' => $ownerrezBookingId,
                    'local_booking_id' => $ownerrezBooking->local_booking_id,
                ]);
            } else {
                $this->updateLocalBookingFromOwnerRez($ownerrezBooking->localBooking, $bookingData);
                Log::info('Successfully updated booking from OwnerRez', [
                    'ownerrez_booking_id' => $ownerrezBookingId,
                    'local_booking_id' => $ownerrezBooking->local_booking_id,
                ]);
            }

            $ownerrezBooking->update([
                'raw_data' => $bookingData,
                'sync_status' => 'synced',
                'synced_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update booking from OwnerRez', [
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
        $ownerrezBookingId = $bookingData['id'] ?? null;

        if (! $ownerrezBookingId) {
            return;
        }

        $ownerrezBooking = OwnerRezBooking::where('ownerrez_booking_id', $ownerrezBookingId)->first();
        if (! $ownerrezBooking || ! $ownerrezBooking->localBooking) {
            return;
        }

        try {
            $this->cancelLocalBookingFromOwnerRez($ownerrezBooking->localBooking);
            Log::info('Successfully canceled booking from OwnerRez', [
                'ownerrez_booking_id' => $ownerrezBookingId,
                'local_booking_id' => $ownerrezBooking->local_booking_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to cancel booking from OwnerRez', [
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

        return Booking::create($localData);
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
        $this->apiService->updateBooking($booking->ownerrez_booking_id, $ownerrezData);

        // Ensure custom field is set
        $this->ensureBookingCustomField((int) $booking->ownerrez_booking_id);
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
            $paymentStatus = 'partial';
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

        // Extract contact info
        $email = $mergedData['email_addresses'][0]['address'] ?? null;
        $phone = $mergedData['phones'][0]['number'] ?? null;

        if ($phone) {
            $phone = str_replace(' ', '', $phone);
        }

        // Step 3: Try to find by phone
        if ($phone) {
            $customer = Customer::where('phone', $phone)->first();
            if ($customer) {
                $customer->update(['ownerrez_guest_id' => $guestId]);
                $logger->info('Found existing customer by phone, linked ownerrez_guest_id', [
                    'customer_id' => $customer->id,
                    'ownerrez_guest_id' => $guestId,
                ]);

                return $customer;
            }
        }

        // Step 5: Create new customer
        try {
            $customer = Customer::create([
                'first_name' => $mergedData['first_name'] ?? '',
                'last_name' => $mergedData['last_name'] ?? '',
                'email' => $email,
                'phone' => $phone ?? 'N/A',
                'ownerrez_guest_id' => $guestId,
                'account_verified' => false,
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
     * Invalidate property cache
     */
    protected function invalidatePropertyCache(int|string $propertyId): void
    {
        $pattern = "ownerrez:availability:{$propertyId}:*";
        // Note: This is a simple implementation. For production, consider using Cache tags
        Cache::forget("ownerrez:bookings:{$propertyId}");
        Cache::forget("ownerrez:property:{$propertyId}");
    }
}
