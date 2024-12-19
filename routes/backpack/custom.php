<?php

use App\Http\Controllers\Admin\BookingController;
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
    Route::crud('faq', 'FaqController'); 
    Route::crud('faq-category', 'FaqCategoryController');
    Route::crud('site-feature', 'SiteFeatureController');
    
    Route::crud('transaction', 'TransactionController');
    Route::crud('customer', 'CustomerController');
 
  
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


});



// Route::post('admin/lock/{id}/unlock', [LockController::class, 'unlock'])->name('lock.unlock');
// Route::post('admin/lock/{id}/add-passcode', [LockController::class, 'addPasscode'])->name('lock.add_passcode');
