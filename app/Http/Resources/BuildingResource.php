<?php

namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class BuildingResource extends JsonResource
{


    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->{'name_' . app()->getLocale()},
            'image' => getImage($this, 'image','grid'),
            'city_id' => $this->city_id,
            'city_name' => $this->city->{'name_' . app()->getLocale()},
            'apartments_count' => $this->apartments()->count(),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'link' => $this->link,
        ];

        return $data;
    }


}
