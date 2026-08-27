<?php

namespace App\Services\Locks;

use App\Models\Booking;
use App\Models\PasscodeRetryAttempt;
use App\Models\SmartLockPasscode;
use App\Services\Locks\Contracts\LockProviderInterface;
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
                    'error' => $e->getMessage(),
                ]);
                PasscodeRetryAttempt::createOrUpdateForBooking($booking, $e->getMessage(), 'provision');
                $booking->markPasscodeAsFailed($e->getMessage());
                $booking->markPasscodeAsRetryScheduled();
                throw $e;
            }
        });
    }

    /**
     * Revoke every active passcode for a booking. Idempotent — a booking
     * with no stored passcode is left untouched.
     *
     * @throws Throwable
     */
    public function revokeForBooking(Booking $booking, string $reason): void
    {
        Cache::lock($this->lockKey($booking), 30)->block(10, function () use ($booking, $reason) {
            $passcodes = $booking->smartLockPasscodes()->get();

            if ($passcodes->isEmpty()) {
                return;
            }

            $apartment = $booking->apartment;
            $buildingCredentials = $apartment ? $this->credentials->forApartment($apartment) : null;

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
                        'error' => $e->getMessage(),
                    ]);
                    PasscodeRetryAttempt::createOrUpdateForBooking($booking, $e->getMessage(), 'revoke');
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

    private function generatePasscode(int $length = 6): string
    {
        return substr(str_shuffle('0123456789'), 0, $length);
    }

    private function combineDateAndTime($date, $time): \Carbon\Carbon
    {
        $time = $time?->format('H:i:s') ?? '00:00:00';

        return \Carbon\Carbon::parse($date->format('Y-m-d').' '.$time);
    }

    private function lockKey(Booking $booking): string
    {
        return "booking-passcode:{$booking->id}";
    }
}
