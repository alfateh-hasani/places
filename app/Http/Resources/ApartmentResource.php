<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApartmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
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
            'image' => getImage($this, 'image'),
            'is_favorite' => $this->is_favorite,
            'features' =>$this->features->map(function ($feature) {
                return [
                    'id' => $feature->id,
                    'name' => $feature->{'name_' . app()->getLocale()},
                    'icon' => getImage($feature, 'icon'),
                ];
            }),
        ];
    }
}
