<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use App\Traits\FileTrait;

abstract class Controller
{
    use ApiResponse , FileTrait;

    protected $data = [];

}
