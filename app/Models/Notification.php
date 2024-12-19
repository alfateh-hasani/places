<?php

namespace App\Models;

use App\Services\PushNotificationService;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
class Notification extends Model implements HasMedia
{
    use CrudTrait;
    use InteractsWithMedia;
     public $guarded = [];
     protected $with = ['media'];
    public function user (){
        return $this->belongsTo(Customer::class);
    }

    public function notification_seen(){
        return $this->hasOne(NotificationSeen::class);
    }


    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image');
    }

    //image
    public function getImageAttribute()
    {
        return $this->getFirstMediaUrl('image');
    }


    //BOOT CREATED
    protected static function boot()
    {
        parent::boot();
        static::created(function ($notification) {
            $notification = $notification->refresh();
            app(PushNotificationService::class)->send($notification);
        });
    }
}
