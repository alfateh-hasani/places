<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use App\Models\Apartment;
use App\Models\Building;
use App\Notifications\Channels\SmsChannel;
use App\Observers\ApartmentObserver;
use App\Observers\BuildingObserver;
use App\Services\Locks\Contracts\LockProviderInterface;
use App\Services\Locks\Providers\ScienerLockProvider;
use Auth;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Support\Facades\Notification;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LockProviderInterface::class, ScienerLockProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        Schema::defaultStringLength(191);

        Notification::extend('sms', function ($app) {
            return new SmsChannel();
        });

        Apartment::observe(ApartmentObserver::class);
        Building::observe(BuildingObserver::class);
    }
}
