<?php

namespace App\Models;

use App\Services\PushNotificationService;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
class Notification extends Model
{
    use CrudTrait;
    public $guarded = [];
    public function user (){
        return $this->belongsTo(Customer::class);
    }

    public function notification_seen(){
        return $this->hasOne(NotificationSeen::class);
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
