<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Image\Enums\CropPosition;
use App\Traits\HasTranslations;
use Spatie\Image\Enums\Fit;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Slider extends Model implements HasMedia
{
    use InteractsWithMedia, CrudTrait, HasTranslations, LogsActivity; 
    
    protected $guarded = [];
    protected $with = ['media'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image_ar')->singleFile();
        $this->addMediaCollection('image_en')->singleFile();
    }
 

    public function registerMediaConversions(Media $media = null): void {
        $this->addMediaConversion('thumb')
        
            ->fit(  Fit::Crop, 2732, 920 )
            ->format('webp')                         // Convert to WebP format
            ->nonQueued();                           // Process synchronously (optional)
    }


    public function getImageArAttribute()
    {
        return $this->getFirstMediaUrl('image_ar');
    }

    public function getImageEnAttribute()
    {
        return $this->getFirstMediaUrl('image_en');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll();
    }
}
