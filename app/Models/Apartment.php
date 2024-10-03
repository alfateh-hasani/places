<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Apartment extends Model implements HasMedia
{
    use CrudTrait;
    use InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name_ar',
        'name_en',
        'building_id',
        'description_ar',
        'description_en',
        'num_rooms',
        'num_beds',
        'area',
        'latitude',
        'longitude',
        'is_active',
        'smart_lock_id',
        'price',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'building_id' => 'integer',
        'num_rooms' => 'integer',
        'num_beds' => 'integer',
        'area' => 'decimal:2',
        'is_active' => 'boolean',
        'smart_lock_id' => 'integer',
        'price' => 'decimal:2',
    ];

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    //features
    public function features()
    {
        return $this->belongsToMany(Feature::class, 'apartment_features');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image');
    }

    //belongsTo polices
    public function policy()
    {
        return $this->belongsTo(Policy::class);
    }

    //lock_alias
    public function lock()
    {
        return $this->belongsTo(Lock::class,'lock_alias');
    }
}
