<?php

namespace App\Services;

use App\Actions\DateChanges\ProcessDateChangeRefund;
use App\Enums\DateChangeStatus;
use App\Mail\DateChangeRequested;
use App\Models\Booking;
use App\Models\Coupon;
use App\Models\DateChangeRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Services\OwnerRez\OwnerRezSyncService;
use App\Services\PaymentMethods\GeideaPayment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Orchestrates a customer request to change a booking's dates.
 *
 * Policy (agreed):
 *   - same price (delta = 0) → applied directly, no money, no review.
 *   - more expensive (delta > 0) → pay-first: customer pays the difference, then dates are applied.
 *   - cheaper (delta < 0) → staff review, then apply + partial refund of the difference (retryable).
 *
 * The new date range is validated against the exact same availability constraints as a fresh
 * booking (local overlaps + OwnerRez), excluding the booking's own current range.
 */
class DateChangeService
{
    public function __construct(
        private readonly BookingService $bookingService,
    ) {}

    /**
     * Validate availability of the new range and compute the price delta.
     *
     * @return array{new_check_in:string,new_check_out:string,nights:int,original_price:float,new_price:float,price_delta:float,direction:string}
     */
    public function quote(Booking $booking, mixed $newCheckIn, mixed $newCheckOut): array
    {
        $newIn = Carbon::parse($newCheckIn)->startOfDay();
        $newOut = Carbon::parse($newCheckOut)->startOfDay();

        if (! $newIn->lt($newOut)) {
            throw ValidationException::withMessages(['dates' => __('يجب أن يكون تاريخ الدخول قبل تاريخ الخروج.')]);
        }

        if ($newIn->equalTo($booking->check_in) && $newOut->equalTo($booking->check_out)) {
            throw ValidationException::withMessages(['dates' => __('api.date_change_same_dates')]);
        }

        // Same availability constraints as a new booking, but ignore this booking's own (old) range.
        $this->bookingService->checkAvailability($booking->apartment, $newIn, $newOut, $booking->id);

        $coupon = $booking->coupon_id ? Coupon::find($booking->coupon_id) : null;
        $newPrices = $this->bookingService->calculatePricesWithDates($booking->apartment, $newIn, $newOut, $coupon);

        $originalPrice = round((float) $booking->final_price, 2);
        $newPrice = round((float) $newPrices['final_price'], 2);
        $delta = round($newPrice - $originalPrice, 2);

        return [
            'new_check_in' => $newIn->toDateString(),
            'new_check_out' => $newOut->toDateString(),
            'nights' => $newIn->diffInDays($newOut),
            'original_price' => $originalPrice,
            'new_price' => $newPrice,
            'price_delta' => $delta,
            'direction' => $delta > 0.001 ? 'surcharge' : ($delta < -0.001 ? 'refund' : 'even'),
        ];
    }

    /**
     * Create a date-change request and route it by the price delta.
     *
     * @return array{request:DateChangeRequest,action:string,redirect?:?string,quote:array}
     */
    public function request(Booking $booking, mixed $newCheckIn, mixed $newCheckOut): array
    {
        $this->assertChangeable($booking);

        return DB::transaction(function () use ($booking, $newCheckIn, $newCheckOut) {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            $quote = $this->quote($booking, $newCheckIn, $newCheckOut);

            $request = DateChangeRequest::create([
                'booking_id' => $booking->id,
                'original_check_in' => $booking->check_in,
                'original_check_out' => $booking->check_out,
                'new_check_in' => $quote['new_check_in'],
                'new_check_out' => $quote['new_check_out'],
                'original_price' => $quote['original_price'],
                'new_price' => $quote['new_price'],
                'price_delta' => $quote['price_delta'],
                'gateway_order_id' => $booking->transaction?->order_id,
                'status' => DateChangeStatus::Pending->value,
            ]);

            $delta = $quote['price_delta'];

            // Same price → apply immediately.
            if (abs($delta) < 0.01) {
                $this->applyDates($request);
                $request->update(['status' => DateChangeStatus::Applied->value]);

                return ['request' => $request->fresh(), 'action' => 'applied', 'quote' => $quote];
            }

            // Cheaper → staff review before refunding the difference.
            if ($delta < 0) {
                $request->update(['status' => DateChangeStatus::PendingReview->value]);
                $this->notifyReviewers($booking, $request);

                return ['request' => $request->fresh(), 'action' => 'pending_review', 'quote' => $quote];
            }

            // More expensive → pay-first.
            $request->update(['status' => DateChangeStatus::AwaitingPayment->value]);
            $redirect = $this->startSurchargePayment($booking, $request);

            return ['request' => $request->fresh(), 'action' => 'awaiting_payment', 'redirect' => $redirect, 'quote' => $quote];
        });
    }

    /**
     * Apply the new dates to the booking, regenerate the passcode, and sync OwnerRez (linked units).
     * Atomic: if OwnerRez PATCH fails, the local date change is rolled back.
     */
    public function applyDates(DateChangeRequest $request): Booking
    {
        $booking = DB::transaction(function () use ($request) {
            $booking = Booking::whereKey($request->booking_id)->lockForUpdate()->firstOrFail();

            $newIn = $request->new_check_in->toDateString();
            $newOut = $request->new_check_out->toDateString();

            // Final availability re-check (excluding self) — guards against a race during review.
            // Live OwnerRez check (bypasses the 5-minute cache): this is the commit point.
            $this->bookingService->checkAvailability($booking->apartment, $newIn, $newOut, $booking->id, liveCheck: true);

            $nights = Carbon::parse($newIn)->diffInDays(Carbon::parse($newOut));
            $newFinal = round((float) $request->new_price, 2);
            $oneNight = $nights > 0 ? round($newFinal / $nights, 2) : $newFinal;

            $vatRate = (float) Config::get('settings.vat_rate', 15);
            $vat = round($newFinal * $vatRate / (100 + $vatRate), 2);

            $booking->update([
                'check_in' => $newIn,
                'check_out' => $newOut,
                'number_of_nights' => $nights,
                'total_price' => $newFinal,
                'final_price' => $newFinal,
                'one_night_price' => $oneNight,
                'tax' => $vat,
            ]);

            $request->update(['applied_at' => now()]);

            // OwnerRez PATCH is the LAST step inside the transaction: if it throws, EVERYTHING
            // above (dates, price, applied_at) rolls back — the local booking never diverges
            // from OwnerRez. Applies to all paths (direct / surcharge-accept / refund-accept).
            $this->syncOwnerRez($booking->fresh());

            return $booking->fresh();
        });

        // Passcode regeneration runs only AFTER a successful commit — it's an external,
        // best-effort call and must never trigger (or be undone by) a DB rollback.
        $this->regeneratePasscode($booking);

        return $booking->fresh();
    }

    /**
     * Apply a date-change request whose surcharge payment has been confirmed by the gateway.
     * Idempotent — safe to call from both the Geidea webhook and the browser-return callback,
     * whichever arrives first; the other becomes a no-op.
     */
    public function confirmSurchargePayment(DateChangeRequest $request): void
    {
        if ($request->status === DateChangeStatus::Applied->value) {
            return;
        }

        $this->applyDates($request);
        $request->update(['status' => DateChangeStatus::Applied->value]);
    }

    /**
     * Re-settle a failed/stuck request. Retry-first policy:
     *  - If the dates were never applied (e.g. surcharge PAID but OwnerRez sync failed) → re-attempt
     *    the apply (re-sync). The captured payment is kept; we do NOT refund on a transient failure.
     *  - If the dates were already applied (reduction path) → retry the difference refund.
     *
     * @return string one of: applied | processing | failed
     */
    public function retrySettlement(DateChangeRequest $request): string
    {
        if (is_null($request->applied_at)) {
            // Track the attempt so the scheduled reconciler can bound apply-retries (attempts < max).
            $request->increment('attempts');
            $request->forceFill(['last_attempt_at' => now()])->save();

            try {
                // Re-attempt apply (throws on OwnerRez failure).
                $this->applyDates($request);
            } catch (\Throwable $e) {
                $request->forceFill([
                    'status' => DateChangeStatus::Failed->value,
                    'error' => $e->getMessage(),
                ])->save();

                throw $e;
            }

            // Cheaper change still owes a refund of the difference; surcharge/even is fully settled.
            if ((float) $request->price_delta < 0) {
                $request->update(['status' => DateChangeStatus::Processing->value]);

                return app(ProcessDateChangeRefund::class)->execute($request->fresh());
            }

            $request->update(['status' => DateChangeStatus::Applied->value, 'error' => null]);

            return 'applied';
        }

        // Dates already applied; only the difference refund failed → retry the refund.
        return app(ProcessDateChangeRefund::class)->execute($request->fresh());
    }

    /**
     * Re-initiate a stuck/failed surcharge payment. Re-validates availability and re-prices
     * (the window may have been taken, or the price moved), then routes by the fresh delta.
     *
     * @return array{request:DateChangeRequest,action:string,redirect?:?string}
     */
    public function retryPayment(DateChangeRequest $request): array
    {
        if ($request->status !== DateChangeStatus::AwaitingPayment->value) {
            throw ValidationException::withMessages(['request' => __('api.date_change_not_awaiting_payment')]);
        }

        return DB::transaction(function () use ($request) {
            $booking = Booking::whereKey($request->booking_id)->lockForUpdate()->firstOrFail();

            $quote = $this->quote($booking, $request->new_check_in->toDateString(), $request->new_check_out->toDateString());

            $request->update([
                'original_price' => $quote['original_price'],
                'new_price' => $quote['new_price'],
                'price_delta' => $quote['price_delta'],
            ]);

            $delta = $quote['price_delta'];

            // Price may have dropped since the request — settle without a new charge.
            if (abs($delta) < 0.01) {
                $this->applyDates($request);
                $request->update(['status' => DateChangeStatus::Applied->value]);

                return ['request' => $request->fresh(), 'action' => 'applied'];
            }

            if ($delta < 0) {
                $request->update(['status' => DateChangeStatus::PendingReview->value]);
                $this->notifyReviewers($booking, $request);

                return ['request' => $request->fresh(), 'action' => 'pending_review'];
            }

            $redirect = $this->startSurchargePayment($booking, $request);

            return ['request' => $request->fresh(), 'action' => 'awaiting_payment', 'redirect' => $redirect];
        });
    }

    /**
     * Customer withdraws an open request (before it is applied), freeing the reserved window.
     */
    public function cancelByCustomer(DateChangeRequest $request): void
    {
        $cancelable = [
            DateChangeStatus::AwaitingPayment->value,
            DateChangeStatus::Pending->value,
            DateChangeStatus::PendingReview->value,
        ];

        if (! in_array($request->status, $cancelable, true)) {
            throw ValidationException::withMessages(['request' => __('api.date_change_cannot_cancel')]);
        }

        $request->update(['status' => DateChangeStatus::Rejected->value]);
    }

    /**
     * Expire unpaid/transient requests older than the given age, releasing their held window.
     */
    public function expireStale(int $minutes): int
    {
        $threshold = now()->subMinutes($minutes);

        return DateChangeRequest::whereIn('status', [
            DateChangeStatus::AwaitingPayment->value,
            DateChangeStatus::Pending->value,
        ])
            ->where('updated_at', '<=', $threshold)
            ->update(['status' => DateChangeStatus::Rejected->value]);
    }

    private function assertChangeable(Booking $booking): void
    {
        if (! $booking->canBeCanceled()) {
            throw ValidationException::withMessages(['booking_id' => __('api.booking_cannot_be_canceled')]);
        }

        $open = DateChangeRequest::where('booking_id', $booking->id)
            ->whereIn('status', DateChangeStatus::openValues())
            ->exists();

        if ($open) {
            throw ValidationException::withMessages(['booking_id' => __('api.date_change_already_pending')]);
        }
    }

    private function startSurchargePayment(Booking $booking, DateChangeRequest $request): string
    {
        $amount = round((float) $request->price_delta, 2);

        $transaction = Transaction::create([
            'customer_id' => $booking->customer_id,
            'apartment_id' => $booking->apartment_id,
            'booking_id' => $booking->id,
            'booking_data' => json_encode([
                'date_change_request_id' => $request->id,
                'type' => 'date_change_surcharge',
            ]),
            'transaction_reference' => time().uniqid(),
            'amount' => $amount,
            'currency' => 'SAR',
            'status' => 'pending',
            'type' => 'deposit',
            'payment_gateway' => 'geidea',
            'payment_gateway_response' => null,
            'platform' => $booking->booking_source ?? 'web',
        ]);

        $request->update(['transaction_id' => $transaction->id]);

        $returnUrl = route('web-booking.date-change.callback', [$request->id, $transaction->id]);
        $result = (new GeideaPayment())->withReturnUrl($returnUrl)->process($transaction);

        if (is_array($result) && isset($result['transaction']['url'])) {
            return $result['transaction']['url'];
        }

        throw ValidationException::withMessages(['payment' => __('api.payment_failed')]);
    }

    private function syncOwnerRez(Booking $booking): void
    {
        if (empty($booking->ownerrez_booking_id)) {
            return;
        }

        app(OwnerRezSyncService::class)->updateOwnerRezBooking($booking);
    }

    private function regeneratePasscode(Booking $booking): void
    {
        if (empty($booking->apartment?->smart_lock_id)) {
            return;
        }

        try {
            foreach ($booking->smartLockPasscodes as $passcode) {
                try {
                    $sciener = new ScienerLockService(
                        $booking->apartment->building->ttlock_username,
                        $booking->apartment->building->ttlock_password,
                    );
                    $sciener->deletePasscode($passcode->smart_lock_id, $passcode->passcode_id);
                } catch (\Throwable $e) {
                    Log::warning('Failed to delete old passcode during date change', [
                        'booking_id' => $booking->id,
                        'error' => $e->getMessage(),
                    ]);
                }
                $passcode->delete();
            }

            $booking->markPasscodeAsPending();
            $this->bookingService->addPasscodeToSmartLock($booking->fresh());
        } catch (\Throwable $e) {
            // Never fail the date change on a lock hiccup — the scheduled retry command will recover it.
            Log::error('Passcode regeneration failed during date change', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Email the review request to users holding the configured reviewer roles
     * (config mail.date_change_reviewers.roles). When mail.date_change_reviewers.only_user_ids
     * is non-empty (local safety net), only those user ids receive it.
     */
    private function notifyReviewers(Booking $booking, DateChangeRequest $request): void
    {
        try {
            $recipients = $this->reviewerUsers();

            if ($recipients->isEmpty()) {
                Log::warning('No date-change reviewers resolved — review email skipped', [
                    'booking_id' => $booking->id,
                    'roles' => Config::array('mail.date_change_reviewers.roles'),
                ]);

                return;
            }

            foreach ($recipients as $recipient) {
                Mail::to($recipient->email)->send(new DateChangeRequested($booking, $request));
            }
        } catch (\Throwable $e) {
            Log::error('Failed to notify reviewers of date-change request', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Users eligible to review date-change requests.
     *
     * @return Collection<int, User>
     */
    public function reviewerUsers(): Collection
    {
        $users = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', Config::array('mail.date_change_reviewers.roles')))
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->orderBy('id')
            ->get(['id', 'email']);

        $onlyIds = Config::array('mail.date_change_reviewers.only_user_ids');

        if ($onlyIds !== []) {
            $users = $users->whereIn('id', $onlyIds)->values();
        }

        return $users;
    }
}
