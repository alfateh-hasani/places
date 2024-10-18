<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Slider,Apartment,City}; // Import Slider model

class HomeController extends Controller
{
    //

    public function index(Request $request){

        $data   = [];
        $data['sliders'] = Slider::orderBy('sort_order','asc')->get();
        $data['apartments'] = Apartment::where('is_active', true) ->with('reviews')  ->orderBy('id', 'desc') ->take(10)->get();
        $data['cities']     = City::orderBy('sort_order','asc')->withCount('apartments')->get();
        $data['buildings'] = City::with('buildings')->orderBy('sort_order','asc')    ->whereHas('buildings')  ->get();

        // apartments
        return view('home.index',$data);
    }
}
