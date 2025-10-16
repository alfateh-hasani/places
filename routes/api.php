<?php

use App\Http\Controllers\Api\{AuthController, BookingController, CustomerController, HomeController, OnboardingController};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

 

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::middleware('appSecret')->group(function () {

    Route::post('otp/request',  [AuthController::class, 'requestOtp']);
    Route::post('otp/verify',  [AuthController::class, 'verifyOtp'])->name('otp.verify');
    Route::post('customer/register',  [AuthController::class, 'registerUser'])->name('otp.registerUser');


    Route::middleware('auth:api')->group(function () {
        Route::controller(CustomerController::class)->prefix('customer')->group(function () {
            Route::get('my-profile', 'myProfile');
            Route::post('logout', 'logout');
            Route::post('update-profile', 'updateProfile');
            Route::post('delete-profile', 'deleteProfile');
            Route::post('add-review', 'addReview');
            Route::get('my-favorite', 'myFavorite');
            Route::post('add-favorite', 'addFavorite');
            Route::post('remove-favorite', 'removeFavorite');
            Route::get('get-all-bookings', 'getAllBookings');
            Route::get('current-bookings', 'currentBookings');
            Route::get('previous-bookings', 'previousBookings');
            Route::post('fmc-token', 'fmcToken');
            Route::post('notification/mark-seen/{id}', 'markSeen');
            Route::get('notification/unread-count', 'unreadCount');
            Route::get('notifications', 'getNotifications');
            Route::get('notification/{id}', 'getNotificationDetails');
        });

        Route::controller(BookingController::class)->prefix('booking')->group(function () {
            Route::get('get-booking', 'getBooking');
            Route::get('login-apartment', 'loginApartment');
            Route::get('entry-apartment', 'entryApartment');
            Route::post('cancel-booking', 'cancelBooking');
            Route::post('add-booking', 'addBooking');
            Route::get('get-booking-via-customer', 'getBookingViaCustomer');
            Route::post('determine-booking', 'determineBookingStatus');
            Route::get('calculate-price-with-coupon', 'calculatePriceWithCoupon');
            Route::get('calculate-price-withOut-coupon', 'calculatePriceWithOutCoupon');
            Route::get('get-services', 'getServices');
            Route::post('add-services-to-booking', 'addServicesToBooking');
            Route::post('booking-services', 'bookingServices');
            Route::post('cancel-booking-payment/{id}', 'cancelBookingPayment');
        });

    });

    Route::controller(HomeController::class)->prefix('home')->group(function () {
        Route::get('index', 'index');
        Route::post('get-filter-apartments', 'getFilterApartments');
        Route::get('get-apartment', 'getApartments');
        Route::get('get-review-for-apartment', 'getReviewApartment');
        Route::get('get-support', 'getSupport');
        Route::get('get-page', 'getPage');
        Route::get('get-faq-page', 'getFaqPage');
        Route::post('contact-us', 'contactUs');
    });

    // Onboarding routes
    Route::controller(OnboardingController::class)->prefix('onboarding')->group(function () {
        Route::get('index', 'index');
    });

});
Route::controller(BookingController::class)->prefix('payment-methods')->group(function () {
    Route::get('{code}/callback/{transaction_id}', 'paymentMethodCallBack')->name('paymentMethodCallBack');
    // Route::post('{code}/callback/{transaction_id}', 'paymentMethodCallBack')->name('paymentMethodCallBack');
    Route::get('success/{booking_id}/{booking_number}', 'paymentMethodSuccess')->name('paymentMethodSuccess');
    Route::get('failed', 'paymentMethodFailed')->name('paymentMethodFailed');
});
