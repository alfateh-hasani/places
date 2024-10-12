<?php

namespace App\Filters;

class CityFilter implements FilterHandlerInterface
{
    public function apply($query, $value)
    {
        return $query->where('city_id', $value);
    }
}

