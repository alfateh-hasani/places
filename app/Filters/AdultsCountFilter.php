<?php

namespace App\Filters;

class AdultsCountFilter implements FilterHandlerInterface
{
    public function apply($query, $value)
    {
        return $query->where('adults_count', $value);
    }
}
