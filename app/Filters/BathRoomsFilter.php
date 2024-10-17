<?php

namespace App\Filters;

class BathRoomsFilter implements FilterHandlerInterface
{
    public function apply($query, $value)
    {
        return $query->where('bathrooms_count', $value);
    }
}
