<?php

namespace App\Http\Controllers\Front;

use App\Filters\FilterFactory;
use App\Http\Controllers\Controller;
use App\Models\Apartment;
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
    
        $bookedDays = $apartment->booked_days($apartment->bookings)->toArray();
        $lastBookedDate = now();
        while (in_array($lastBookedDate->format('Y-m-d'), $bookedDays)) {
            $lastBookedDate->addDay();
        }
        $started_day = $lastBookedDate->copy()->addDay()->format('Y-m-d');
        $next_started_day = $lastBookedDate->copy()->addDays(2)->format('Y-m-d');
    
        $data = [
            'apartment' => $apartment,
            'started_day' => $started_day,
            'next_started_day' => $next_started_day,
        ];
    
        return view('apartment.show', $data);
    }


    //search
    public function search(Request $request)
    {
    
        $filters = $request->filters;
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
        return view('apartment.index', compact('apartments'));
    }
    
 
}
