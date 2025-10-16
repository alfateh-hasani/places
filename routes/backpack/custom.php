<?php

use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\AirbnbBookingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\LockController;


Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace' => 'App\Http\Controllers\Admin',
], function () {
    Route::crud('smart-lock', 'LockSmartController');
    Route::get('get-smart-locks', 'LockSmartController@getSmartLocks');
    Route::crud('city', 'CityController');
    Route::crud('coupon', 'CouponController');
    Route::crud('feature', 'FeatureController');
    Route::crud('apartment', 'ApartmentController');
    Route::crud('building', 'BuildingController');
    Route::crud('apartment-label', 'ApartmentLabelController');
    Route::crud('policy', 'PolicyController');
    Route::crud('buildings', 'BuildingController');
    Route::crud('sliders', 'SliderController');
    Route::crud('sliders-app', 'SliderAppController');
    Route::get('get-related-entities', 'SliderAppController@getRelatedEntities');

    Route::crud('advantages', 'AdvantageController');
    Route::crud('pages', 'PageController');
    Route::crud('blogs', 'BlogController');
    Route::crud('notifications', 'NotificationController');
    Route::crud('booking', 'BookingController');
    Route::get('booking/{id}/edit-check-in-time', 'BookingController@editCheckInTime');
    Route::post('booking/{id}/update-check-in-time', 'BookingController@updateCheckInTime');
    Route::post('booking/{id}/regenerate-passcode', 'BookingController@regeneratePasscode');
    Route::crud('airbnb-booking', 'AirbnbBookingController');
    Route::crud('faq', 'FaqController'); 
    Route::crud('faq-category', 'FaqCategoryController');
    Route::crud('site-feature', 'SiteFeatureController');
    
    Route::crud('transaction', 'TransactionController');
    Route::crud('customer', 'CustomerController');
    Route::crud('service', 'ServiceController');
 
 
  
    Route::post('booking/{id}/change-status/{status}', [BookingController::class, 'changeStatus'])->name('admin.booking.change-status');
    // Route for changing payment status
    Route::post('booking/{id}/change-payment-status/{status}', [BookingController::class, 'changePaymentStatus'])->name('admin.booking.change-payment-status');
    
    // Daily Occupancy
    Route::get('charts/daily-occupancy', [\App\Http\Controllers\Admin\Charts\DailyOccupancyChartController::class, 'data'])->name('charts.daily-occupancy');

    // Ongoing Bookings
    Route::get('charts/ongoing-bookings', [\App\Http\Controllers\Admin\Charts\OngoingBookingsChartController::class, 'data'])->name('charts.ongoing-bookings');

    // Units Available
    Route::get('charts/units-available', [\App\Http\Controllers\Admin\Charts\UnitsAvailableChartController::class, 'data'])->name('charts.units-available');

    // Average Rating
    Route::get('charts/average-rating', [\App\Http\Controllers\Admin\Charts\AverageRatingChartController::class, 'data'])->name('charts.average-rating');

    // Monthly Bookings
    Route::get('charts/monthly-bookings', [\App\Http\Controllers\Admin\Charts\MonthlyBookingsChartController::class, 'data'])->name('charts.monthly-bookings');

    Route::get('charts/total-users', [\App\Http\Controllers\Admin\Charts\TotalUsersChartController::class, 'data'])
    ->name('charts.total-users');
    Route::get('reports', [\App\Http\Controllers\Admin\ReportsController::class, 'index'])->name('admin.reports.index');
    Route::get('/reports/daily-checkout', [\App\Http\Controllers\Admin\ReportsController::class, 'dailyCheckOutReport']);
    Route::crud('contact-us', 'ContactUsCrudController');
    Route::crud('service-booking', 'ServiceBookingCrudController');
    Route::crud('review', 'ReviewCrudController');

    // تقويم الحجوزات
    Route::get('apartment/{id}/calendar', [\App\Http\Controllers\Admin\CalenderController::class, 'showCalendar']);
    Route::get('apartment/{id}/bookings', [\App\Http\Controllers\Admin\CalenderController::class, 'getApartmentBookings']);
    
    // تقويم الأسعار (منفصل)
    Route::get('apartment/{id}/pricing', [\App\Http\Controllers\Admin\CalenderController::class, 'showPricingCalendar']);
    Route::get('apartment/{id}/custom-prices', [\App\Http\Controllers\Admin\CalenderController::class, 'getCustomPrices']);
    Route::post('apartment/{id}/custom-price', [\App\Http\Controllers\Admin\CalenderController::class, 'saveCustomPrice']);
    Route::delete('apartment/{id}/custom-price/{priceId}', [\App\Http\Controllers\Admin\CalenderController::class, 'deleteCustomPrice']);
    Route::post('apartment/{id}/preview-pricing', [\App\Http\Controllers\Admin\CalenderController::class, 'previewPricing']);

    Route::crud('category', 'CategoryCrudController');
    Route::crud('onboarding', 'OnboardingCrudController');
});



// Route::post('admin/lock/{id}/unlock', [LockController::class, 'unlock'])->name('lock.unlock');
// Route::post('admin/lock/{id}/add-passcode', [LockController::class, 'addPasscode'])->name('lock.add_passcode');
