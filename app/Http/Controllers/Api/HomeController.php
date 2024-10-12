<?php

namespace App\Http\Controllers\Api;

use App\Filters\FilterFactory;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApartmentResource;
use App\Http\Resources\CityResource;
use App\Http\Resources\SliderResource;
use App\Models\{Apartment, City, SliderApp};
use Illuminate\Http\Request;

class HomeController extends Controller
{
    protected $sliderApp;
    protected $city;
    protected $apartment;

    public function __construct(SliderApp $sliderApp, City $city, Apartment $apartment)
    {
        $this->sliderApp = $sliderApp;
        $this->city = $city;
        $this->apartment = $apartment;
    }

    public function index()
    {
        $sliders = $this->sliderApp->get();
        $this->data['sliders'] = SliderResource::collection($sliders);
        $cities = $this->city->orderBy('sort_order')->get();
        $this->data['cities'] = CityResource::collection($cities);
        $cities = $this->city->with('buildings')->get();
        $this->data['cities_with_building'] = CityResource::collection($cities);
        $apartments = $this->apartment->with('building.city')->latest()->limit(5)->get();
        $this->data['apartments'] = ApartmentResource::collection($apartments);
        return $this->successResponse($this->data);
    }

    //list all apartments

    public function getListingApartments()
    {
        $apartments = $this->apartment->with('building.city')->latest()->paginate(1);
        $this->data['apartments'] = ApartmentResource::collection($apartments);
        $this->data['pagination'] =  $this->pagination($apartments);
        return $this->successResponse($this->data);
    }

    //filter apartments by city

    public function  getFilterApartments(Request $request)
    {
        $filters = $request->filters;
        $query = $this->apartment::query();
        foreach ($filters as $key=> $val) {
            if($val) {
                $filterHandler = FilterFactory::make($key);
                $query = $filterHandler->apply($query, $val);
            }
        }
        $apartments = $query->paginate(30);
        $this->data['apartments'] = ApartmentResource::collection($apartments);
        $this->data['pagination'] =  $this->pagination($apartments);
        return $this->successResponse($this->data);
    }

    //getApartments

    public function getApartments(Request $request)
    {
//        $validator = $this->validate($request, [
//            'id' => 'required|exists:apartments,id'
//        ]);
        $id = $request->id;
        $apartments =  $this->apartment->with('building')->findOrFail($id);
        $this->data['apartments'] =new ApartmentResource($apartments);
        return $this->successResponse($this->data);
    }
}
