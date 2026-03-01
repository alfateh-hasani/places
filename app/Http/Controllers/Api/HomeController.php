<?php

namespace App\Http\Controllers\Api;

use App\Filters\FilterFactory;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApartmentResource;
use App\Http\Resources\BuildingResource;
use App\Http\Resources\CityResource;
use App\Http\Resources\PageResource;
use App\Http\Resources\ReviewResource;
use App\Http\Resources\SliderResource;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\City;
use App\Models\ContactUs;
use App\Models\Page;
use App\Models\Review;
use App\Models\SliderApp;
use App\Services\OwnerRez\OwnerRezSyncService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    protected $sliderApp;

    protected $city;

    protected $apartment;

    protected $building;

    protected OwnerRezSyncService $ownerRezSync;

    public function __construct(SliderApp $sliderApp, City $city,
        Apartment $apartment, Building $building,
        OwnerRezSyncService $ownerRezSync)
    {
        $this->sliderApp = $sliderApp;
        $this->city = $city;
        $this->apartment = $apartment;
        $this->building = $building;
        $this->ownerRezSync = $ownerRezSync;
    }

    public function index()
    {
        $sliders = $this->sliderApp->get();
        $this->data['sliders'] = SliderResource::collection($sliders);
        $cities = $this->city->orderBy('sort_order')->whereHas('apartments')->get();
        $this->data['cities'] = []; // CityResource::collection($cities);
        $cities = $this->city->with('buildings')->whereHas('buildings')->get();
        $this->data['cities_with_building'] = CityResource::collection($cities);
        $apartments = $this->apartment->with('building.city')->where('is_active', true)->latest()->limit(5)->get();
        $this->data['apartments'] = ApartmentResource::collection($apartments);
        $buildings = $this->building->orderBy('sort_order', 'asc')->get();
        $this->data['buildings'] = BuildingResource::collection($buildings);
        $user = \Auth::guard('api')->user();
        $this->data['user_name'] = $user?->first_name.' '.$user?->last_name;
        $this->data['filter_keys'] = [
            'min_price' => $this->apartment->min('price'),
            'max_price' => $this->apartment->max('price'),
            'max_rooms' => $this->apartment->max('num_rooms'),
            'max_area' => $this->apartment->max('area'),
            'min_area' => $this->apartment->min('area'),
            'max_beds' => $this->apartment->max('num_beds'),
            'max_bathrooms' => 6, // $this->apartment->max('num_bathrooms'), // not exist in db
        ];

        if ($user && $user->hasMedia('profile')) {
            $this->data['avatar'] = $user->getFirstMediaUrl('profile');
            $this->data['email'] = $user?->email;
        } else {
            $this->data['avatar'] = null;
            $this->data['email'] = null;
        }

        return $this->successResponse($this->data);
    }

    // list all apartments

    public function getListingApartments()
    {
        $apartments = $this->apartment->with('building.city')->where('is_active', 1)->latest()->paginate(20);
        $this->data['apartments'] = ApartmentResource::collection($apartments);
        $this->data['pagination'] = $this->pagination($apartments);

        return $this->successResponse($this->data);
    }

    // filter apartments by city

    /**
     * @throws \Exception
     */
    public function getFilterApartments(Request $request)
    {

        $filters = $request->filters;
        $query = $this->apartment::query();
        if (! empty($filters)) {
            foreach ($filters as $key => $val) {
                if (! empty($filters)) {
                    foreach ($filters as $key => $val) {
                        if ($val) {
                            if ($key == 'city_id') {
                                $query->whereHas('building', function ($query) use ($val) {
                                    $query->where('city_id', $val);
                                });

                                continue;
                            }

                            if ($key == 'building_id') {
                                $query->whereHas('building', function ($query) use ($val) {
                                    $query->where('id', $val);
                                });

                                continue;
                            }

                            if ($key == 'check_in' || $key == 'check_out') {
                                if (! isset($filters['check_in']) || ! isset($filters['check_out'])) {
                                    continue;
                                }

                                $checkInDate = Carbon::parse($filters['check_in'])->format('Y-m-d');
                                $checkOutDate = Carbon::parse($filters['check_out'])->format('Y-m-d');

                                $query->whereDoesntHave('bookings', function ($query) use ($checkInDate, $checkOutDate) {
                                    $query->where(function ($q) use ($checkInDate, $checkOutDate) {
                                        $q->where('check_in', '<', $checkOutDate)
                                            ->where('check_out', '>', $checkInDate);
                                    });
                                });

                                break; // توقف بعد معالجة الفلتر المدمج
                            }

                            $filterHandler = FilterFactory::make($key);
                            $query = $filterHandler->apply($query, $val);
                        }
                    }
                }
            }
        }

        $apartments = $query->where('is_active', 1)->paginate(30);
        $this->data['apartments'] = ApartmentResource::collection($apartments); // total_amount
        $this->data['pagination'] = $this->pagination($apartments);

        return $this->successResponse($this->data);
    }

    // getApartments

    public function getApartments(Request $request)
    {
        $id = $request->id;

        $apartments = Apartment::where('id', $id)->orWhere('slug', $id)
            ->with(['building', 'reviews', 'labels', 'bookings', 'ownerrezMapping'])
            ->where('is_active', 1)
            ->first();

        if (! $apartments) {
            return $this->errorResponse([], __('api.apartment_not_found'), 404);
        }

        $apartmentData = (new ApartmentResource($apartments))->toArray($request);

        // دمج حجوزات OwnerRez من الكاش مع حجوزات قاعدة البيانات
        $mapping = $apartments->ownerrezMapping;
        if (filter_var($request->input('withOwnerrez', true), FILTER_VALIDATE_BOOLEAN) && $mapping && config('ownerrez.availability.enabled')) {
            try {
                $today = Carbon::today()->format('Y-m-d');

                $ownerRezDays = $this->ownerRezSync->getCalendarBookings($mapping->ownerrez_property_id)
                    ->flatMap(function ($booking) use ($today) {
                        $period = CarbonPeriod::create(
                            $booking['arrival'],
                            Carbon::parse($booking['departure'])->subDay()
                        );

                        return collect($period)
                            ->map(fn ($date) => $date->format('Y-m-d'))
                            ->filter(fn ($date) => $date >= $today)
                            ->values();
                    })->values()->toArray();

                $apartmentData['booked_days'] = array_values(array_unique(
                    array_merge(
                        collect($apartmentData['booked_days'] ?? [])->toArray(),
                        $ownerRezDays
                    )
                ));
            } catch (\Exception $e) {
                \Log::warning('OwnerRez calendar fetch failed in API', [
                    'apartment_id' => $apartments->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->data['apartments'] = $apartmentData;

        return $this->successResponse($this->data);
    }

    // getReviewApartment

    public function getReviewApartment(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:apartments,id',
        ]);
        $id = $request->id;
        $reviews = Review::where('apartment_id', $id)->latest()->get();
        $this->data['reviews'] = ReviewResource::collection($reviews);

        return $this->successResponse($this->data);
    }

    protected function booked_days($reservations)
    {
        $bookedDays = collect();
        $today = Carbon::today()->format('Y-m-d');

        if ($reservations && $reservations->count() > 0) {
            foreach ($reservations as $reservation) {
                // إنشاء الفترة من check_in إلى check_out (بدون تضمين check_out)
                $period = CarbonPeriod::create($reservation->check_in, $reservation->check_out->subDay());

                foreach ($period as $date) {
                    $dateString = $date->format('Y-m-d');
                    // التأكد من عدم إضافة تواريخ سابقة عن اليوم الحالي
                    if ($dateString >= $today) {
                        $bookedDays->push($dateString);
                    }
                }
            }
        }

        return $bookedDays;
    }

    // getSupport
    public function getSupport()
    {
        $this->data = [
            'phone' => '01000000000',
            'email' => 'info@diyfa.sa',
            'whatsapp' => '01000000000',
        ];

        return $this->successResponse($this->data);
    }

    // getTerms
    public function getPage(Request $request)
    {
        $template = $request->template;
        $page = Page::where('template', $template)->first();
        $this->data['page'] = new PageResource($page);

        return $this->successResponse($this->data);
    }

    // getFaqPage

    public function getFaqPage()
    {
        $page = Page::where('template', 'faq')->first();
        $this->data['page'] = new PageResource($page);

        return $this->successResponse($this->data);
    }

    public function contactUs(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
            'message' => 'required',
            'subject' => 'required',
        ]);
        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'message' => $request->message,
            'subject' => $request->subject,
        ];
        ContactUs::create($data);

        return $this->successResponse([], __('api.contact_us'));

    }
}
