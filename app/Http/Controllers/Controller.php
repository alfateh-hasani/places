<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use App\Traits\FileTrait;

abstract class Controller
{
    use ApiResponse , FileTrait;

    protected $data = [];


    protected function pagination($paginator)
    {
        return [
            'total'       => $paginator->total(),
            'count'       => $paginator->count(),
            'per_page'    => $paginator->perPage(),
            'pageIndex'   => $paginator->currentPage(),
            'nextPage'    => $paginator->currentPage() + 1,
            'total_pages' => $paginator->lastPage(),
            'has_more'    => !$paginator->currentPage() >= $paginator->lastPage()
        ];
    }
}
