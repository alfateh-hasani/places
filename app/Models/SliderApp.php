<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class SliderApp extends Model implements HasMedia
{
    use InteractsWithMedia;
    use CrudTrait;
    protected $fillable = [
        'name_ar',
        'name_en',
        'related_type',
        'related_id',
    ];
    protected $with = ['media'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image_ar')->singleFile();
        $this->addMediaCollection('image_en')->singleFile();
    }

    public function getImageArAttribute(): string
    {
        return $this->getFirstMediaUrl('image_ar');
    }

    public function getImageEnAttribute(): string
    {
        return $this->getFirstMediaUrl('image_en');
    }

    public function related()
    {
        if ($this->related_type === 'general') {
            return null;
        }
        return $this->morphTo();
    }

}
