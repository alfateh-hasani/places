<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Front\{HomeController,ApartmentController, CustomerAccountController,PageController};


Route::group(['prefix' => LaravelLocalization::setLocale()], function()
{

    // Home Route using HomeController@index
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::post('contact-us', [HomeController::class, 'contactUs'])->name('home.contact-us');
    //get blog
    Route::get('blog/{slug}', [HomeController::class, 'blog'])->name('blog');

    Route::get('{slug}', [PageController::class, 'index'])->name('page');

    Route::get('/apartments', [ApartmentController::class, 'index'])->name('apartments.index');
    Route::get('/apartments/{slug}', [ApartmentController::class, 'show'])->name('apartments.show');



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
            Route::get('favorite', 'favorite')->name('favorite');
            Route::get('notifications', 'notifications')->name('notifications');
        });

    });

});

