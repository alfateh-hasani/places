<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Coupon extends Model
{
    use CrudTrait;
    protected $guarded = [];

    protected $casts = [
        'id' => 'integer',
        'building_id' => 'integer',
        'discount' => 'float',
        'uses_total' => 'integer',
        'uses_customer' => 'integer',
    ];
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }
    public function apartments(): BelongsToMany
    {
        return $this->belongsToMany(Apartment::class, 'coupon_apartment', 'coupon_id', 'apartment_id');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}
