<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class NotificationSeen extends Model
{
    public $guarded = [];
    public function notification(){
        return $this->belongsTo(Notification::class);
    }
}
