<?php

namespace App\Services;

use App\Enums\CustomerSource;
use App\Models\Apartment;
use App\Models\Booking;
use App\Models\Building;
use App\Models\Customer;
use App\Models\Transaction;
use App\Services\OwnerRez\OwnerRezSyncService;
use App\Services\Pricing\PricingService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/**
 * Creates a manual "direct booking" from the admin dashboard, settled by bank transfer
 * (no online gateway). Mirrors a paid web booking: same local fields and, for a mapped
 * apartment, the same OwnerRez record (guest + BXSOURCEDOMAIN custom field).
 *
 * For a mapped apartment the OwnerRez push is SYNCHRONOUS and TRANSACTIONAL: if it fails
 * the whole local booking is rolled back (no local-only record), so staff can fix and retry.
 */
class DirectBookingService
{
    public function __construct(
        private BookingService $bookingService,
        private PricingService $pricingService,
        private OwnerRezSyncService $ownerRezSyncService,
    ) {}

    /**
     * @param  array{
     *   apartment_id:int,
     *   check_in:string, check_out:string,
     *   number_of_adults:int, number_of_children:int,
     *   customer_id?:int|null,
     *   new_customer?:array{first_name:string,last_name:string,phone:string,email?:string|null}|null,
     *   final_price?:float|string|null,
     *   transfer_number?:string|null,
     *   receipt?:UploadedFile|null
     * }  $data
     */
    public function createManualBooking(array $data): Booking
    {
        $apartment = Apartment::findOrFail($data['apartment_id']);

        $this->bookingService->validateGuestsCount(
            $apartment,
            $data['number_of_adults'],
            $data['number_of_children'],
        );

        $checkIn = Carbon::parse($data['check_in']);
        $checkOut = Carbon::parse($data['check_out']);

        // reserveApartment locks the apartment row + runs a live (OwnerRez-inclusive)
        // availability check, then commits the booking + OwnerRez push atomically.
        // Customer resolution/creation runs inside the transaction too, so a rollback
        // (e.g. OwnerRez failure) also removes a just-created customer — no orphans and
        // no duplicates on retry.
        $booking = $this->bookingService->reserveApartment(
            $apartment->id,
            $checkIn,
            $checkOut,
            null,
            function (Apartment $apartment) use ($data, $checkIn, $checkOut): Booking {
                $customer = $this->resolveCustomer($data);
                $booking = $this->createPendingBooking($apartment, $customer, $checkIn, $checkOut, $data);

                $this->createBankTransferTransaction($booking, $customer, $data);

                // Synchronous OwnerRez push for mapped apartments. Any failure throws and
                // rolls back this transaction (booking + transaction), leaving no local
                // record — exactly what the retry UX expects.
                $mapping = $apartment->ownerrezMapping;
                if ($mapping && $mapping->sync_enabled) {
                    $this->ownerRezSyncService->sendBookingToOwnerRez($booking);
                }

                return $booking;
            }
        );

        // Post-commit: flip to approved+paid. The `updated` hook fires BookingApproved,
        // which provisions the smart lock immediately (QUEUE_CONNECTION=sync) and is skipped
        // by the OwnerRez listener for 'dashboard' bookings since we pushed synchronously.
        //
        // Smart-lock provisioning must be NON-FATAL here: the booking + OwnerRez record are
        // already committed, so a lock/Sciener failure must not bubble up — otherwise the
        // controller would report failure and staff would retry, creating a duplicate. The
        // passcode is marked failed/retry-scheduled and picked up by the retry mechanism.
        try {
            $booking->update([
                'status' => 'approved',
                'payment_status' => 'paid',
            ]);
        } catch (\Throwable $e) {
            // The status/payment columns were already persisted before the listener ran;
            // only the lock side-effect failed.
            Log::error('Direct booking smart-lock provisioning failed (booking kept)', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }

        $this->attachReceipt($booking, $data['receipt'] ?? null);

        return $booking->refresh();
    }

    private function resolveCustomer(array $data): Customer
    {
        if (! empty($data['customer_id'])) {
            return Customer::findOrFail($data['customer_id']);
        }

        $new = $data['new_customer'];

        return Customer::create([
            'first_name' => trim($new['first_name']),
            'last_name' => trim($new['last_name']),
            'phone' => trim($new['phone']),
            'email' => isset($new['email']) && $new['email'] !== ''
                ? strtolower(trim($new['email']))
                : null,
            'source' => CustomerSource::Local,
        ]);
    }

    private function createPendingBooking(Apartment $apartment, Customer $customer, Carbon $checkIn, Carbon $checkOut, array $data): Booking
    {
        $numberOfNights = $checkIn->diffInDays($checkOut);
        $prices = $this->resolvePrices($apartment, $checkIn, $checkOut, $data['final_price'] ?? null, $numberOfNights);
        $building = Building::find($apartment->building_id);

        return Booking::create([
            'apartment_id' => $apartment->id,
            'customer_id' => $customer->id,
            'customer_full_name' => $customer->full_name,
            'customer_email' => $customer->email,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'number_of_nights' => $numberOfNights,
            'adults_count' => $data['number_of_adults'],
            'children_count' => $data['number_of_children'],
            'status' => 'pending',
            'payment_status' => 'pending',
            'total_price' => $prices['total_price'],
            'discount' => $prices['discount'],
            'final_price' => $prices['final_price'],
            'one_night_price' => $prices['one_night_price'],
            'tax' => $prices['tax'],
            'coupon_id' => null,
            'coupon_code' => null,
            'booking_source' => 'dashboard',
            'payment_method_code' => 'bank_transfer',
            'check_in_time' => $building?->check_in_time,
            'check_out_time' => $building?->check_out_time,
        ]);
    }

    /**
     * Auto-calculate from the pricing engine (VAT-inclusive), then honor an optional
     * admin override of the final price. A lowered final price is recorded as a discount.
     *
     * @return array{total_price:float,final_price:float,discount:float,tax:float,one_night_price:float}
     */
    private function resolvePrices(Apartment $apartment, Carbon $checkIn, Carbon $checkOut, float|string|null $override, int $numberOfNights): array
    {
        $baseTotal = round((float) $this->pricingService->calculate($apartment, $checkIn, $checkOut)['total'], 2);

        $finalPrice = ($override !== null && $override !== '')
            ? round((float) $override, 2)
            : $baseTotal;

        $discount = max(round($baseTotal - $finalPrice, 2), 0);
        $totalPrice = round($finalPrice + $discount, 2);
        $tax = round($finalPrice * 15 / 115, 2);
        $oneNightPrice = $numberOfNights > 0 ? round($totalPrice / $numberOfNights, 2) : 0.0;

        return [
            'total_price' => $totalPrice,
            'final_price' => $finalPrice,
            'discount' => $discount,
            'tax' => $tax,
            'one_night_price' => $oneNightPrice,
        ];
    }

    private function createBankTransferTransaction(Booking $booking, Customer $customer, array $data): Transaction
    {
        $transaction = Transaction::create([
            'customer_id' => $customer->id,
            'apartment_id' => $booking->apartment_id,
            'booking_id' => $booking->id,
            'transaction_reference' => time().uniqid(),
            'transfer_number' => $data['transfer_number'] ?? null,
            'amount' => (float) $booking->final_price,
            'currency' => 'SAR',
            'type' => 'deposit',
            'status' => 'completed',
            'payment_gateway' => 'bank_transfer',
            'platform' => 'dashboard',
        ]);

        $booking->update(['transaction_id' => $transaction->id]);

        return $transaction;
    }

    /**
     * Attach the optional receipt image to the transaction, post-commit and best-effort:
     * a media failure must not undo an already-created (and OwnerRez-synced) booking.
     */
    private function attachReceipt(Booking $booking, ?UploadedFile $receipt): void
    {
        if (! $receipt) {
            return;
        }

        $transaction = $booking->transaction;
        if (! $transaction) {
            return;
        }

        try {
            $transaction->addMedia($receipt)->toMediaCollection('receipt');
        } catch (\Throwable $e) {
            Log::warning('Direct booking receipt upload failed', [
                'booking_id' => $booking->id,
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
