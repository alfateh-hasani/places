<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ApartmentLabel extends Model implements HasMedia
{
    use InteractsWithMedia;
    use CrudTrait;
    protected $fillable = [
        'name_ar',
        'name_en',
        'description_ar',
        'description_en',
    ];
    protected $with = ['media'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('icon')->singleFile();

    }

    //icon
    public function getIconAttribute()
    {
        return $this->getFirstMediaUrl('icon');
    }

    //apartment_label_apartment

    public function apartments()
    {
        return $this->belongsToMany(Apartment::class, 'apartment_label_apartment', 'label_id', 'apartment_id');
    }
}
