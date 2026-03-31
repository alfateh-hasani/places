<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Building;
use App\Models\Transaction;
use App\Services\BookingService;
use App\Services\PaymentMethods\GeideaPayment;
use Illuminate\Http\Request;
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

        // البحث عن Transaction بواسطة order_id أو merchant reference
        $transaction = Transaction::where('order_id', $orderId)->first();

        if (! $transaction) {
            $merchantRef = $data['merchantReferenceId']
                ?? ($data['order']['merchantReferenceId'] ?? null);

            if ($merchantRef) {
                $transaction = Transaction::where('transaction_reference', $merchantRef)->first();
            }
        }

        if (! $transaction) {
            Log::channel('geidea_webhook')->warning('Geidea webhook: transaction not found', [
                'order_id' => $orderId,
            ]);

            return response()->json(['status' => 'ignored', 'reason' => 'transaction not found']);
        }

        // إذا العملية مكتملة مسبقاً، لا نكرر المعالجة
        if ($transaction->status === 'completed') {
            return response()->json(['status' => 'already_processed']);
        }

        // التحقق من Geidea API مباشرة
        $geidea = new GeideaPayment;
        $orderData = $geidea->verifyPayment($orderId);

        if (! $orderData || ($orderData['order']['detailedStatus'] ?? null) !== 'Paid') {
            Log::channel('geidea_webhook')->warning('Geidea webhook: payment not confirmed by API', [
                'order_id' => $orderId,
                'transaction_id' => $transaction->id,
                'api_status' => $orderData['order']['detailedStatus'] ?? null,
            ]);

            return response()->json(['status' => 'not_paid']);
        }

        // تحديث Transaction
        $transaction->status = 'completed';
        $transaction->order_id = $orderId;
        $transaction->payment_gateway_response = json_encode($data);
        $transaction->save();

        // تأكيد الحجز
        $booking = $transaction->booking;

        if (! $booking || $booking->status === 'approved') {
            return response()->json(['status' => 'ok']);
        }

        app(BookingService::class)->completeBookingAfterPayment($transaction->id);

        // إرسال الإيميلات
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
