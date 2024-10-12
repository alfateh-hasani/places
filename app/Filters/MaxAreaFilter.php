<?php

namespace App\Filters;

class MaxAreaFilter implements FilterHandlerInterface
{
    public function apply($query, $value)
    {
        return $query->where('area', '<=', $value);
    }
}
