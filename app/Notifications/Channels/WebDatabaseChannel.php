<?php

namespace App\Notifications\Channels;

use App\Models\WebNotification;
use Illuminate\Notifications\Notification;

class WebDatabaseChannel
{
    /**
     * Persist the notification into the polymorphic `web_notifications` store.
     * This is the in-app (bell / notifications page) record for staff Users and
     * web Customers. It is deliberately separate from the legacy mobile
     * `notifications` table so the mobile FCM flow is never touched.
     *
     * @param  \App\Models\User|\App\Models\Customer  $notifiable
     */
    public function send(object $notifiable, Notification $notification): WebNotification
    {
        /** @var array{title: string, body?: string|null, type?: string|null, data?: array<string, mixed>} $payload */
        $payload = $notification->toWebDatabase($notifiable);

        return $notifiable->webNotifications()->create([
            'type' => $payload['type'] ?? $notification::class,
            'title' => $payload['title'],
            'body' => $payload['body'] ?? null,
            'data' => $payload['data'] ?? [],
        ]);
    }
}
