<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Building;
use App\Services\BookingService;
use App\Services\PaymentMethods\GeideaPayment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DeletePendingBookings extends Command
{
    protected $signature = 'delete:pending-bookings';

    protected $description = 'Delete pending bookings, but verify with Geidea before deleting paid ones';

    public function handle(): void
    {
        $pendingBookings = Booking::query()
            ->where('status', 'pending')
            ->where('created_at', '<', now()->subMinutes(5))
            ->with('transaction')
            ->get();

        $geidea = new GeideaPayment;

        foreach ($pendingBookings as $booking) {
            $transaction = $booking->transaction;

            // لا يوجد transaction أو transaction بدون order_id → حذف مباشر
            if (! $transaction || ! $transaction->order_id) {
                $booking->delete();

                continue;
            }

            // يوجد order_id → نتحقق من Geidea قبل الحذف
            try {
                $orderData = $geidea->verifyPayment($transaction->order_id);

                if (! $orderData) {
                    // فشل الاتصال بـ Geidea → لا نحذف، ننتظر المحاولة التالية
                    Log::channel('geidea')->warning('DeletePendingBookings: Geidea API unreachable, skipping', [
                        'booking_id' => $booking->id,
                        'order_id' => $transaction->order_id,
                    ]);

                    continue;
                }

                $detailedStatus = $orderData['order']['detailedStatus'] ?? null;

                if ($detailedStatus === 'Paid') {
                    // مدفوع فعلاً! نأكد الحجز بدل ما نحذفه مع lock لمنع race condition مع الـ webhook
                    $confirmed = DB::transaction(function () use ($transaction) {
                        $tx = \App\Models\Transaction::where('id', $transaction->id)->lockForUpdate()->first();

                        if ($tx->status === 'completed') {
                            return false;
                        }

                        $tx->update(['status' => 'completed']);

                        return true;
                    });

                    if (! $confirmed) {
                        continue;
                    }

                    app(BookingService::class)->completeBookingAfterPayment($transaction->id);

                    $booking->refresh();
                    $this->sendConfirmationEmails($booking);

                    Log::channel('geidea')->info('DeletePendingBookings: recovered paid booking', [
                        'booking_id' => $booking->id,
                        'order_id' => $transaction->order_id,
                    ]);
                } else {
                    // Geidea أكدت أنه غير مدفوع → حذف
                    $booking->delete();

                    Log::channel('geidea')->info('DeletePendingBookings: confirmed unpaid, deleting', [
                        'booking_id' => $booking->id,
                        'order_id' => $transaction->order_id,
                        'geidea_status' => $detailedStatus,
                    ]);
                }
            } catch (\Exception $e) {
                Log::channel('geidea')->error('DeletePendingBookings: Geidea verification failed', [
                    'booking_id' => $booking->id,
                    'order_id' => $transaction->order_id,
                    'error' => $e->getMessage(),
                ]);
                // لا نحذف في حالة الخطأ - ننتظر المحاولة التالية
            }
        }
    }

    private function sendConfirmationEmails(Booking $booking): void
    {
        try {
            if ($booking->customer_email) {
                Mail::to($booking->customer_email)->send(new \App\Mail\ReservationDetails($booking));
            }

            $building = Building::where('id', $booking->apartment?->building_id)->first();

            if ($building) {
                $supervisor = \App\Models\User::where('id', $building->supervisor_id)->first();

                if ($supervisor?->email) {
                    Mail::to($supervisor->email)->send(new \App\Mail\ReservationDetails($booking));
                }
            }
        } catch (\Exception $e) {
            Log::channel('geidea')->error('DeletePendingBookings: failed to send email', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
