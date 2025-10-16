<?php

namespace App\Filters;

use Carbon\Carbon;

class CheckInFilter implements FilterHandlerInterface
{
    public function apply($query, $value)
    {
        return ;
        $checkInDate = Carbon::parse($value)->format('Y-m-d');
        return $query->whereDoesntHave('bookings', function ($query) use ($checkInDate) {
            $query->where('check_out', '>', $checkInDate) // الحجز ينتهي بعد تاريخ check-in المحدد
                  ->where('check_in', '<', $checkInDate); // الحجز يبدأ قبل تاريخ check-in المحدد
        });
    }
}