<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Apartment;

class ApartmentController extends Controller
{
    public function index()
    {
        // Fetch all active apartments
        $apartments = Apartment::where('is_active', true)->paginate(12);

        return view('apartment.index', compact('apartments'));
    }

    public function show($slug)
    {
        $data['apartment'] = Apartment::with(['building.city','reviews','labels','bookings'])->where('slug', $slug)->firstOrFail();
        $data['lang'] = app()->getLocale();
        $data['direction'] = app()->isLocale('ar') ? 'rtl' : 'ltr';  
        return view('apartment.show', $data);
    }
 
}
