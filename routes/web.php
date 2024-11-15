<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Front\{HomeController,ApartmentController, BookingController, CustomerAccountController,PageController};


Route::group(['prefix' => LaravelLocalization::setLocale()], function()
{
    Route::get('apartments-filter', [ApartmentController::class, 'search'])->name('apartments.search');

    // Home Route using HomeController@index
    Route::get('/', [HomeController::class, 'index'])->name('home');

    Route::get('{slug}', [PageController::class, 'index'])->name('page');
    Route::get('blog/{slug}', [HomeController::class, 'blog'])->name('blog');
    Route::get('get-apartments-by-city/{slug}', [HomeController::class, 'getApartmentsByCity'])->name('by-city');
    Route::post('contact-us', [HomeController::class, 'contactUs'])->name('home.contact-us');
    Route::get('/apartments', [ApartmentController::class, 'index'])->name('apartments.index');
    Route::get('/apartments/{slug}', [ApartmentController::class, 'show'])->name('apartments.show');
    //search



    Route::middleware('guest:customer')->group(function () {
        Route::post('/request-otp', [\App\Http\Controllers\Front\Auth\LoginController::class, 'requestOtp'])->name('login.step1');
        Route::post('/resend-otp', [\App\Http\Controllers\Front\Auth\LoginController::class, 'resendOtp'])->name('login.resend_otp');
        Route::post('/verify-otp', [\App\Http\Controllers\Front\Auth\LoginController::class, 'verifyOtp'])->name('login.step2');
        Route::post('/register', [\App\Http\Controllers\Front\Auth\LoginController::class, 'registerUser'])->name('login.register');
    });


    Route::middleware('auth:customer')->group(function () {
        Route::post('/logout', [\App\Http\Controllers\Front\Auth\LoginController::class, 'logout'])->name('customer.logout');
        Route::controller(CustomerAccountController::class)->name('customer.')->prefix('customer')->group(function () {
            Route::get('account', 'profile')->name('account');
            Route::post('account-update', 'update')->name('update');
            Route::get('get-booking', 'getBooking')->name('booking');
            Route::get('booking-details/{number_of_booking}', 'BookingDetails')->name('booking.details');
            Route::get('favorite', 'favorite')->name('favorite');
            Route::get('notifications', 'notifications')->name('notifications');
            Route::post('toggle-favorite', 'toggleFavorite')->name('toggle.favorite'); 
            Route::post('post-review', 'addReview')->name('post.review');
        });
        Route::controller(BookingController::class)->name('web-booking.')->prefix('web-booking')->group(function () {
            Route::get('determine-booking/{apartment_id}', 'determineBookingStatus')->name('determine');
            Route::post('add-booking', 'addBooking')->name('add');
            Route::get('{code}/callback/{transaction_id}', 'paymentMethodCallBack')->name('paymentMethodCallBack');
            Route::get('login-apartment', 'loginApartment')->name('login');
            Route::post('coupons-verify', 'couponsVerify')->name('coupons.verify');
            Route::post('cancel-booking', 'cancelBooking')->name('cancel');
        }); 
    });

});

