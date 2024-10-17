<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favorites extends Model
{
    protected $guarded = [];
    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
