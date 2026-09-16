<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\Bookings\BookingCancellationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Staff actions for the two-step cancellation → refund flow, surfaced directly on
 * the booking (list row + detail page) via the "Manage cancellation" modal. All
 * state transitions live in {@see BookingCancellationService}; this controller only
 * authorizes, guards the current state, and reports the outcome.
 */
class BookingCancellationController extends Controller
{
    public function __construct(private BookingCancellationService $cancellations) {}

    /**
     * Finalize a cancellation locally (frees the unit). For an unmapped unit this is
     * the normal path; for an OwnerRez-linked unit it's the force-cancel fallback used
     * when the webhook never arrives (local-only — OwnerRez keeps holding the booking).
     */
    public function cancelLocal(int $id): RedirectResponse
    {
        $booking = $this->authorizedBooking($id);

        if (! $booking->isCancellationRequested() || $booking->refund_status !== 'pending') {
            \Alert::error(__('cms.invalid_booking_status'))->flash();

            return back();
        }

        $this->cancellations->finalizeLocalCancellation($booking);

        \Alert::success(__('cms.booking_canceled_now_refund'))->flash();

        return back();
    }

    /**
     * Process the refund after the cancellation is finalized — full or partial
     * (0 < amount ≤ full price).
     */
    public function refund(int $id, Request $request): RedirectResponse
    {
        $booking = $this->authorizedBooking($id);

        if (! $booking->isCanceled() || $booking->refund_status !== 'pending') {
            \Alert::error(__('cms.invalid_booking_status'))->flash();

            return back();
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'lte:'.(float) $booking->final_price],
        ], [], ['amount' => __('cms.refund_amount')]);

        $outcome = $this->cancellations->refund($booking, (float) $validated['amount']);

        $fresh = $booking->fresh();
        match ($outcome) {
            'approved' => \Alert::success(__('cms.refund_done'))->flash(),
            'processing' => \Alert::warning(__('cms.refund_processing_flash'))->flash(),
            default => \Alert::error(__('cms.refund_failed').': '.($fresh->refund_error ?: ''))->flash(),
        };

        return back();
    }

    /**
     * Reject the cancellation request and reinstate the booking (safe: the unit was
     * held throughout, so reinstating cannot double-book).
     */
    public function reject(int $id): RedirectResponse
    {
        $booking = $this->authorizedBooking($id);

        if (! $booking->isCancellationRequested() || $booking->refund_status !== 'pending') {
            \Alert::error(__('cms.invalid_booking_status'))->flash();

            return back();
        }

        $this->cancellations->reject($booking);

        \Alert::success(__('cms.refund_rejected_successfully'))->flash();

        return back();
    }

    private function authorizedBooking(int $id): Booking
    {
        if (! backpack_user()->can('booking.changeStatus')) {
            abort(403, 'Unauthorized Access');
        }

        return Booking::findOrFail($id);
    }
}
