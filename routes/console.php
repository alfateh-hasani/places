<?php

use App\Services\BookingService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::call(function () {
    app(BookingService::class)->deleteUnpaidBookings();
})->everyTenMinutes();

// Retry failed passcode generation attempts every 20 minutes
Schedule::command('passcode:retry-failed')->cron('*/10 * * * *');

// Check for bookings that need passcode generation every 30 minutes
Schedule::command('booking:check-missing-passcodes')->cron('*/10 * * * *');

Schedule::command('import:airbnb-ics')->cron('*/10 * * * *');

// إرسال إشعارات الخروج يومياً في الساعة 10 صباحاً
Schedule::command('notifications:send-checkout-reminders')->dailyAt('10:00');
 