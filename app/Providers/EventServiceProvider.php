<?php

namespace App\Providers;

use App\Events\BookingApproved;
use App\Events\CustomerCancellationAccepted;
use App\Listeners\ProcessCustomerRefund;
use App\Listeners\SyncBookingToOwnerRez;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        BookingApproved::class => [
            SyncBookingToOwnerRez::class,
        ],
        CustomerCancellationAccepted::class => [
            ProcessCustomerRefund::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
