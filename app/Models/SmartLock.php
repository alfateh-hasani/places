<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class SmartLock extends Model
{
    use CrudTrait;

    protected $guarded = [];


    //buliding relationship 

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    //get full name of the smart lock

    public function getFullNameAttribute()
    {
        return $this->lock_name . ' - ' . $this->building?->name_ar;
    }
}
