<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Blog extends Model implements HasMedia
{
    use CrudTrait, HasTranslations, InteractsWithMedia, LogsActivity;

    protected $connection = 'mysql';

    protected $guarded = ['id'];

    /**
     * Public URLs use lowercase hyphenated slugs; a blank admin value falls back to the English name.
     */
    public function setSlugAttribute(?string $value): void
    {
        $slug = Str::slug((string) $value);

        if ($slug === '') {
            $slug = Str::slug((string) ($this->attributes['name_en'] ?? ''));
        }

        $this->attributes['slug'] = $slug;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('card')

            ->fit(Fit::Crop, 390, 195)
            ->format('webp')                         // Convert to WebP format
            ->nonQueued();                           // Process synchronously (optional)
    }

    public function getImageAttribute()
    {
        return $this->getFirstMediaUrl('image');
    }

    // getLinkAttribute
    public function getLinkAttribute()
    {
        return route('blog', $this->slug);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }
}
