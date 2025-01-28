<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceBooking extends Model
{
    use CrudTrait;
    protected $guarded = [];
     
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
