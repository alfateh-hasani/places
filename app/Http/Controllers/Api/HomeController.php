<?php

namespace App\Http\Controllers\Api;

use App\Filters\FilterFactory;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApartmentResource;
use App\Http\Resources\CityResource;
use App\Http\Resources\SliderResource;
use App\Models\{Apartment, Building, City, SliderApp};
use Illuminate\Http\Request;

class HomeController extends Controller
{
    protected $sliderApp;
    protected $city;
    protected $apartment;
    protected $building;

    public function __construct(SliderApp $sliderApp, City $city,
                                Apartment $apartment , Building $building)
    {
        $this->sliderApp = $sliderApp;
        $this->city = $city;
        $this->apartment = $apartment;
        $this->building = $building;
    }

    public function index()
    {
        $sliders = $this->sliderApp->get() ?? [
            'id' => 1,
            'name' => 'default',
            'image' => 'default.jpg',
            'related_id' => 1,
            'related_type' => 'general'
        ];
        $this->data['sliders'] = SliderResource::collection($sliders);
        $cities = $this->city->orderBy('sort_order')->get();
        $this->data['cities'] = CityResource::collection($cities);
        $cities = $this->city->with('buildings')->get();
        $this->data['cities_with_building'] = CityResource::collection($cities);
        $apartments = $this->apartment->with('building.city')->latest()->limit(5)->get();
        $this->data['apartments'] = ApartmentResource::collection($apartments);
        $this->data['buildings']  = $this->building->get()?->map(function ($building) {
            return  [
                'id' => $building->id,
                'name' => $building->{'name_' . app()->getLocale()},
            ];
        });
        $user = \Auth::guard('api')->user();
        $this->data['user_name'] = $user?->first_name.' '.$user?->last_name;
        $this->data['filter_keys'] = [
            'min_price' =>  $this->apartment->min('price'),
            'max_price' =>  $this->apartment->max('price'),
            'max_rooms' =>  $this->apartment->max('num_rooms'),
            'max_area'  =>   $this->apartment->max('area'),
            'min_area'  =>   $this->apartment->min('area'),
            'max_beds'  =>   $this->apartment->max('num_beds'),
            'max_bathrooms'  =>   6, // $this->apartment->max('num_bathrooms'), // not exist in db
        ];
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

    /**
     * @throws \Exception
     */
    public function  getFilterApartments(Request $request)
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
        $apartments =  $this->apartment->with(['building','reviews'])->findOrFail($id);
        $this->data['apartments'] =new ApartmentResource($apartments);
        return $this->successResponse($this->data);
    }
}
