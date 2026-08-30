<?php

namespace App\Models;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Transaction extends Model implements HasMedia
{
    use CrudTrait, InteractsWithMedia;

    protected $guarded = [];

    protected $casts = [
        'order_id' => 'string',
    ];

    /** Bank-transfer receipt uploaded by staff for a manual (dashboard) booking. */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('receipt')->singleFile();
    }

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
