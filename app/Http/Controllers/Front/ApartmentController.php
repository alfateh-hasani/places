<?php

namespace App\Http\Controllers\Front;

use App\Enums\BookingStatus;
use App\Enums\DateChangeStatus;
use App\Filters\FilterFactory;
use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\City;
use App\Models\DateChangeRequest;
use App\Models\SlugRedirect;
use App\Rules\MaxStay;
use App\Services\OwnerRez\OwnerRezSyncService;
use App\Services\Pricing\PricingService;
use App\Services\Seo\SeoService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class ApartmentController extends Controller
{
    protected Apartment $apartment;

    protected PricingService $pricing;

    protected OwnerRezSyncService $ownerRezSync;

    protected SeoService $seo;

    public function __construct(Apartment $apartment, PricingService $pricing, OwnerRezSyncService $ownerRezSync, SeoService $seo)
    {
        $this->apartment = $apartment;
        $this->pricing = $pricing;
        $this->ownerRezSync = $ownerRezSync;
        $this->seo = $seo;
    }

    public function index(Request $request)
    {
        // /apartments is an entry point (favorites empty-state CTA, sitemap). Send it
        // to the filter/listing page defaulted to Riyadh with no date window, so it
        // shows every bookable unit there instead of a bare, date-scoped list.
        $riyadh = City::query()
            ->where('name_ar', 'الرياض')
            ->orWhere('name_en', 'Riyadh')
            ->value('slug');

        return redirect()->route('apartments.search', array_filter(['city' => $riyadh]), 301);
    }

    public function show(Request $request, $slug)
    {
        $apartment = Apartment::with([
            'building.city',
            'reviews',
            'features',
            'bookings' => function ($query) {
                $query->where('check_out', '>=', now()->startOfDay())->whereNotIn('status', [BookingStatus::Canceled->value]);
            },
            'policy',
            'ownerrezMapping',
        ])
            ->bookable()
            ->where('slug', $slug)
            ->first();

        if (! $apartment) {
            if ($redirect = $this->redirectFromOldSlug(Apartment::class, $slug, 'apartments.show')) {
                return $redirect;
            }
            abort(404);
        }

        // MySQL matches slugs case-insensitively; only the canonical spelling may answer 200.
        if ($slug !== $apartment->slug) {
            return redirect()->route('apartments.show', $apartment->slug, 301);
        }

        // تحديد أول فترة للحجز
        $lastBookedDate = $apartment->bookings->sortBy('check_out')->first()?->check_out;
        if ($lastBookedDate) {
            $started_day = $lastBookedDate->copy()->addDay()->format('Y-m-d');
            $next_started_day = $lastBookedDate->copy()->addDays(2)->format('Y-m-d');
        } else {
            $started_day = now()->format('Y-m-d');
            $next_started_day = now()->addDay()->format('Y-m-d');
        }

        $booked_days = $apartment->bookings->map(fn ($b) => [
            'check_in' => $b->check_in->format('Y-m-d'),
            'check_out' => $b->check_out->format('Y-m-d'),
        ])->toArray();

        // نوافذ طلبات تعديل التواريخ المفتوحة تحجب أيضاً (لتطابق checkAvailability)
        $booked_days = array_merge($booked_days, $this->pendingDateChangeWindows($apartment));

        // طبقة ثانية: قفل تواريخ OwnerRez من الكاش الدائم (stale-while-revalidate)
        $mapping = $apartment->ownerrezMapping;
        if ($mapping && config('ownerrez.availability.enabled')) {
            try {
                $ownerRezDays = $this->ownerRezSync->getCalendarBookings(
                    $mapping->ownerrez_property_id
                )->map(fn ($b) => [
                    'check_in' => $b['arrival'],
                    'check_out' => $b['departure'],
                ])->values()->toArray();

                $booked_days = array_merge($booked_days, $ownerRezDays);
            } catch (\Exception $e) {
                \Log::warning('OwnerRez calendar fetch failed', [
                    'apartment_id' => $apartment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // احسب سعر ليلة واحدة بناءً على اليوم الحالي دائماً
        $priceInfo = $this->pricing->calculate(
            $apartment,
            Carbon::today(),
            Carbon::tomorrow()
        );

        $url = route('apartments.show', $apartment->slug);
        $building = $apartment->building;
        $city = $building?->city;

        $this->seo
            ->page(
                $apartment->ml('seo_title') ?: $apartment->ml('name'),
                $apartment->ml('seo_description') ?: $apartment->ml('description'),
                $url,
                $apartment->image_view ?: null,
                'product'
            )
            ->breadcrumbs(array_values(array_filter([
                $city ? ['name' => $city->ml('name'), 'url' => route('by-city', $city->slug)] : null,
                ($building && $building->is_active) ? ['name' => $building->ml('name'), 'url' => route('building.details', $building->slug)] : null,
                ['name' => $apartment->ml('name'), 'url' => $url],
            ])))
            ->apartment($apartment, $url, $priceInfo);

        return view('apartment.show', [
            'apartment' => $apartment,
            'apartment_id' => $apartment->id,
            'started_day' => $started_day,
            'next_started_day' => $next_started_day,
            'booked_days' => $booked_days,
            'priceInfo' => $priceInfo,    // ← إضافة
            'request' => $request,
        ]);
    }

    public function search(Request $request)
    {
        // Parse the requested window defensively — a malformed/mangled date
        // (e.g. a corrupt "10/09/0191") must fall back to today→tomorrow instead
        // of producing a ~year-long span (huge prices) and broken availability.
        $checkIn = $this->safeSearchDate($request->check_in, Carbon::today());
        $checkOut = $this->safeSearchDate($request->check_out, $checkIn->copy()->addDay());

        if ($checkIn->lt(Carbon::today())) {
            $checkIn = Carbon::today();
        }
        if ($checkOut->lte($checkIn)) {
            $checkOut = $checkIn->copy()->addDay();
        }

        // Only filter by date availability when the customer actually picked a window.
        // With no dates, return every bookable unit (the today→tomorrow window above is
        // still used to price the cards); dates only narrow results once chosen.
        $datesRequested = $request->filled('check_in') && $request->filled('check_out');

        // Price is filtered in PHP against the same per-night price shown on the
        // card (dynamic day/seasonal pricing), so the raw `price` column isn't used.
        $priceMin = is_numeric($request->price_min) ? (float) $request->price_min : null;
        $priceMax = is_numeric($request->price_max) ? (float) $request->price_max : null;

        // Public links use the city slug (?city=riyadh); the form still posts city_id.
        $cityId = $request->city_id;
        if (! $cityId && $request->filled('city')) {
            $cityId = City::where('slug', Str::slug((string) $request->city))->value('id');
        }

        // بناء الفلاتر الأصلي
        $filters = array_filter([
            'city_id' => $cityId,
            'adults_count' => $request->adults,
            'children_count' => $request->children,
            'min_area' => $request->area_min,
            'max_area' => $request->area_max,
            'num_rooms' => $request->rooms,
            'num_beds' => $request->beds,
            'rate' => $request->rate,
            'building_id' => $request->building_id,
        ], fn ($v) => ! is_null($v) && $v !== '');

        $query = $this->apartment::query()->bookable();

        // When a date window is requested, hide units booked for it so only available
        // ones show. Canceled bookings don't block. With no dates, skip this entirely.
        if ($datesRequested) {
            $query->whereDoesntHave('bookings', function ($q) use ($checkIn, $checkOut) {
                $q->where('check_in', '<', $checkOut->format('Y-m-d'))
                    ->where('check_out', '>', $checkIn->format('Y-m-d'))
                    ->whereNotIn('status', [BookingStatus::Canceled->value]);
            });
        }

        foreach ($filters as $key => $val) {
            if ($key === 'city_id') {
                $query->whereHas('building', fn ($q) => $q->where('city_id', $val));

                continue;
            }
            if (in_array($key, ['num_rooms', 'num_beds']) && is_array($val)) {
                $query->whereIn($key, $val);

                continue;
            }
            if (in_array($key, ['adults_count', 'children_count'])) {
                $query->where($key, '>=', (int) $val);

                continue;
            }
            if ($key === 'rate') {
                // Match the rating shown on the card, which is number_format(AVG, 1).
                // Rounding here (not RateFilter, shared with the mobile API) keeps the
                // web filter consistent with the displayed value: a unit shown as "4.0"
                // (avg 3.95+) passes the "4 & up" filter.
                $rate = is_array($val) ? (float) min($val) : (float) $val;
                $query->whereIn('id', function ($sub) use ($rate) {
                    $sub->select('apartment_id')
                        ->from('reviews')
                        ->groupBy('apartment_id')
                        ->havingRaw('ROUND(AVG(rating), 1) >= ?', [$rate]);
                });

                continue;
            }
            if ($key === 'min_area') {
                $query->where('area', '>=', $val);

                continue;
            }
            if ($key === 'max_area') {
                $query->where('area', '<=', $val);

                continue;
            }
            $handler = FilterFactory::make($key);
            $query = $handler->apply($query, $val);
        }

        // Exclude OwnerRez-managed units booked for the window. Their availability
        // lives in the OwnerRez calendar (cached), not the local bookings table, so
        // the SQL filter above can't see it.
        $matched = $query->with('ownerrezMapping')->latest()->get();

        // Price each matched unit for the requested window up front, so the price
        // filter and the card display use the SAME per-night price.
        $matched->each(fn (Apartment $apt) => $apt->offsetSet(
            'priceInfo',
            $this->pricing->calculate($apt, $checkIn, $checkOut)
        ));

        if ($datesRequested && config('ownerrez.availability.enabled')) {
            $ci = $checkIn->format('Y-m-d');
            $co = $checkOut->format('Y-m-d');
            $matched = $matched->reject(function (Apartment $apt) use ($ci, $co) {
                $mapping = $apt->ownerrezMapping;
                if (! $mapping) {
                    return false;
                }

                return $this->ownerRezSync->getCalendarBookings($mapping->ownerrez_property_id)
                    ->contains(fn ($b) => ($b['arrival'] ?? '') < $co && ($b['departure'] ?? '') > $ci);
            });
        }

        // Filter by the per-night price actually shown on the card (dynamic pricing),
        // not the raw `price` column — so "min 840" never shows an 830 unit.
        if ($priceMin !== null) {
            $matched = $matched->filter(fn (Apartment $apt) => ($apt->priceInfo['one_night_price'] ?? $apt->price) >= $priceMin);
        }
        if ($priceMax !== null) {
            $matched = $matched->filter(fn (Apartment $apt) => ($apt->priceInfo['one_night_price'] ?? $apt->price) <= $priceMax);
        }
        $matched = $matched->values();

        $perPage = 8;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $apartments = new LengthAwarePaginator(
            $matched->forPage($page, $perPage)->values(),
            $matched->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->except('page')]
        );

        $data = [
            'cities' => City::orderBy('sort_order')->withCount('apartments')->get(),
            'apartments' => $apartments,
            'filter_keys' => $this->prepareFilterKeys(),
        ];

        $this->seo
            ->page(__('site.search'), null, route('apartments.search'))
            ->noIndex();

        return view('apartment.list', $data);
    }

    protected function prepareFilterKeys()
    {
        return [
            'min_price' => $this->apartment->min('price') ?? 0,
            'max_price' => $this->apartment->max('price') ?? 0,
            'rooms_options' => $this->apartment->pluck('num_rooms')->unique()->sort()->values()->toArray(),
            'beds_options' => $this->apartment->pluck('num_beds')->unique()->sort()->values()->toArray(),
            'max_area' => $this->apartment->max('area') ?? 0,
            'min_area' => $this->apartment->min('area') ?? 0,
            'bathrooms_options' => $this->apartment->pluck('bathrooms_count')->unique()->sort()->values()->toArray(),
            'buildings_options' => Building::when(
                request('city_id'),
                fn ($q, $cityId) => $q->where('city_id', $cityId)
            )
                ->orderBy('name_'.app()->getLocale())
                ->get()
                ->mapWithKeys(fn (Building $b) => [$b->id => $b->ml('name')])
                ->toArray(),
        ];
    }

    /**
     * Parse a user-supplied search date, rejecting empty/unparseable/mangled
     * values (e.g. a corrupt year) and returning $default instead.
     */
    private function safeSearchDate($value, Carbon $default): Carbon
    {
        if (empty($value)) {
            return $default;
        }

        try {
            $date = Carbon::parse($value);
        } catch (\Throwable $e) {
            return $default;
        }

        if ($date->year < 2000 || $date->year > 2100) {
            return $default;
        }

        return $date;
    }

    /**
     * A listing whose slug was renamed 404s under its old URL unless we forward it here —
     * this is what keeps that old link's ranking/shares alive instead of losing them overnight.
     * MySQL matches old_slug case-insensitively, so a case-only rename ("S405" → "s405") would
     * point a hidden listing back at its own URL; that must 404 rather than redirect forever.
     */
    private function redirectFromOldSlug(string $type, string $oldSlug, string $routeName): ?RedirectResponse
    {
        $current = SlugRedirect::where('redirectable_type', $type)
            ->where('old_slug', $oldSlug)
            ->first()
            ?->redirectable;

        if (! $current || Str::lower($current->slug) === Str::lower($oldSlug)) {
            return null;
        }

        return redirect()->route($routeName, $current->slug, 301);
    }

    /**
     * API endpoint لإرجاع التواريخ المحجوزة من الكاش (للتحديث التلقائي للتقويم)
     */
    public function blockedDates(Request $request, int $id): JsonResponse
    {
        $apartment = Apartment::with('ownerrezMapping')->bookable()->findOrFail($id);

        $bookedDays = $apartment->bookings()
            ->where('check_out', '>=', now()->startOfDay())
            ->whereNotIn('status', [BookingStatus::Canceled->value])
            ->get()
            ->map(fn ($b) => [
                'check_in' => $b->check_in->format('Y-m-d'),
                'check_out' => $b->check_out->format('Y-m-d'),
            ])->toArray();

        // نوافذ طلبات تعديل التواريخ المفتوحة تحجب أيضاً (لتطابق checkAvailability)
        $bookedDays = array_merge($bookedDays, $this->pendingDateChangeWindows($apartment));

        $mapping = $apartment->ownerrezMapping;
        if ($mapping && config('ownerrez.availability.enabled')) {
            $ownerRezDays = $this->ownerRezSync->getCalendarBookings($mapping->ownerrez_property_id)
                ->map(fn ($b) => [
                    'check_in' => $b['arrival'],
                    'check_out' => $b['departure'],
                ])->values()->toArray();

            $bookedDays = array_merge($bookedDays, $ownerRezDays);
        }

        return response()->json([
            'booked_days' => $bookedDays,
        ]);
    }

    /**
     * Open date-change requests hold their requested window (mirrors BookingService::checkAvailability
     * step 1b) — so the calendar must show them blocked, otherwise a date looks free but booking it fails.
     *
     * @return array<int, array{check_in:string, check_out:string}>
     */
    private function pendingDateChangeWindows(Apartment $apartment): array
    {
        return DateChangeRequest::query()
            ->whereIn('status', DateChangeStatus::openValues())
            ->whereHas('booking', fn ($q) => $q->where('apartment_id', $apartment->id))
            ->where('new_check_out', '>=', now()->startOfDay())
            ->get()
            ->map(fn ($r) => [
                'check_in' => $r->new_check_in->format('Y-m-d'),
                'check_out' => $r->new_check_out->format('Y-m-d'),
            ])->toArray();
    }

    /**
     * API endpoint لحساب السعر بناءً على التواريخ المحددة
     */
    public function calculatePrice(Request $request, $apartmentId)
    {
        $request->validate([
            'check_in' => 'required|date',
            'check_out' => ['required', 'date', 'after:check_in', new MaxStay],
        ]);

        $apartment = Apartment::findOrFail($apartmentId);
        $checkIn = Carbon::parse($request->check_in);
        $checkOut = Carbon::parse($request->check_out);

        // استخدام PricingService لحساب السعر الفعلي
        $priceInfo = $this->pricing->calculate($apartment, $checkIn, $checkOut);

        $nights = $checkIn->diffInDays($checkOut);

        return response()->json([
            'success' => true,
            'nights' => $nights,
            'total' => $priceInfo['total'],           // السعر الكلي (شامل الضريبة)
            'one_night_price' => $priceInfo['one_night_price'], // متوسط سعر الليلة
            'discount' => $priceInfo['discount'],     // خصم الإقامة الطويلة
            'vat' => $priceInfo['vat'],              // الضريبة
            'net' => $priceInfo['net'],              // السعر بدون ضريبة
        ]);
    }

    public function getApartmentBuliding($slug)
    {
        $building = Building::where('slug', $slug)->first();

        if (! $building) {
            if ($redirect = $this->redirectFromOldSlug(Building::class, $slug, 'building.details')) {
                return $redirect;
            }
            abort(404);
        }

        // An inactive building is hidden from the public site entirely — its
        // landing page 404s rather than showing an empty (or stale) unit list.
        if (! $building->is_active) {
            abort(404);
        }

        if ($slug !== $building->slug) {
            return redirect()->route('building.details', $building->slug, 301);
        }

        $apartments = Apartment::where('building_id', $building->id)->active()->paginate(12);

        $checkIn = Carbon::today();
        $checkOut = Carbon::tomorrow();

        $apartments->getCollection()->transform(fn (Apartment $apt) => tap($apt)->offsetSet(
            'priceInfo',
            $this->pricing->calculate($apt, $checkIn, $checkOut)
        ));

        $data = [
            'building' => $building,
            'apartments' => $apartments,
            'filter_keys' => $this->prepareFilterKeys(),
            'cities' => City::orderBy('sort_order')->withCount('apartments')->get(),
        ];

        $url = route('building.details', $building->slug);
        $city = $building->city;

        $this->seo
            ->page(
                $building->ml('seo_title') ?: $building->ml('name'),
                $building->ml('seo_description') ?: $building->ml('address'),
                $url,
                $building->image ?: null
            )
            ->breadcrumbs(array_values(array_filter([
                $city ? ['name' => $city->ml('name'), 'url' => route('by-city', $city->slug)] : null,
                ['name' => $building->ml('name'), 'url' => $url],
            ])))
            ->building($building, $url, $apartments->total());

        return view('building.show', $data);
    }
}
