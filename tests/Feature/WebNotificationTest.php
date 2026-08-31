<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\Channels\WebDatabaseChannel;
use App\Notifications\WebPushNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use NotificationChannels\WebPush\WebPushChannel;
use Tests\TestCase;

class WebNotificationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_web_push_notification_persists_an_in_app_record_for_a_user(): void
    {
        $user = User::factory()->create();

        // No push subscription registered, so the WebPush channel is a safe no-op;
        // this exercises via() routing + the WebDatabaseChannel persistence.
        $user->notify(new WebPushNotification(
            title: 'Booking confirmed',
            body: 'Your booking is confirmed.',
            actionUrl: '/admin/bookings/1',
            type: 'booking.confirmed',
        ));

        $this->assertDatabaseHas('web_notifications', [
            'notifiable_type' => $user->getMorphClass(),
            'notifiable_id' => $user->getKey(),
            'type' => 'booking.confirmed',
            'title' => 'Booking confirmed',
        ]);

        $notification = $user->webNotifications()->first();
        $this->assertSame('/admin/bookings/1', $notification->data['action_url']);
        $this->assertSame(1, $user->unreadWebNotifications()->count());
    }

    public function test_notification_routes_to_both_web_database_and_web_push_channels(): void
    {
        $user = User::factory()->create();
        $channels = (new WebPushNotification('t'))->via($user);

        $this->assertContains(WebDatabaseChannel::class, $channels);
        $this->assertContains(WebPushChannel::class, $channels);
    }

    public function test_marking_a_web_notification_as_read_clears_it_from_unread(): void
    {
        $user = User::factory()->create();
        $user->notify(new WebPushNotification('Test', 'Body'));

        $this->assertSame(1, $user->unreadWebNotifications()->count());

        $user->webNotifications()->first()->markAsRead();

        $this->assertSame(0, $user->unreadWebNotifications()->count());
    }
}
