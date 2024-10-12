<?php

namespace App\Filters;

class MinPriceFilter implements FilterHandlerInterface
{
    public function apply($query, $value)
    {
        return $query->where('price', '>=', $value);
    }
}
