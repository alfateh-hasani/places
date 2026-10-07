<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use LaravelLocalization;
use Request;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Page extends Model implements HasMedia
{
    use CrudTrait, HasTranslations, InteractsWithMedia, LogsActivity;

    protected $connection = 'mysql';

    protected $guarded = ['id'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }

    public function getImageAttribute()
    {
        return $this->getFirstMediaUrl('image');
    }

    // public static function isRequestedPathAPage(): bool
    // {
    //     $url = urldecode(Request::path());

    //     $url = remove_http(LaravelLocalization::getNonLocalizedURL($url)) ;

    //     $domain = remove_http(config('app.url').'/');
    //     $url = str_replace($domain, '', $url);
    //     return   Self::whereSlug($url)->exists();
    // }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }
}
