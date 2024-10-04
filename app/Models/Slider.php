<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Slider extends Model implements HasMedia
{
    use InteractsWithMedia;
    use CrudTrait;
    protected $guarded = [];
    protected $with = ['media'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image_ar')->singleFile();
        $this->addMediaCollection('image_en')->singleFile();
    }

    public function getImageArAttribute()
    {
        return $this->getFirstMediaUrl('image_ar');
    }

    public function getImageEnAttribute()
    {
        return $this->getFirstMediaUrl('image_en');
    }
}
