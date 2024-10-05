<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CityResource;
use App\Http\Resources\SliderResource;
use App\Models\City;
use App\Models\Slider;

class HomeController extends Controller
{
    public function index()
    {
        $this->data['sliders'] = SliderResource::collection(Slider::where('position','app')->get());
        $this->data['cities'] = CityResource::collection(City::orderBy('sort_order')->get());

        return $this->successResponse($this->data);
    }
}
