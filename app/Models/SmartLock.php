<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class SmartLock extends Model
{
    use CrudTrait, LogsActivity;

    protected $connection = 'mysql';

    protected $guarded = [];


    //buliding relationship 

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    //get full name of the smart lock

    public function getFullNameAttribute()
    {
        return $this->lock_id . ' - ' .  $this->lock_name;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll();
    }
}
