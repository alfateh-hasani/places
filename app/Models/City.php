<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class City extends Model implements HasMedia
{
    use CrudTrait, HasFactory, HasTranslations, InteractsWithMedia, LogsActivity;

    protected $connection = 'mysql';

    protected $with = ['media'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name_ar',
        'name_en',
        'sort_order',
        'slug',
        'seo_title_ar',
        'seo_description_ar',
        'seo_title_en',
        'seo_description_en',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'sort_order' => 'integer',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }

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

    // getattributeimage
    public function getImageAttribute()
    {
        return $this->getFirstMediaUrl('image');
    }

    // hasMany apartments

    // belongsTo buildings
    public function buildings()
    {
        return $this->hasMany(Building::class);
    }

    // city has many apartments from buildings

    public function apartments()
    {
        return $this->hasManyThrough(Apartment::class, Building::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }
}
