<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Building extends Model implements HasMedia
{
    use CrudTrait, HasTranslations, InteractsWithMedia, LogsActivity;

    protected $connection = 'mysql';

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
        'seo_title_ar',
        'seo_title_en',
        'seo_description_ar',
        'seo_description_en',
        'supervisor_id',
        'latitude',
        'longitude',
        'ttlock_username',
        'ttlock_password',
        'sort_order',
        'is_active',
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
        'ttlock_password' => 'encrypted',
        'is_active' => 'boolean',
    ];

    /**
     * Never serialize the TTLock account password into an array/JSON response.
     * Direct attribute access ($building->ttlock_password, used by
     * LockCredentialResolver) is unaffected — this only governs toArray()/toJson().
     */
    protected $hidden = [
        'ttlock_password',
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

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('grid')
            ->fit(Fit::Crop, 1000, desiredHeight: 1000)

            ->quality(75)
            ->format('webp')                         // Convert to WebP format
            ->nonQueued();                           // Process synchronously (optional)

        $this->addMediaConversion('card')
            ->fit(Fit::Crop, 600, desiredHeight: 600)
            ->quality(75)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('card_md')
            ->fit(Fit::Crop, 800, desiredHeight: 800)
            ->quality(75)
            ->format('webp')
            ->nonQueued();
    }

    public function getImageGridAttribute()
    {
        return $this->getFirstMediaUrl('image', 'grid');
    }

    /**
     * Smaller square crop for the website cards; falls back to the grid size
     * until the conversion has been generated.
     */
    public function getImageCardSrcsetAttribute(): string
    {
        $media = $this->getFirstMedia('image');

        if (! $media) {
            return '';
        }

        return collect(['card' => 600, 'card_md' => 800, 'grid' => 1000])
            ->filter(fn (int $width, string $conversion): bool => $media->hasGeneratedConversion($conversion))
            ->map(fn (int $width, string $conversion): string => $media->getUrl($conversion).' '.$width.'w')
            ->implode(', ');
    }

    public function getImageCardAttribute(): string
    {
        $media = $this->getFirstMedia('image');

        if (! $media) {
            return '';
        }

        return $media->hasGeneratedConversion('card') ? $media->getUrl('card') : $this->image_grid;
    }

    public function getImageAttribute()
    {
        return $this->getFirstMediaUrl('image');
    }

    // hasMany apartments
    public function apartments()
    {
        return $this->hasMany(Apartment::class);
    }

    /**
     * Only buildings that are currently active (available to the public site & API).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('is_active'), true);
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept(['ttlock_password'])
            ->logOnlyDirty();
    }
}
