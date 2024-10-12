<?php

namespace App\Filters;

class ChildrenCountFilter implements FilterHandlerInterface
{
    public function apply($query, $value)
    {
        return $query->where('children_count', $value);
    }
}
