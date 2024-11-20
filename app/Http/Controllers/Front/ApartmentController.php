<?php

namespace App\Http\Controllers\Front;

use App\Filters\FilterFactory;
use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\City;
use Artesaos\SEOTools\Facades\SEOTools;
use Config;
use Illuminate\Http\Request;

class ApartmentController extends Controller
{
    protected $apartment;
    public function __construct(  Apartment $apartment  )
    {
        $this->apartment = $apartment;
    }
 
    public function index() 
    {
        // Fetch all active apartments
        $apartments = Apartment::where('is_active', true)->paginate(12);

        return view('apartment.index', compact('apartments'));
    }

    public function show($slug)
    {
        $apartment = Apartment::with(['building.city', 'reviews', 'features', 'bookings', 'policy'])
            ->where('slug', $slug)
            ->firstOrFail();
    
            $lastBookedDate = $apartment->bookings->sortBy('check_out')->first()?->check_out;

        if ($lastBookedDate) {
            $started_day = $lastBookedDate->copy()->addDay()->format('Y-m-d');
            $next_started_day = $lastBookedDate->copy()->addDays(2)->format('Y-m-d');
        } else {
            $started_day = now()->format('Y-m-d');  
            $next_started_day = now()->addDay()->format('Y-m-d'); 
        }
        
        $booked_days = $apartment->bookings?->map(function ($booking) {
            return [
                'check_in' => $booking->check_in->format('Y-m-d'),
                'check_out' => $booking->check_out->format('Y-m-d')
            ];
        })->toArray();
            
          $data = [
            'apartment' => $apartment,
            'started_day' => $started_day,
            'next_started_day' => $next_started_day,
            'booked_days' => $booked_days
        ];
        $seo_title = $apartment->ml('seo_title') . ' | ' . Config::get('settings.seo_title_'.app()->getLocale());
        $seo_description = $apartment->ml('seo_description');
        $url = route('apartments.show', $apartment->slug);
        $this->generateSeo($seo_title, $seo_description, $url);

    
        return view('apartment.show', $data);
    }
    


    //search
    public function search(Request $request)
    {
           
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
        ], function ($value) {
            return !is_null($value) && $value !== '';
        });
     

        $query = $this->apartment::query();

        foreach ($filters as $key => $val) {
            if ($key === 'city_id') {
                $query->whereHas('building', function ($query) use ($val) {
                    $query->where('city_id', $val);
                });
                continue;
            }

            if (in_array($key, ['num_rooms', 'num_beds']) && is_array($val) && count($val) > 0) {
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

            $filterHandler = FilterFactory::make($key);
            $query = $filterHandler->apply($query, $val);
        }

        $apartments = $query->latest()->paginate(8);

        $data = [
            'cities' => City::orderBy('sort_order', 'asc')->withCount('apartments')->get(),
            'apartments' => $apartments,
            'filter_keys' => $this->prepareFilterKeys(),
        ];

        return view('apartment.list', $data);
    }

    protected function prepareFilterKeys()
    {
        return [
            'min_price' => $this->apartment->min('price') ?? 0,
            'max_price' => $this->apartment->max('price') ?? 0,
            'rooms_options' => $this->apartment
                ? $this->apartment->pluck('num_rooms')->unique()->sort()->values()->toArray()
                : [],
            'max_area' => $this->apartment->max('area') ?? 0,
            'min_area' => $this->apartment->min('area') ?? 0,
            'beds_options' => $this->apartment
                ? $this->apartment->pluck('num_beds')->unique()->sort()->values()->toArray()
                : [],
            'bathrooms_options' => $this->apartment
                ? $this->apartment->pluck('bathrooms_count')->unique()->sort()->values()->toArray()
                : [],
        ];
    }


    private function generateSeo($seo_title, $seo_description,$url)
    {
        SEOTools::setTitle($seo_title);
        SEOTools::setDescription($seo_description);
        SEOTools::opengraph()->setUrl($url);
        SEOTools::setCanonical($url);
        SEOTools::opengraph()->addProperty('type', 'articles');

    }
}
