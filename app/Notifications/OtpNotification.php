<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
class OtpNotification extends Notification
{
    use Queueable;


    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected string $otp
    )
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['sms'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toSms(object $notifiable): array
    {
        return [
            'content' => "Hello  Your registration OTP is: {$this->otp}",
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
