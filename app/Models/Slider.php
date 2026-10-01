<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Slider extends Model implements HasMedia
{
    use CrudTrait, HasTranslations, InteractsWithMedia, LogsActivity;

    protected $connection = 'mysql';

    protected $guarded = [];

    protected $with = ['media'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image_ar')->singleFile();
        $this->addMediaCollection('image_en')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')

            ->fit(Fit::Crop, 2732, 920)
            ->format('webp')                         // Convert to WebP format
            ->nonQueued();                           // Process synchronously (optional)

        $this->addMediaConversion('hero')
            ->performOnCollections('image_ar', 'image_en')
            ->fit(Fit::Max, 1600, 1600)
            ->quality(80)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('hero_small')
            ->performOnCollections('image_ar', 'image_en')
            ->fit(Fit::Max, 800, 800)
            ->quality(80)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('hero_mobile')
            ->performOnCollections('image_mobile_ar', 'image_mobile_en')
            ->fit(Fit::Max, 900, 1400)
            ->quality(80)
            ->format('webp')
            ->nonQueued();
    }

    /**
     * URL of a display-sized conversion, falling back to the original
     * upload when the conversion has not been generated yet.
     */
    public function displayImageUrl(string $collection, string $conversion): string
    {
        $media = $this->getFirstMedia($collection);

        if (! $media) {
            return '';
        }

        return $media->hasGeneratedConversion($conversion)
            ? $media->getUrl($conversion)
            : $media->getUrl();
    }

    /**
     * srcset for the desktop slide: the 800px and 1600px conversions that exist.
     */
    public function heroSrcset(string $collection): string
    {
        $media = $this->getFirstMedia($collection);

        if (! $media) {
            return '';
        }

        return collect(['hero_small' => 800, 'hero' => 1600])
            ->filter(fn (int $width, string $conversion): bool => $media->hasGeneratedConversion($conversion))
            ->map(fn (int $width, string $conversion): string => $media->getUrl($conversion).' '.$width.'w')
            ->implode(', ');
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
            ->logAll()
            ->logOnlyDirty();
    }
}
