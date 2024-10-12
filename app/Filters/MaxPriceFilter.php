<?php

namespace App\Filters;

class MaxPriceFilter implements FilterHandlerInterface
{
    public function apply($query, $value)
    {
        return $query->where('price', '<=', $value);
    }
}
