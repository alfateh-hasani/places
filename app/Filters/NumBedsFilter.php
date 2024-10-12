<?php

namespace App\Filters;

class NumBedsFilter implements FilterHandlerInterface
{
    public function apply($query, $value)
    {
        return $query->where('num_beds', $value);
    }
}
