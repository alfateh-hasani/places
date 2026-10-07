<?php

namespace App\Providers;

use App\Models\Apartment;
use App\Models\Blog;
use App\Models\Building;
use App\Models\City;
use App\Notifications\Channels\SmsChannel;
use App\Observers\ApartmentObserver;
use App\Observers\BlogObserver;
use App\Observers\BuildingObserver;
use App\Observers\CityObserver;
use App\Services\Locks\Contracts\LockProviderInterface;
use App\Services\Locks\Providers\ScienerLockProvider;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

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
            return new SmsChannel;
        });

        Apartment::observe(ApartmentObserver::class);
        Building::observe(BuildingObserver::class);
        City::observe(CityObserver::class);
        Blog::observe(BlogObserver::class);
    }
}
