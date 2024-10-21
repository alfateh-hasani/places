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
            'price' => floatval($this->price) ,
            'num_rooms' => $this->num_rooms,
            'num_beds' => $this->num_beds,
            'area' => $this->area,
            'adults_count' => $this->adults_count,
            'children_count' => $this->children_count,
            'bathrooms_count' => $this->bathrooms_count,
            'image' => getAllImages($this, 'image'),
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
            $data['map_link'] =  $this->building?->link;
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

        return $data;
    }
}
