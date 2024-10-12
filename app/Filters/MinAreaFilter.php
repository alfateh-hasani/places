<?php

namespace App\Filters;

class MinAreaFilter implements FilterHandlerInterface
{
    public function apply($query, $value)
    {
        return $query->where('area', '>=', $value);
    }
}
