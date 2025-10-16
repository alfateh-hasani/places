<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;


use Illuminate\Database\Eloquent\Model;

class ServiceBooking extends Model
{
    use CrudTrait;
    protected $guarded = [];

    public function apartment()
    {
        return $this->belongsTo(Apartment::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
