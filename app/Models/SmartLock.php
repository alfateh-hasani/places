<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class SmartLock extends Model
{
    use CrudTrait;

    protected $guarded = [];
}
