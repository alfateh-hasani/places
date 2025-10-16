<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Traits\HasTranslations;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Image\Enums\Fit;
class Building extends Model implements HasMedia
{
    use CrudTrait, InteractsWithMedia, HasTranslations, LogsActivity;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name_ar',
        'name_en',
        'address_en',
        'address_ar',
        'city_id',
        'map',
        'link',
        'check_in_time',
        'check_out_time',
        'slug',
        'supervisor_id',
        'latitude',
        'longitude',
        'ttlock_username',
        'ttlock_password',
        'sort_order',
        
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'city_id' => 'integer',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')
        ->singleFile();
    }


    public function registerMediaConversions(Media $media = null): void {
        $this->addMediaConversion('grid')
            ->fit(  Fit::Crop, 1000, desiredHeight: 1000 )

            ->quality(75)
            ->format('webp')                         // Convert to WebP format
            ->nonQueued();                           // Process synchronously (optional)


    }


    public function getImageGridAttribute()
    {
        return $this->getFirstMediaUrl('image' , 'grid');
    }


    public function getImageAttribute()
    {
        return $this->getFirstMediaUrl('image'  );
    }

    //hasMany apartments
    public function apartments()
    {
        return $this->hasMany(Apartment::class);
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll();
    }

}
