<?php

namespace App\Notifications\Channels;
use Illuminate\Notifications\Notification;
use  App\Notifications\Providers\SmsProvider;
class SmsChannel
{
    public function send($notifiable, Notification $notification)
    {
        \Log::info('Sending SMS Notification');
        $message = $notification->toSms($notifiable);
        SmsProvider::sendSms($notifiable->routeNotificationFor('sms'), $message['content']);
    }
}
