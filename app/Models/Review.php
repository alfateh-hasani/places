<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = [
        'customer_id',
        'rating',
        'review_text',
    ];
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
