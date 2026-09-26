<?php

namespace App\Filters;

class BuildingFilter implements FilterHandlerInterface
{
    public function apply($query, $value)
    {
        return is_array($value)
            ? $query->whereIn('building_id', $value)
            : $query->where('building_id', $value);
    }
}
