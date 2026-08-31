<?php

namespace App\Notifications;

use App\Notifications\Channels\WebDatabaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Generic web notification for staff Users and web Customers. Persists an in-app
 * record (bell / notifications page) via the WebDatabaseChannel and delivers a
 * browser push via the WebPush channel. Has nothing to do with the mobile FCM flow.
 */
class WebPushNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        protected string $title,
        protected ?string $body = null,
        protected ?string $actionUrl = null,
        protected ?string $type = null,
        protected array $data = [],
    ) {
        //
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [WebDatabaseChannel::class, WebPushChannel::class];
    }

    /**
     * @return array{title: string, body: string|null, type: string|null, data: array<string, mixed>}
     */
    public function toWebDatabase(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'type' => $this->type,
            'data' => $this->payload(),
        ];
    }

    public function toWebPush(object $notifiable, mixed $notification): WebPushMessage
    {
        $message = (new WebPushMessage)
            ->title($this->title)
            ->icon('/images/notification-icon.png')
            ->badge('/images/notification-icon.png')
            ->data(['action_url' => $this->actionUrl ?? '/']);

        if ($this->body !== null) {
            $message->body($this->body);
        }

        return $message;
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(): array
    {
        return array_merge($this->data, [
            'action_url' => $this->actionUrl,
        ]);
    }
}
