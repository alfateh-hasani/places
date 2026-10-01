<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Maximum stay
    |--------------------------------------------------------------------------
    |
    | Longest stay (in nights) a booking or price quote may cover. Pricing runs
    | per night, so an unbounded range (e.g. year 2000 → 9999) would tie up a
    | PHP worker for minutes per request.
    |
    */

    'max_nights' => (int) env('BOOKING_MAX_NIGHTS', 365),

    /*
    | Unpaid pending bookings a customer may hold at once (each one blocks its dates
    | until the cleanup job releases it).
    */
    'max_open_pending_per_customer' => (int) env('BOOKING_MAX_OPEN_PENDING', 3),

];
