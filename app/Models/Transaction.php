<?php

namespace App\Models;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use CrudTrait;
    protected $guarded = [];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }


    public function apartment(): BelongsTo
    {
        return $this->belongsTo(apartment::class);
    }

    //booking
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }


}
