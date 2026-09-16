<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Booking;
use App\Models\BookingUnitTransfer;
use App\Models\Building;
use App\Services\BookingUnitTransfer\BookingUnitTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Staff-facing endpoints for moving a booking to another apartment. Intentionally has NO
 * dedicated permission — anyone who can view bookings can start a transfer (per product
 * decision); state changes live in {@see BookingUnitTransferService}.
 */
class BookingUnitTransferController extends Controller
{
    public function __construct(private BookingUnitTransferService $transfers) {}

    /** Anyone who can see the bookings list can transfer — no separate permission. */
    private function authorizeAccess(): void
    {
        if (! backpack_user()->can('booking.list')) {
            abort(403, 'Unauthorized Access');
        }
    }

    /** The transfer form: pick an active destination unit and preview the price impact. */
    public function create(int $id): View
    {
        $this->authorizeAccess();

        $booking = Booking::with('apartment.building')->findOrFail($id);

        // Only active apartments (excluding the current one). Buildings are derived from these,
        // so only buildings that actually have an active apartment appear ("active buildings").
        $apartments = Apartment::where('is_active', true)
            ->where('id', '!=', $booking->apartment_id)
            ->orderBy('name_ar')
            ->get(['id', 'name_ar', 'name_en', 'building_id', 'adults_count', 'children_count']);

        $buildings = Building::whereIn('id', $apartments->pluck('building_id')->unique())
            ->orderBy('name_ar')
            ->get(['id', 'name_ar', 'name_en']);

        return view('admin.unit-transfer.create', compact('booking', 'apartments', 'buildings'));
    }

    /** AJAX: availability + price impact for a chosen destination (same dates). */
    public function pricePreview(Request $request, int $id): JsonResponse
    {
        $this->authorizeAccess();

        $booking = Booking::findOrFail($id);
        $validated = $request->validate([
            'apartment_id' => ['required', 'integer', 'exists:apartments,id'],
        ]);

        try {
            $quote = $this->transfers->quote($booking, (int) $validated['apartment_id']);
        } catch (ValidationException $e) {
            return response()->json([
                'ok' => false,
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'direction' => $quote->direction->value,
            'original_price' => $quote->originalPrice,
            'new_price' => $quote->newPrice,
            'price_delta' => $quote->priceDelta,
            'refund_amount' => $quote->refundAmount,
        ]);
    }

    /** Initiate the transfer — creates a request awaiting customer confirmation, holds the unit. */
    public function store(Request $request, int $id): RedirectResponse
    {
        $this->authorizeAccess();

        $booking = Booking::findOrFail($id);
        $validated = $request->validate([
            'apartment_id' => ['required', 'integer', 'exists:apartments,id'],
        ]);

        try {
            $this->transfers->initiate($booking, (int) $validated['apartment_id']);
        } catch (ValidationException $e) {
            \Alert::error(collect($e->errors())->flatten()->first())->flash();

            return back();
        }

        \Alert::success(__('cms.unit_transfer_initiated'))->flash();

        return redirect(backpack_url('booking/'.$booking->id.'/show'));
    }

    /** Staff withdraw a pending transfer (releases the held destination). */
    public function cancel(int $transfer): RedirectResponse
    {
        $this->authorizeAccess();

        $record = BookingUnitTransfer::findOrFail($transfer);

        try {
            $this->transfers->cancel($record);
            \Alert::success(__('cms.unit_transfer_canceled'))->flash();
        } catch (ValidationException $e) {
            \Alert::error(collect($e->errors())->flatten()->first())->flash();
        }

        return back();
    }

    /** Re-attempt a transfer that failed to apply (e.g. a transient deadlock). */
    public function retry(int $transfer): RedirectResponse
    {
        $this->authorizeAccess();

        $record = BookingUnitTransfer::findOrFail($transfer);

        try {
            $this->transfers->retry($record);
            \Alert::success(__('cms.unit_transfer_applied'))->flash();
        } catch (ValidationException $e) {
            \Alert::error(collect($e->errors())->flatten()->first())->flash();
        } catch (\Throwable $e) {
            \Alert::error(__('cms.unit_transfer_retry_failed').': '.$e->getMessage())->flash();
        }

        return back();
    }

    /** Refund the difference for a cheaper-unit move (full or partial). */
    public function refund(Request $request, int $transfer): RedirectResponse
    {
        $this->authorizeAccess();

        $record = BookingUnitTransfer::findOrFail($transfer);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'lte:'.$record->refundableAmount()],
        ], [], ['amount' => __('cms.refund_amount')]);

        try {
            $outcome = $this->transfers->processRefund($record, (float) $validated['amount']);
        } catch (ValidationException $e) {
            \Alert::error(collect($e->errors())->flatten()->first())->flash();

            return back();
        }

        match ($outcome) {
            'approved' => \Alert::success(__('cms.refund_done'))->flash(),
            'processing' => \Alert::warning(__('cms.refund_processing_flash'))->flash(),
            default => \Alert::error(__('cms.refund_failed'))->flash(),
        };

        return back();
    }

    /** Re-attempt the automatic parking (auto-free) of the OLD OwnerRez booking. */
    public function retryPark(int $transfer): RedirectResponse
    {
        $this->authorizeAccess();

        $record = BookingUnitTransfer::findOrFail($transfer);

        if ($this->transfers->retryParkOldBooking($record)) {
            \Alert::success(__('cms.transfer_old_ownerrez_parked'))->flash();
        } else {
            \Alert::warning(__('cms.transfer_old_ownerrez_park_failed'))->flash();
        }

        return back();
    }

    /** Staff mark the OLD OwnerRez booking as cancelled by hand (after using the deeplink). */
    public function markOwnerRezCancelled(int $transfer): RedirectResponse
    {
        $this->authorizeAccess();

        $record = BookingUnitTransfer::findOrFail($transfer);
        $this->transfers->markOldOwnerRezCancelled($record);

        \Alert::success(__('cms.unit_transfer_old_ownerrez_marked'))->flash();

        return back();
    }
}
