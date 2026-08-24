<?php

namespace App\Http\Controllers\Api;

use App\Enums\DateChangeStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Building;
use App\Models\DateChangeRequest;
use App\Models\Transaction;
use App\Services\BookingService;
use App\Services\DateChangeService;
use App\Services\PaymentMethods\GeideaPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class GeideaWebhookController extends Controller
{
    public function handle(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->all();

        Log::channel('geidea_webhook')->info('Geidea webhook received', $data);

        $orderId = $data['orderId'] ?? ($data['order']['orderId'] ?? null);

        if (! $orderId) {
            Log::channel('geidea_webhook')->warning('Geidea webhook missing orderId', $data);

            return response()->json(['status' => 'ignored', 'reason' => 'missing orderId']);
        }

        // التحقق من Geidea API قبل فتح الـ DB transaction (عملية شبكة طويلة)
        $geidea = new GeideaPayment;
        $orderData = $geidea->verifyPayment($orderId);

        if (! $orderData || ($orderData['order']['detailedStatus'] ?? null) !== 'Paid') {
            Log::channel('geidea_webhook')->warning('Geidea webhook: payment not confirmed by API', [
                'order_id' => $orderId,
                'api_status' => $orderData['order']['detailedStatus'] ?? null,
            ]);

            return response()->json(['status' => 'not_paid']);
        }

        // الـ critical section: قراءة + تحديث في DB transaction واحدة مع lock
        $result = DB::transaction(function () use ($orderId, $data) {
            $transaction = Transaction::where('order_id', $orderId)->lockForUpdate()->first();

            if (! $transaction) {
                $merchantRef = $data['merchantReferenceId']
                    ?? ($data['order']['merchantReferenceId'] ?? null);

                if ($merchantRef) {
                    $transaction = Transaction::where('transaction_reference', $merchantRef)->lockForUpdate()->first();
                }
            }

            if (! $transaction) {
                return ['status' => 'ignored', 'reason' => 'transaction not found'];
            }

            if ($transaction->status === 'completed') {
                return ['status' => 'already_processed'];
            }

            $transaction->status = 'completed';
            $transaction->order_id = $orderId;
            $transaction->payment_gateway_response = json_encode($data);
            $transaction->save();

            return ['status' => 'proceed', 'transaction' => $transaction];
        });

        if ($result['status'] !== 'proceed') {
            if ($result['status'] === 'ignored') {
                Log::channel('geidea_webhook')->warning('Geidea webhook: transaction not found', [
                    'order_id' => $orderId,
                ]);
            }

            return response()->json(['status' => $result['status'], 'reason' => $result['reason'] ?? null]);
        }

        $transaction = $result['transaction'];

        // Date-change surcharge payment: the underlying booking is already `approved` (it existed
        // before the date-change request), so it must be routed separately from a primary-booking
        // confirmation below — otherwise the `booking.status === 'approved'` check short-circuits
        // and the new dates are never applied even though the surcharge was paid.
        $dateChangeRequest = DateChangeRequest::where('transaction_id', $transaction->id)->first();

        if ($dateChangeRequest) {
            try {
                app(DateChangeService::class)->confirmSurchargePayment($dateChangeRequest);
            } catch (\Throwable $e) {
                Log::channel('geidea_webhook')->error('Geidea webhook: failed to apply date-change surcharge', [
                    'order_id' => $orderId,
                    'date_change_request_id' => $dateChangeRequest->id,
                    'error' => $e->getMessage(),
                ]);
                $dateChangeRequest->update(['status' => DateChangeStatus::Failed->value, 'error' => $e->getMessage()]);
            }

            return response()->json(['status' => 'ok']);
        }

        $booking = $transaction->booking;

        if (! $booking) {
            Log::channel('geidea_webhook')->warning('Geidea webhook: booking not found for transaction', [
                'order_id' => $orderId,
                'transaction_id' => $transaction->id,
            ]);

            return response()->json(['status' => 'ok']);
        }

        if ($booking->status === 'approved') {
            return response()->json(['status' => 'ok']);
        }

        app(BookingService::class)->completeBookingAfterPayment($transaction->id);

        $booking->refresh();
        $this->sendConfirmationEmails($booking);

        Log::channel('geidea_webhook')->info('Geidea webhook: booking confirmed', [
            'order_id' => $orderId,
            'transaction_id' => $transaction->id,
            'booking_id' => $booking->id,
        ]);

        return response()->json(['status' => 'ok']);
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
            Log::channel('geidea_webhook')->error('Geidea webhook: failed to send confirmation email', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
