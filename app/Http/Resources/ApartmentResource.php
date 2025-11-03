<?php

namespace App\Http\Resources;

use App\Models\Favorites;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class ApartmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {

        $checkIn = $request->filters['check_in'] ?? null;
        $checkOut = $request->filters['check_out'] ?? null;
        $nights = ($checkIn && $checkOut) ? Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut)) : null;

        $data = [
            'id' => $this->id,
            'apart_no' => (string) $this->slug,
             'slug' =>   $this->slug,
            'name' => $this->{'name_' . app()->getLocale()},
            'description' => $this->{'description_' . app()->getLocale()},
            'building_id' => $this->building_id,
            'building_name' => $this->building->{'name_' . app()->getLocale()},
            'city_id' => $this->building->city_id,
            'city_name' => $this->building?->city->{'name_' . app()->getLocale()},
            'price' => floatval(value: $this->price) ,
            'total_amount' => ($nights !== null) ? floatval($nights * $this->price) : null,
            'num_rooms' => $this->num_rooms,
            'num_beds' => $this->num_beds,
            'area' => $this->area,
            'adults_count' => $this->adults_count,
            'children_count' => $this->children_count,
            'bathrooms_count' => $this->bathrooms_count,
            'image' => getAllImages($this, 'image','grid-app2', 5),
            'original_image' => getAllImages($this, 'image' ),
            'is_favorite' =>  $this->is_favorite,
            'top_rated' => $this->top_rated,
            'ratings' => $this->total_ratings,
            'features' =>$this->features->map(function ($feature) {
                return [
                    'id' => $feature->id,
                    'name' => $feature->{'name_' . app()->getLocale()},
                    'color' => $feature->color ?? '#000000',
                    'icon' => getImage($feature, 'icon'),
                ];
            }),
        ];
        if ($this->whenLoaded('reviews')) {
            $data['reviews'] = ReviewResource::collection($this->reviews);
            $data['total_reviews'] = $this->reviews->count();
        }
        if ($this->whenLoaded('policy')) {
            $data['policy_title'] = $this->policy?->{'name_' . app()->getLocale()};
            $data['policy_description'] = $this->policy?->{'description_' . app()->getLocale()};
        }
        if ($this->whenLoaded('building')) {
            $data['map'] =  $this->building?->map;
            $data['latitude'] =  $this->building?->latitude;
            $data['longitude'] =  $this->building?->longitude;
            $data['link'] =  $this->building?->link;
      

            $data['check_in_time'] = $this->building?->check_in_time 
                ? Carbon::parse($this->building->check_in_time)->format('h:i A') 
                : null;
            
            $data['check_out_time'] = $this->building?->check_out_time 
                ? Carbon::parse($this->building->check_out_time)->format('h:i A') 
                : null;
            
            
        }
        if ($this->whenLoaded('labels')) {
            $data['labels'] = $this->labels->map(function ($label) {
                return [
                    'id' => $label->id,
                    'name' => $label->{'name_' . app()->getLocale()},
                    'description' => $label->{'description_' . app()->getLocale()},
                    'icon' => getImage($label, 'icon'),
                ];
            });
        }
        //booked days
        if ($this->whenLoaded('bookings')) {
            $data['booked_days'] = $this->booked_days($this->bookings->whereNotIn('status', ['canceled', 'customer_canceled']));
        }

        return $data;
    }
}
