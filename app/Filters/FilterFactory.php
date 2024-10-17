<?php

namespace App\Filters;

class FilterFactory
{
    public static function make($field)
    {
        return match ($field) {
            'city_id' => app(CityFilter::class),
            'building_id' => app(BuildingFilter::class),
            'max_price' => app(MaxPriceFilter::class),
            'min_price' => app(MinPriceFilter::class),
            'adults_count' => app(AdultsCountFilter::class),
            'children_count' => app(ChildrenCountFilter::class),
            'num_rooms' => app(NumRoomsFilter::class),
            'num_beds' => app(NumBedsFilter::class),
            'max_area' => app(MaxAreaFilter::class),
            'min_area' => app(MinAreaFilter::class),
            'bathrooms_count' => app(BathRoomsFilter::class),
            default => throw new \Exception("No filter found for field: $field"),
        };
    }
}

