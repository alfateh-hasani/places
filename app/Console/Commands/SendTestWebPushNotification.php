<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\User;
use App\Notifications\WebPushNotification;
use Illuminate\Console\Command;

class SendTestWebPushNotification extends Command
{
    protected $signature = 'webpush:test {recipient : user or customer} {id : the recipient id}';

    protected $description = 'Send a test web notification (in-app + browser push) to a staff User or web Customer';

    public function handle(): int
    {
        $recipient = $this->argument('recipient');

        $model = match ($recipient) {
            'user' => User::find($this->argument('id')),
            'customer' => Customer::find($this->argument('id')),
            default => null,
        };

        if ($model === null) {
            $this->error("No {$recipient} found for id {$this->argument('id')}.");

            return self::FAILURE;
        }

        $model->notify(new WebPushNotification(
            title: 'إشعار تجريبي',
            body: 'هذا إشعار ويب تجريبي من نظام Places.',
            actionUrl: '/',
            type: 'test',
        ));

        $subscriptions = $model->pushSubscriptions()->count();
        $this->info("Sent to {$recipient} #{$model->getKey()} — stored in-app; {$subscriptions} browser subscription(s) pushed.");

        return self::SUCCESS;
    }
}
