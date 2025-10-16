<?php

namespace App\Filters;

class BookingDateFilter implements FilterHandlerInterface
{
    public function apply($query, $dates)
    {
        return $query->whereDoesntHave('bookings', function ($query) use ($dates) {
            $query->where(function ($q) use ($dates) {
                $q->where('check_in', '<=', $dates['check_out'])
                  ->where('check_out', '>=', $dates['check_in']);
            });
        });
    }
}
