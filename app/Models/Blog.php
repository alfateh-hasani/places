<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Traits\HasTranslations;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
class Blog extends Model implements HasMedia
{
    use CrudTrait, InteractsWithMedia, HasTranslations, LogsActivity; 
    protected $guarded = ['id'];
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }


    public function registerMediaConversions(Media $media = null): void {
        $this->addMediaConversion('card')

            ->fit(  Fit::Crop, 390, 195 )
            ->format('webp')                         // Convert to WebP format
            ->nonQueued();                           // Process synchronously (optional)
    }
    public function getImageAttribute()
    {
        return $this->getFirstMediaUrl('image');
    }

    //getLinkAttribute
    public function getLinkAttribute()
    {
        return route('blog', $this->slug);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll();
    }

}
