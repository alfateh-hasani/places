<?php

namespace App\Models\Concerns;

use App\Models\WebNotification;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Shared in-app (web) notification behaviour for recipient models (User, Customer).
 * Backed by the polymorphic `web_notifications` table — independent of the legacy
 * mobile `notifications` table and its FCM pipeline.
 */
trait HasWebNotifications
{
    public function webNotifications(): MorphMany
    {
        return $this->morphMany(WebNotification::class, 'notifiable')->latest();
    }

    public function unreadWebNotifications(): MorphMany
    {
        return $this->webNotifications()->whereNull('read_at');
    }
}
