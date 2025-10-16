<?php

namespace App\Filters;

use Carbon\Carbon;

class CheckOutFilter implements FilterHandlerInterface
{
    public function apply($query, $value)
    {
        return ;
        $checkOutDate = Carbon::parse($value)->format('Y-m-d');
        return $query->whereDoesntHave('bookings', function ($query) use ($checkOutDate) {
            $query->where('check_in', '<', $checkOutDate) // الحجز يبدأ قبل تاريخ check-out المحدد
                  ->where('check_out', '>', $checkOutDate); // الحجز ينتهي بعد تاريخ check-out المحدد
        });
    }
}