<?php

namespace App\Filters;

class NumRoomsFilter implements FilterHandlerInterface
{
    public function apply($query, $value)
    {
        return $query->where('num_rooms', $value);
    }
}
