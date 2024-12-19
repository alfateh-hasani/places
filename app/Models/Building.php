<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Traits\HasTranslations;
class Building extends Model implements HasMedia
{
    use CrudTrait;
    use InteractsWithMedia;
    use HasTranslations;
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
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'city_id' => 'integer',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }


    public function getImageAttribute()
    {
        return $this->getFirstMediaUrl('image');
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

}
