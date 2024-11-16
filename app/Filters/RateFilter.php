<?php

namespace App\Filters;

class RateFilter implements FilterHandlerInterface
{
    public function apply($query, $value)
    {
       return  $query->whereHas('reviews', function($q) use ($value) {
            $q->where('rate', '>=', $value);
       });
    }
}

