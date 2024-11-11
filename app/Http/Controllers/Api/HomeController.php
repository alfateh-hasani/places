<?php

namespace App\Http\Controllers\Api;

use App\Filters\FilterFactory;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApartmentResource;
use App\Http\Resources\CityResource;
use App\Http\Resources\PageResource;
use App\Http\Resources\ReviewResource;
use App\Http\Resources\SliderResource;
use App\Models\{Apartment, Building, City, ContactUs, Page, Review, SliderApp};
use Carbon\CarbonPeriod;
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
        $sliders = $this->sliderApp->get();
        $this->data['sliders'] = SliderResource::collection($sliders);
        $cities = $this->city->orderBy('sort_order')->get();
        $this->data['cities'] = CityResource::collection($cities);
        $cities = $this->city->with('buildings')->whereHas('buildings')->get();
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
        $apartments = $this->apartment->with('building.city')->latest()->paginate(20);
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
        $request->validate([
            'id' => 'required|exists:apartments,id'
        ]);
        $id = $request->id;
        $apartments =  $this->apartment->with(['building','reviews','labels','bookings'])->findOrFail($id);
        $this->data['apartments'] =new ApartmentResource($apartments);

        return $this->successResponse($this->data);
    }

    //getReviewApartment

    public function getReviewApartment(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:apartments,id'
        ]);
        $id = $request->id;
        $reviews =  Review::where('apartment_id',$id)->latest()->get();
        $this->data['reviews'] =  ReviewResource::collection($reviews);
        return $this->successResponse($this->data);
    }


    protected function booked_days($reservations)
    {
        $bookedDays = collect();
        if ($reservations && $reservations->count() > 0) {
            foreach ($reservations as $reservation) {
                $period = CarbonPeriod::create($reservation->check_in, $reservation->check_out);
                foreach ($period as $date) {
                    $bookedDays->push($date->format('Y-m-d'));
                }
            }
        }
        return $bookedDays;
    }

    //getSupport
    public function getSupport()
    {
        $this->data['support'] = [
            'phone' => '01000000000',
            'email' => '',
            'whatsapp' => '01000000000',
            ];
        return $this->successResponse($this->data);
    }

    //getTerms
    public function getPage(Request $request)
    {
        $template = $request->template;
        $page = Page::where('template',$template)->first();
        $this->data['page'] =new PageResource($page);
        return $this->successResponse($this->data);
    }

    //getFaqPage

    public function getFaqPage()
    {
        $page = Page::where('template','faq')->first();
        $this->data['page'] =new PageResource($page);
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
        $data=[
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'message' => $request->message,
            'subject' => $request->subject,
        ];
        ContactUs::create($data);
        return $this->successResponse([],__('api.contact_us'));


    }
}
