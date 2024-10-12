<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->{'name_' . app()->getLocale()},
            'image' => getImage($this, 'image'),
        ];
        if ($this->relationLoaded('buildings')) {
            $data['buildings'] = BuildingResource::collection($this->buildings);
        }
        if ($this->relationLoaded('apartments')) {
            $data['apartments'] = ApartmentResource::collection($this->apartments);
        }
        return $data;
    }
}
