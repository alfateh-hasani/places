<?php

namespace App\Filters;
use Illuminate\Database\Eloquent\Builder;
interface FilterHandlerInterface
{
    public function apply(Builder $query, $value);
}
