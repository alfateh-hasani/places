<?php

namespace App\Http\Controllers\Front;

use App\Filters\FilterFactory;
use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\City;
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
        $started_day = $lastBookedDate->copy()->addDay()->format('Y-m-d');
        $next_started_day = $lastBookedDate->copy()->addDays(2)->format('Y-m-d');
        $booked_days =  $apartment->bookings?->map(function($booking) {
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
    
    
        return view('apartment.show', $data);
    }
    


    //search
    public function search(Request $request)
    {
        $filters = [
            'city_id' => $request->city_id,
            'check_out' => $request->check_out,
            'check_in' => $request->check_in,
            'adults_count' => $request->adults_count,
            'children_count' => $request->children_count,
        ];
        $query = $this->apartment::query();
        if (!empty($filters)) {
            foreach ($filters as $key=> $val) {
                if($val) {
                    if ($key == 'city_id') {
                        $query->whereHas('building', function ($query) use ($val) {
                            $query->where('city_id', $val);
                        });
                        continue;
                    }
                    $filterHandler = FilterFactory::make($key);
                    $query = $filterHandler->apply($query, $val);
                }
            }
        }
        $apartments = $query->paginate(30);
        $data['cities'] =  City::orderBy('sort_order','asc')->withCount('apartments')->get();
        $data['apartments'] = $apartments;
        $data['filter_keys'] = [
            'min_price' =>  $this->apartment->min('price'),
            'max_price' =>  $this->apartment->max('price'),
            'max_rooms' =>  $this->apartment->max('num_rooms'),
            'max_area'  =>   $this->apartment->max('area'),
            'min_area'  =>   $this->apartment->min('area'),
            'max_beds'  =>   $this->apartment->max('num_beds'),
            'max_bathrooms'  =>   6, // $this->apartment->max('num_bathrooms'), // not exist in db
        ];
        
        return view('apartment.list', $data);
    }
    
 
}
