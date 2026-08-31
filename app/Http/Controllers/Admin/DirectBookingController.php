<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DirectBookingRequest;
use App\Models\Apartment;
use App\Models\Customer;
use App\Services\DirectBookingService;
use App\Services\Pricing\PricingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class DirectBookingController extends Controller
{
    public function __construct(
        private DirectBookingService $directBookingService,
        private PricingService $pricingService,
    ) {}

    private function authorizePage(): void
    {
        if (! backpack_user()->can('direct-booking.create')) {
            abort(403, 'You do not have permission to create direct bookings.');
        }
    }

    public function create(): \Illuminate\View\View
    {
        $this->authorizePage();

        $apartments = Apartment::where('is_active', true)
            ->orderBy('name_ar')
            ->get(['id', 'name_ar', 'name_en', 'building_id', 'adults_count', 'children_count']);

        $buildings = \App\Models\Building::orderBy('name_ar')->get(['id', 'name_ar', 'name_en']);

        return view('admin.direct-booking.create', compact('apartments', 'buildings'));
    }

    public function customerSearch(Request $request): JsonResponse
    {
        $this->authorizePage();

        $term = trim((string) $request->query('q', ''));

        $customers = Customer::query()
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($q) use ($term) {
                    $q->where('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'first_name', 'last_name', 'phone', 'email']);

        // Wrap the phone in a Unicode LTR isolate (U+2066 … U+2069) so the leading "+"
        // renders on the left even next to an Arabic (RTL) name.
        return response()->json($customers->map(fn (Customer $c) => [
            'id' => $c->id,
            'text' => trim($c->first_name.' '.$c->last_name).' — '."\u{2066}".$c->phone."\u{2069}",
        ]));
    }

    public function pricePreview(Request $request): JsonResponse
    {
        $this->authorizePage();

        $validated = $request->validate([
            'apartment_id' => ['required', 'integer', 'exists:apartments,id'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
        ]);

        $apartment = Apartment::findOrFail($validated['apartment_id']);
        $checkIn = Carbon::parse($validated['check_in']);
        $checkOut = Carbon::parse($validated['check_out']);
        $nights = $checkIn->diffInDays($checkOut);

        $prices = $this->pricingService->calculate($apartment, $checkIn, $checkOut);
        $total = round((float) $prices['total'], 2);

        return response()->json([
            'ok' => true,
            'nights' => $nights,
            'total_price' => $total,
            'vat' => round($total * 15 / 115, 2),
            'suggested_final_price' => $total,
        ]);
    }

    public function store(DirectBookingRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['receipt'] = $request->file('receipt');

        try {
            $booking = $this->directBookingService->createManualBooking($data);
        } catch (ValidationException $e) {
            // Availability / guest-count conflicts — the apartment or dates are unusable.
            return response()->json([
                'ok' => false,
                'message' => collect($e->errors())->flatten()->first() ?? __('api.general_error'),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            // OwnerRez push (or any other) failure: the transaction rolled back, so no local
            // booking exists. Keep the form open so staff can retry with the same data.
            Log::error('Direct booking creation failed', [
                'apartment_id' => $data['apartment_id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => __('api.ownerrez_sync_failed_retry'),
                'detail' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'message' => __('api.booking_created_successfully'),
            'booking_id' => $booking->id,
            'redirect' => backpack_url('booking/'.$booking->id.'/show'),
        ]);
    }
}
