<?php

namespace App\Filters;

class BuildingFilter implements FilterHandlerInterface
{
    public function apply($query, $value)
    {
        return $query->where('building_id', $value);
    }
}
