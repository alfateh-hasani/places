<?php

namespace App\Http\Controllers\Front;

use App\Filters\FilterFactory;
use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\City;
use App\Services\OwnerRez\OwnerRezSyncService;
use App\Services\Pricing\PricingService;
use Artesaos\SEOTools\Facades\SEOTools;
use Carbon\Carbon;
use Config;
use Illuminate\Http\Request;

class ApartmentController extends Controller
{
    protected Apartment $apartment;

    protected PricingService $pricing;

    protected OwnerRezSyncService $ownerRezSync;

    public function __construct(Apartment $apartment, PricingService $pricing, OwnerRezSyncService $ownerRezSync)
    {
        $this->apartment = $apartment;
        $this->pricing = $pricing;
        $this->ownerRezSync = $ownerRezSync;
    }

    public function index(Request $request)
    {
        // تاريخي افتراضي للحجز لحساب التسعير (يمكنّ تعديلهما عبر الريكوست لو رغبت لاحقاً)
        $checkIn = Carbon::today();
        $checkOut = Carbon::tomorrow();

        $apartments = Apartment::where('is_active', true)
            ->paginate(12);

        // دمج أسعار الفترة في كل كائن
        $apartments->getCollection()->transform(function (Apartment $apt) use ($checkIn, $checkOut) {
            $apt->priceInfo = $this->pricing->calculate($apt, $checkIn, $checkOut);

            return $apt;
        });

        return view('apartment.index', compact('apartments'));
    }

    public function show(Request $request, $slug)
    {
        $apartment = Apartment::with([
            'building.city',
            'reviews',
            'features',
            'bookings' => function ($query) {
                $query->where('check_out', '>=', now()->startOfDay())->whereNotIn('status', ['canceled', 'customer_canceled']);
            },
            'policy',
            'ownerrezMapping',
        ])
            ->where('is_active', true)
            ->where('slug', $slug)
            ->firstOrFail();

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

        // احسب سعر ليلة واحدة
        $priceInfo = $this->pricing->calculate(
            $apartment,
            Carbon::parse($started_day),
            Carbon::parse($next_started_day)
        );

        // إعداد SEO
        $seo_title = $apartment->ml('seo_title').' | '.Config::get('settings.seo_title_'.app()->getLocale());
        $seo_description = $apartment->ml('seo_description');
        $url = route('apartments.show', $apartment->slug);
        $this->generateSeo($seo_title, $seo_description, $url);

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
        // التواريخ إن وجدت
        $checkIn = $request->filled('check_in')
            ? Carbon::parse($request->check_in)
            : Carbon::today();
        $checkOut = $request->filled('check_out')
            ? Carbon::parse($request->check_out)
            : Carbon::today()->addDay();

        // بناء الفلاتر الأصلي
        $filters = array_filter([
            'city_id' => $request->city_id,
            'check_out' => $request->check_out,
            'check_in' => $request->check_in,
            'adults_count' => $request->adults_count,
            'children_count' => $request->children_count,
            'max_price' => $request->price_range,
            'max_area' => $request->area_range,
            'num_rooms' => $request->rooms,
            'num_beds' => $request->beds,
        ], fn ($v) => ! is_null($v) && $v !== '');

        $query = $this->apartment::query();

        foreach ($filters as $key => $val) {
            if ($key === 'city_id') {
                $query->whereHas('building', fn ($q) => $q->where('city_id', $val));

                continue;
            }
            if (in_array($key, ['num_rooms', 'num_beds']) && is_array($val)) {
                $query->whereIn($key, $val);

                continue;
            }
            if ($key === 'max_price') {
                $query->where('price', '<=', $val);

                continue;
            }
            if ($key === 'max_area') {
                $query->where('area', '<=', $val);

                continue;
            }
            if ($key === 'check_in' || $key === 'check_out') {
                if (! isset($filters['check_in'], $filters['check_out'])) {
                    continue;
                }
                $ci = Carbon::parse($filters['check_in'])->format('Y-m-d');
                $co = Carbon::parse($filters['check_out'])->format('Y-m-d');
                $query->whereDoesntHave('bookings', fn ($q) => $q->where('check_in', '<', $co)->where('check_out', '>', $ci)
                );
                break;
            }

            $query->where('is_active', true);
            $handler = FilterFactory::make($key);
            $query = $handler->apply($query, $val);
        }

        $apartments = $query->latest()->paginate(8);

        // دمج التسعير الجديد في كل شقة
        $apartments->getCollection()->transform(fn (Apartment $apt) => tap($apt)->offsetSet(
            'priceInfo',
            $this->pricing->calculate($apt, $checkIn, $checkOut)
        ));

        $data = [
            'cities' => City::orderBy('sort_order')->withCount('apartments')->get(),
            'apartments' => $apartments,
            'filter_keys' => $this->prepareFilterKeys(),
        ];

        $seo_title = __('site.search').' | '.Config::get('settings.seo_title_'.app()->getLocale());
        $seo_description = Config::get('settings.seo_description_'.app()->getLocale());
        $url = route('apartments.search');
        $this->generateSeo($seo_title, $seo_description, $url);

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
        ];
    }

    private function generateSeo($seo_title, $seo_description, $url)
    {
        SEOTools::setTitle($seo_title);
        SEOTools::setDescription($seo_description);
        SEOTools::opengraph()->setUrl($url);
        SEOTools::setCanonical($url);
        SEOTools::opengraph()->addProperty('type', 'articles');
    }

    /**
     * API endpoint لإرجاع التواريخ المحجوزة من الكاش (للتحديث التلقائي للتقويم)
     */
    public function blockedDates(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $apartment = Apartment::with('ownerrezMapping')->where('is_active', true)->findOrFail($id);

        $bookedDays = $apartment->bookings()
            ->where('check_out', '>=', now()->startOfDay())
            ->whereNotIn('status', ['canceled', 'customer_canceled'])
            ->get()
            ->map(fn ($b) => [
                'check_in' => $b->check_in->format('Y-m-d'),
                'check_out' => $b->check_out->format('Y-m-d'),
            ])->toArray();

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
     * API endpoint لحساب السعر بناءً على التواريخ المحددة
     */
    public function calculatePrice(Request $request, $apartmentId)
    {
        $request->validate([
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
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
        $building = Building::where('slug', $slug)->firstOrFail();
        $apartments = Apartment::where('building_id', $building->id)->where('is_active', true)->paginate(12);

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

        $seo_title = $building->ml('seo_title').' | '.Config::get('settings.seo_title_'.app()->getLocale());
        $seo_description = $building->ml('seo_description');
        $url = route('buliding.show', $building->slug);
        $this->generateSeo($seo_title, $seo_description, $url);

        return view('building.show', $data);
    }
}
