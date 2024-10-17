<?php

namespace App\Http\Resources;

use App\Models\Favorites;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApartmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->{'name_' . app()->getLocale()},
            'description' => $this->{'description_' . app()->getLocale()},
            'building_id' => $this->building_id,
            'building_name' => $this->building->{'name_' . app()->getLocale()},
            'city_id' => $this->building->city_id,
            'city_name' => $this->building?->city->{'name_' . app()->getLocale()},
            'price' => $this->price,
            'num_rooms' => $this->num_rooms,
            'num_beds' => $this->num_beds,
            'area' => $this->area,
            'adults_count' => $this->adults_count,
            'children_count' => $this->children_count,
            'image' => getAllImages($this, 'image'),
            'is_favorite' =>  $this->is_favorite,
            'top_rated' => $this->top_rated,
            'ratings' => $this->total_ratings,
            'features' =>$this->features->map(function ($feature) {
                return [
                    'id' => $feature->id,
                    'name' => $feature->{'name_' . app()->getLocale()},
                    'icon' => getImage($feature, 'icon'),
                ];
            }),
        ];
        if ($this->whenLoaded('reviews')) {
            $data['reviews'] = ReviewResource::collection($this->reviews);
        }
        if ($this->whenLoaded('policy')) {
            $data['policy'] = [
                'id' => $this->policy?->id,
                'description' => $this->policy?->{'description_' . app()->getLocale()},
            ];
        }
        if ($this->whenLoaded('building')) {
            $data['map'] =  [
                'latitude' => $this->building?->latitude,
                'longitude' => $this->building?->longitude,
            ];
        }

        return $data;
    }
}
