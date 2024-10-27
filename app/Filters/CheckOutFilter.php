<?php

namespace App\Filters;

class CheckOutFilter implements FilterHandlerInterface
{
    public function apply($query, $value)
    {
        return $query->whereDoesntHave('bookings', function ($query) use ($value) {
            $query->where('check_in', '<=', $value)
                  ->where('check_out', '>=', $value);
        });
    }
}

