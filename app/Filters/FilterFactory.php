<?php

namespace App\Filters;

class FilterFactory
{
    public static function make($field)
    {
        switch ($field) {

            case 'city':
                return app(CityFilter::class);
            case 'building':
                return app(BuildingFilter::class);
            case 'max_price':
                return app(MaxPriceFilter::class);
            case 'min_price':
                return app(MinPriceFilter::class);
            case 'adults_count':
                return app(AdultsCountFilter::class);
            case 'children_count':
                return app(ChildrenCountFilter::class);
            case 'num_rooms':
                return app(NumRoomsFilter::class);
            case 'num_beds ':
                return app(NumBedsFilter::class);
            case 'max_area':
                return app(MaxAreaFilter::class);
            case 'min_area':
                return app(MinAreaFilter::class);


            default:
                throw new \Exception("No filter found for field: $field");
        }
    }
}

