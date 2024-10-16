<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Coupon extends Model
{
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }


    public function apartments(): BelongsToMany
    {
        return $this->belongsToMany(Apartment::class, 'coupon_apartment', 'coupon_id', 'apartment_id');
    }
}
