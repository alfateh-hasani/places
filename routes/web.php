<?php

use App\Http\Controllers\Front\ApartmentController;
use App\Http\Controllers\Front\ApartmentsICSController;
use App\Http\Controllers\Front\BookingController;
use App\Http\Controllers\Front\CustomerAccountController;
use App\Http\Controllers\Front\HomeController;
use App\Http\Controllers\Front\PageController;
use App\Http\Controllers\Front\RobotsController;
use App\Http\Controllers\Front\SitemapController;
use App\Http\Controllers\Front\WishlistController;
use Illuminate\Support\Facades\Route;

Route::get('/apartments/{apartment}/unit.ics', [ApartmentsICSController::class, 'generateICS'])->name('apartments.ics');
Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('robots.txt', [RobotsController::class, 'index'])->name('robots');

Route::group(['prefix' => LaravelLocalization::setLocale()], function () {
    Route::get('apartments-filter', [ApartmentController::class, 'search'])->name('apartments.search');

    // Home Route using HomeController@index
    Route::get('/', [HomeController::class, 'index'])->name('home');

    Route::get('{slug}', [PageController::class, 'index'])->name('page');
    Route::get('blog/{slug}', [HomeController::class, 'blog'])->name('blog');
    Route::get('city/{slug}/apartments', [HomeController::class, 'getApartmentsByCity'])->name('by-city');
    Route::post('contact-us', [HomeController::class, 'contactUs'])->name('home.contact-us');
    Route::get('/apartments', [ApartmentController::class, 'index'])->name('apartments.index');
    Route::get('/apartments/{slug}', [ApartmentController::class, 'show'])->name('apartments.show');
    Route::post('/apartments/{apartmentId}/calculate-price', [ApartmentController::class, 'calculatePrice'])->name('apartments.calculate-price');
    Route::get('/apartments/{id}/blocked-dates', [ApartmentController::class, 'blockedDates'])->name('apartments.blocked-dates');
    // NOTE: named `building.details` (not `building.show`) on purpose — Backpack's
    // Route::crud('building', ...) auto-generates an admin route named `building.show`,
    // and since admin routes load later they would win the name lookup, making
    // route('building.show') resolve to /admin/building/{id}/show. A unique public
    // name keeps the customer-facing link pointing at /building/{slug}.
    Route::get('building/{slug}', [ApartmentController::class, 'getApartmentBuliding'])->name('building.details');
    // Old, misspelled URL — kept as a permanent redirect so previously indexed/shared links keep working.
    // TODO:: remove this after month today is 14/septemper 9 / 2026
    Route::get('buliding/{slug}', fn($slug) => redirect()->route('building.details', ['slug' => $slug], 301));
    // search

    Route::middleware('guest:customer')->group(function () {
        Route::post('/request-otp', [\App\Http\Controllers\Front\Auth\LoginController::class, 'requestOtp'])->name('login.step1');
        Route::post('/resend-otp', [\App\Http\Controllers\Front\Auth\LoginController::class, 'resendOtp'])->name('login.resend_otp');
        Route::post('/verify-otp', [\App\Http\Controllers\Front\Auth\LoginController::class, 'verifyOtp'])->name('login.step2');
        Route::post('/register', [\App\Http\Controllers\Front\Auth\LoginController::class, 'registerUser'])->name('login.register');
    });

    Route::middleware(['auth:customer', 'customer.not_blocked'])->group(function () {
        Route::post('/logout', [\App\Http\Controllers\Front\Auth\LoginController::class, 'logout'])->name('customer.logout');

        // Browser Web Push subscription for the logged-in web customer.
        Route::post('/push/subscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'subscribe'])->name('customer.push.subscribe');
        Route::delete('/push/unsubscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'unsubscribe'])->name('customer.push.unsubscribe');
        Route::controller(CustomerAccountController::class)->name('customer.')->prefix('customer')->group(function () {
            Route::get('account', 'profile')->name('account');
            Route::post('account-update', 'update')->name('profile-update');
            Route::get('get-booking', 'getBooking')->name('booking');
            Route::get('booking-details/{number_of_booking}', 'BookingDetails')->name('booking.details');
            Route::get('notifications', 'notifications')->name('notifications');
            Route::post('post-review', 'addReview')->name('post.review');
            Route::get('booking-details/{number_of_booking}/print', 'printBookingDetails')->name('booking.print_details');

        });

        // Wishlist (favorites) — own controller/service so favorites concerns stay isolated.
        Route::controller(WishlistController::class)->name('customer.')->prefix('customer')->group(function () {
            Route::get('favorite', 'index')->name('favorite');
            Route::post('toggle-favorite', 'toggle')->name('toggle.favorite');
        });
        Route::controller(BookingController::class)->name('web-booking.')->prefix('web-booking')->group(function () {
            Route::post('start-booking/{apartment_id}', 'determineBookingStatus')->name('determine');
            Route::get('confirm-booking/{uuid}', 'confirmBooking')->name('confirm-booking');
            Route::post('start-payment/{uuid}', 'startPayment')->name('add');
            // Graceful fallback: a GET on the POST-only payment route (browser back / direct visit)
            // → send the user back to the confirm page instead of a 405 Method Not Allowed.
            Route::get('start-payment/{uuid}', fn ($uuid) => redirect()->route('web-booking.confirm-booking', ['uuid' => $uuid]))->name('add.get');
            Route::get('{code}/callback/{transaction_id}', 'paymentMethodCallBack')->name('paymentMethodCallBack');
            Route::get('login-apartment', 'loginApartment')->name('login');
            Route::post('coupons-verify/{uuid}', 'couponsVerify')->name('coupons.verify');
            Route::post('remove-coupon/{uuid}', [BookingController::class, 'removeCoupon'])->name('coupons.remove');

            Route::post('cancel-booking', 'cancelBooking')->name('cancel');
            Route::post('withdraw-cancellation', 'withdrawCancellation')->name('withdraw-cancellation');

            // Date-change (edit booking dates) — quote, request, and surcharge payment return
            Route::post('calculate-date-change', 'calculateDateChange')->name('date-change.calculate');
            Route::post('request-date-change', 'requestDateChange')->name('date-change.request');
            Route::get('date-change/{request}/callback/{transaction}', 'dateChangeCallback')->name('date-change.callback');
            Route::post('date-change/{request}/retry-payment', 'retryDateChangePayment')->name('date-change.retry-payment');
            Route::post('date-change/{request}/cancel', 'cancelDateChangeRequest')->name('date-change.cancel');

            // Unit transfer — customer confirms / declines a staff-initiated move
            Route::post('unit-transfer/{transfer}/confirm', 'confirmUnitTransfer')->name('unit-transfer.confirm');
            Route::post('unit-transfer/{transfer}/decline', 'declineUnitTransfer')->name('unit-transfer.decline');

        });
    });

});
