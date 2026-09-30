<?php

namespace App\Services\Locks;

use App\Exceptions\Locks\LockOperationException;
use App\Models\Apartment;
use App\Models\Booking;
use App\Models\PasscodeRetryAttempt;
use App\Models\SmartLockPasscode;
use App\Services\Locks\Contracts\LockProviderInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Single entry point for every smart-lock passcode operation (provisioning,
 * revocation, rescheduling). All callers — controllers, services, console
 * commands, event listeners — go through this class instead of talking to
 * ScienerLockService directly.
 */
class LockAccessService
{
    public function __construct(
        private readonly LockProviderInterface $provider,
        private readonly LockCredentialResolver $credentials,
    ) {}

    /**
     * Generate and store a passcode for the booking's stay. Idempotent —
     * a booking that already has a passcode row is left untouched.
     *
     * Deliberately checks existence, not getActivePasscode() — a passcode
     * for a future check-in has a start_date that hasn't arrived yet, so
     * "active" would be false even though the booking is already provisioned,
     * causing duplicate passcodes to be created on every trigger.
     *
     * @throws Throwable
     */
    public function provisionForBooking(Booking $booking): void
    {
        if ($booking->smartLockPasscodes()->exists()) {
            return;
        }

        Cache::lock($this->lockKey($booking), 30)->block(10, function () use ($booking) {
            $booking->refresh();

            if ($booking->smartLockPasscodes()->exists()) {
                return;
            }

            try {
                $apartment = $booking->apartment;

                if (! $apartment) {
                    throw new RuntimeException("Booking {$booking->id} has no apartment.");
                }

                $credentials = $this->credentials->forApartment($apartment);

                $passcode = $this->generatePasscode();
                $start = $this->combineDateAndTime($booking->check_in, $booking->check_in_time);
                $end = $this->combineDateAndTime($booking->check_out, $booking->check_out_time);

                $vendorPasscodeId = $this->provider->createPasscode($credentials, $passcode, $start, $end, (string) $booking->id);

                SmartLockPasscode::create([
                    'smart_lock_id' => $credentials->lockId,
                    'passcode_id' => $vendorPasscodeId,
                    'apartment_id' => $booking->apartment_id,
                    'customer_id' => $booking->customer_id,
                    'booking_id' => $booking->id,
                    'nickname' => $booking->id,
                    'keyboard_pwd' => $passcode,
                    'start_date' => $start,
                    'end_date' => $end,
                ]);

                PasscodeRetryAttempt::where('booking_id', $booking->id)->where('operation', 'provision')->delete();
                $booking->markPasscodeAsGenerated();
            } catch (Throwable $e) {
                Log::error('Smart lock passcode provisioning failed', [
                    'booking_id' => $booking->id,
                    'vendor_error_code' => $e instanceof LockOperationException ? $e->vendorErrorCode : null,
                    'retryable' => $e instanceof LockOperationException ? $e->retryable : true,
                    'error' => $e->getMessage(),
                ]);
                $this->recordFailedAttempt($booking, $e, 'provision');
                $booking->markPasscodeAsFailed(LockErrorPresenter::storedMessage($e));
                $booking->markPasscodeAsRetryScheduled();
                throw $e;
            }
        });
    }

    /**
     * Revoke every active passcode for a booking, resolving the lock credentials from the
     * booking's CURRENT apartment. Idempotent — a booking with no stored passcode is left
     * untouched.
     *
     * @throws Throwable
     */
    public function revokeForBooking(Booking $booking, string $reason): void
    {
        $this->revokePasscodes($booking, $booking->apartment, $reason);
    }

    /**
     * Revoke a booking's passcodes using an EXPLICIT apartment for the building/Sciener
     * credentials. Required when moving a booking to another unit: the old passcode must be
     * deleted with the SOURCE apartment's credentials while `booking.apartment_id` already
     * points at the destination (buildings can have different Sciener accounts).
     *
     * @throws Throwable
     */
    public function revokeForBookingUsingApartment(Booking $booking, Apartment $credentialApartment, string $reason): void
    {
        $this->revokePasscodes($booking, $credentialApartment, $reason);
    }

    /**
     * @throws Throwable
     */
    private function revokePasscodes(Booking $booking, ?Apartment $credentialApartment, string $reason): void
    {
        Cache::lock($this->lockKey($booking), 30)->block(10, function () use ($booking, $credentialApartment, $reason) {
            $passcodes = $booking->smartLockPasscodes()->get();

            if ($passcodes->isEmpty()) {
                return;
            }

            $buildingCredentials = $credentialApartment ? $this->credentials->forApartment($credentialApartment) : null;

            foreach ($passcodes as $passcode) {
                try {
                    $credentials = new LockCredentials(
                        lockId: $passcode->smart_lock_id,
                        username: $buildingCredentials?->username,
                        password: $buildingCredentials?->password,
                    );

                    DB::transaction(function () use ($passcode, $credentials) {
                        $vendorPasscodeId = $passcode->passcode_id;
                        $passcode->delete();
                        $this->provider->deletePasscode($credentials, $vendorPasscodeId);
                    });

                    PasscodeRetryAttempt::where('booking_id', $booking->id)->where('operation', 'revoke')->delete();
                } catch (Throwable $e) {
                    Log::error('Smart lock passcode revocation failed', [
                        'booking_id' => $booking->id,
                        'passcode_id' => $passcode->id,
                        'reason' => $reason,
                        'vendor_error_code' => $e instanceof LockOperationException ? $e->vendorErrorCode : null,
                        'retryable' => $e instanceof LockOperationException ? $e->retryable : true,
                        'error' => $e->getMessage(),
                    ]);
                    $this->recordFailedAttempt($booking, $e, 'revoke');
                    throw $e;
                }
            }

            Log::info('Smart lock passcode revoked', ['booking_id' => $booking->id, 'reason' => $reason]);
        });
    }

    /**
     * Revoke the current passcode(s) and provision a fresh one — used when a
     * booking's dates or check-in time change. A failed revoke is logged but
     * never blocks the reschedule; the retry command recovers it.
     */
    public function rescheduleForBooking(Booking $booking): void
    {
        try {
            $this->revokeForBooking($booking, 'reschedule');
        } catch (Throwable $e) {
            Log::warning('Failed to revoke previous passcode during reschedule', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }

        $booking->refresh();
        $booking->markPasscodeAsPending();

        $this->provisionForBooking($booking->fresh());
    }

    /**
     * Move a booking's passcode when it is transferred to a DIFFERENT apartment: revoke the
     * old code on the SOURCE lock (using the source apartment's credentials, since the
     * booking already points at the destination), then provision a fresh code on the new
     * apartment's lock. A failed revoke is logged but never blocks provisioning of the new
     * code; the retry command recovers a stuck old code.
     */
    public function moveForBooking(Booking $booking, Apartment $oldApartment): void
    {
        try {
            $this->revokeForBookingUsingApartment($booking, $oldApartment, 'unit-transfer');
        } catch (Throwable $e) {
            Log::warning('Failed to revoke previous passcode during unit transfer', [
                'booking_id' => $booking->id,
                'old_apartment_id' => $oldApartment->id,
                'error' => $e->getMessage(),
            ]);
        }

        $booking->refresh();
        $booking->markPasscodeAsPending();

        $this->provisionForBooking($booking->fresh());
    }

    /**
     * Record a retry-attempt row for a failure. When the failure is a
     * non-retryable vendor error (e.g. wrong Sciener credentials), skip
     * straight to max_attempts_reached instead of scheduling a next attempt —
     * a permanently-broken credential fails identically every time, so
     * retrying it every 10 minutes for days only wastes a cycle and hides
     * a problem that needs a human to fix the stored credentials.
     */
    private function recordFailedAttempt(Booking $booking, Throwable $e, string $operation): void
    {
        $attempt = PasscodeRetryAttempt::createOrUpdateForBooking($booking, $e->getMessage(), $operation);

        if ($e instanceof LockOperationException && ! $e->retryable) {
            $attempt->update(['status' => 'max_attempts_reached', 'next_attempt_at' => null]);
        }
    }

    /**
     * Door passcode from a CSPRNG over the full digit space (repeated digits allowed).
     * str_shuffle is not cryptographically secure and never repeats a digit, which cut
     * the 6-digit space from 1,000,000 to ~151,000 codes.
     */
    private function generatePasscode(int $length = 6): string
    {
        return str_pad((string) random_int(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);
    }

    private function combineDateAndTime($date, $time): Carbon
    {
        $time = $time?->format('H:i:s') ?? '00:00:00';

        return Carbon::parse($date->format('Y-m-d').' '.$time);
    }

    private function lockKey(Booking $booking): string
    {
        return "booking-passcode:{$booking->id}";
    }
}
