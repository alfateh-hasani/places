<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Apartment extends Model implements HasMedia
{
    use CrudTrait;
    use InteractsWithMedia;
    protected $with = ['media'];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

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
        'bathrooms_count' => 'integer',
    ];

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    //features
    public function features()
    {
        return $this->belongsToMany(Feature::class, 'apartment_features', 'apartment_id', 'feature_id');
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
        return $this->belongsTo(SmartLock::class, 'smart_lock_id');
    }


    //is_favorite
    public function getIsFavoriteAttribute()
    {
        $user =  \Auth::guard('api')->user();
        return  $this->favorites()->where('customer_id', $user->id)->exists();
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorites::class);
    }

    //top_rated
    public function getTopRatedAttribute()
    {
//        return $this->ratings()->avg('rating');
        return true;
    }

    //ratings
    public function getTotalRatingsAttribute()
    {
//        return $this->hasMany(Rating::class);

        return '4.5';
    }

    //reviews
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    //image
    public function getImageAttribute()
    {
        return $this->getFirstMediaUrl('image');
    }


    //coupon_apartment

    public function coupons()
    {
        return $this->belongsToMany(Coupon::class, 'coupon_apartment', 'apartment_id', 'coupon_id');
    }

    //bookings
    public function bookings()
    {
        return $this->hasMany(Reservation::class);
    }
}
