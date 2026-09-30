<?php

use App\Http\Controllers\Admin\BookingCancellationController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\BookingUnitTransferController;
use App\Http\Controllers\Admin\CalenderController;
use App\Http\Controllers\Admin\Charts\AverageRatingChartController;
use App\Http\Controllers\Admin\Charts\DailyOccupancyChartController;
use App\Http\Controllers\Admin\Charts\MonthlyBookingsChartController;
use App\Http\Controllers\Admin\Charts\OngoingBookingsChartController;
use App\Http\Controllers\Admin\Charts\TotalUsersChartController;
use App\Http\Controllers\Admin\Charts\UnitsAvailableChartController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DateChangeRequestCrudController;
use App\Http\Controllers\Admin\DirectBookingController;
use App\Http\Controllers\Admin\RefundCrudController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\WebNotificationController;
use App\Http\Controllers\PushSubscriptionController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace' => 'App\Http\Controllers\Admin',
], function () {
    // Browser Web Push subscription for the logged-in staff/admin user.
    Route::post('push/subscribe', [PushSubscriptionController::class, 'subscribe'])->name('admin.push.subscribe');
    Route::delete('push/unsubscribe', [PushSubscriptionController::class, 'unsubscribe'])->name('admin.push.unsubscribe');

    // Staff notification bell (in-app web_notifications).
    Route::get('web-notifications', [WebNotificationController::class, 'index'])->name('admin.web-notifications.index');
    Route::post('web-notifications/read-all', [WebNotificationController::class, 'markAllAsRead'])->name('admin.web-notifications.read-all');
    Route::post('web-notifications/{id}/read', [WebNotificationController::class, 'markAsRead'])->name('admin.web-notifications.read');

    Route::crud('smart-lock', 'LockSmartController');
    Route::get('get-smart-locks', 'LockSmartController@getSmartLocks');
    Route::crud('city', 'CityController');
    Route::crud('coupon', 'CouponController');
    Route::crud('feature', 'FeatureController');
    Route::crud('apartment', 'ApartmentController');
    Route::crud('building', 'BuildingController');
    Route::post('building/{id}/test-sciener-connection', 'BuildingController@testScienerConnection')
        ->name('admin.building.test-sciener-connection');
    Route::put('building/{id}/toggle-active', 'BuildingController@toggleActive')
        ->name('admin.building.toggle-active');
    Route::crud('apartment-label', 'ApartmentLabelController');
    Route::crud('ownerrez-property-mapping', 'OwnerRezPropertyMappingController');
    Route::get('ownerrez-property-mapping/{id}/sync', 'OwnerRezPropertyMappingController@sync');
    Route::get('api/ownerrez-properties', 'OwnerRezPropertyController@index')->name('admin.ownerrez-properties.index')->middleware('staff.can:apartment.update');
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
    Route::post('customer/{id}/block', [CustomerController::class, 'block'])->name('admin.customer.block');
    Route::post('customer/{id}/unblock', [CustomerController::class, 'unblock'])->name('admin.customer.unblock');
    Route::post('customer/reset-otp-by-phone', [CustomerController::class, 'resetOtpByPhone'])->name('admin.customer.reset_otp_by_phone');
    Route::post('customer/{id}/reset-otp', [CustomerController::class, 'resetOtp'])->name('admin.customer.reset_otp');
    Route::crud('service', 'ServiceController');

    Route::post('booking/{id}/change-status/{status}', [BookingController::class, 'changeStatus'])->name('admin.booking.change-status');
    // Confirm a pending booking (Geidea-first, else bank transfer) + reject a pending booking.
    Route::post('booking/{id}/confirm', [BookingController::class, 'confirmBooking'])->name('admin.booking.confirm');
    // Route for changing payment status
    Route::post('booking/{id}/change-payment-status/{status}', [BookingController::class, 'changePaymentStatus'])->name('admin.booking.change-payment-status');

    // Booking cancellation → refund actions (surfaced on the booking itself via the
    // "Manage cancellation" modal — no separate page). Two-step: finalize, then refund.
    Route::post('booking/{id}/cancel-local', [BookingCancellationController::class, 'cancelLocal'])->name('admin.booking.cancel-local');
    Route::post('booking/{id}/refund', [BookingCancellationController::class, 'refund'])->name('admin.booking.refund');
    Route::post('booking/{id}/reject-cancellation', [BookingCancellationController::class, 'reject'])->name('admin.booking.reject-cancellation');

    // Refunds tracker (follow the money)
    Route::crud('refund', 'RefundCrudController');
    Route::post('refund/{id}/retry', [RefundCrudController::class, 'retry'])->name('admin.refund.retry');

    // Date-change requests — review the "cheaper" path (apply + refund the difference)
    Route::crud('date-change-requests', 'DateChangeRequestCrudController');
    Route::post('date-change-requests/{id}/approve', [DateChangeRequestCrudController::class, 'approve'])->name('admin.date-change-requests.approve');
    Route::post('date-change-requests/{id}/reject', [DateChangeRequestCrudController::class, 'reject'])->name('admin.date-change-requests.reject');
    Route::post('date-change-requests/{id}/retry', [DateChangeRequestCrudController::class, 'retry'])->name('admin.date-change-requests.retry');

    // Daily Occupancy
    Route::get('charts/daily-occupancy', [DailyOccupancyChartController::class, 'data'])->name('charts.daily-occupancy');

    // Ongoing Bookings
    Route::get('charts/ongoing-bookings', [OngoingBookingsChartController::class, 'data'])->name('charts.ongoing-bookings');

    // Units Available
    Route::get('charts/units-available', [UnitsAvailableChartController::class, 'data'])->name('charts.units-available');

    // Average Rating
    Route::get('charts/average-rating', [AverageRatingChartController::class, 'data'])->name('charts.average-rating');

    // Monthly Bookings
    Route::get('charts/monthly-bookings', [MonthlyBookingsChartController::class, 'data'])->name('charts.monthly-bookings');

    Route::get('charts/total-users', [TotalUsersChartController::class, 'data'])
        ->name('charts.total-users');
    Route::get('reports', [ReportsController::class, 'index'])->name('admin.reports.index')->middleware('staff.can:booking.list');
    Route::get('/reports/daily-checkout', [ReportsController::class, 'dailyCheckOutReport'])->name('admin.reports.daily-checkout')->middleware('staff.can:booking.list');
    Route::get('/reports/daily-checkout/ownerrez', [ReportsController::class, 'ownerRezCheckoutToday'])->name('admin.reports.daily-checkout.ownerrez')->middleware('staff.can:booking.list');
    Route::get('/reports/daily-checkout/ownerrez-maintenance', [ReportsController::class, 'ownerRezMaintenanceToday'])->name('admin.reports.daily-checkout.ownerrez-maintenance')->middleware('staff.can:booking.list');
    Route::get('/reports/daily-checkin', [ReportsController::class, 'dailyCheckInReport'])->name('admin.reports.daily-checkin')->middleware('staff.can:booking.list');
    Route::get('/reports/daily-checkin/ownerrez', [ReportsController::class, 'ownerRezCheckinToday'])->name('admin.reports.daily-checkin.ownerrez')->middleware('staff.can:booking.list');
    Route::get('/reports/daily-checkin/ownerrez-maintenance', [ReportsController::class, 'ownerRezMaintenanceCheckinToday'])->name('admin.reports.daily-checkin.ownerrez-maintenance')->middleware('staff.can:booking.list');
    Route::crud('contact-us', 'ContactUsCrudController');
    Route::crud('service-booking', 'ServiceBookingCrudController');
    Route::crud('review', 'ReviewCrudController');

    // حجز مباشر من لوحة التحكم (تحويل بنكي)
    Route::get('direct-booking', [DirectBookingController::class, 'create'])->name('admin.direct-booking.create');
    Route::get('direct-booking/customers', [DirectBookingController::class, 'customerSearch'])->name('admin.direct-booking.customers');
    Route::post('direct-booking/price-preview', [DirectBookingController::class, 'pricePreview'])->name('admin.direct-booking.price-preview');
    Route::post('direct-booking', [DirectBookingController::class, 'store'])->name('admin.direct-booking.store');

    // نقل الحجز إلى وحدة أخرى (يبدأه الموظف، ويؤكده العميل) — بلا صلاحية خاصة:
    // متاح لكل من يستطيع عرض الحجوزات. راجع BookingUnitTransferController.
    Route::get('booking/{id}/transfer-unit', [BookingUnitTransferController::class, 'create'])->name('admin.booking.transfer-unit.create');
    Route::post('booking/{id}/transfer-unit/price-preview', [BookingUnitTransferController::class, 'pricePreview'])->name('admin.booking.transfer-unit.price-preview');
    Route::post('booking/{id}/transfer-unit', [BookingUnitTransferController::class, 'store'])->name('admin.booking.transfer-unit.store');
    Route::post('unit-transfer/{transfer}/cancel', [BookingUnitTransferController::class, 'cancel'])->name('admin.unit-transfer.cancel');
    Route::post('unit-transfer/{transfer}/retry', [BookingUnitTransferController::class, 'retry'])->name('admin.unit-transfer.retry');
    Route::post('unit-transfer/{transfer}/refund', [BookingUnitTransferController::class, 'refund'])->name('admin.unit-transfer.refund');
    Route::post('unit-transfer/{transfer}/mark-ownerrez-cancelled', [BookingUnitTransferController::class, 'markOwnerRezCancelled'])->name('admin.unit-transfer.mark-ownerrez-cancelled');
    Route::post('unit-transfer/{transfer}/retry-park', [BookingUnitTransferController::class, 'retryPark'])->name('admin.unit-transfer.retry-park');

    // تقويم الحجوزات
    Route::get('apartment/{id}/calendar', [CalenderController::class, 'showCalendar'])->middleware('staff.can:apartment.list');
    Route::get('apartment/{id}/bookings', [CalenderController::class, 'getApartmentBookings'])->middleware('staff.can:apartment.list');
    Route::get('apartment/{id}/ownerrez-bookings', [CalenderController::class, 'getOwnerRezBookings'])->middleware('staff.can:apartment.list');

    // تقويم الأسعار (منفصل)
    Route::get('apartment/{id}/pricing', [CalenderController::class, 'showPricingCalendar'])->middleware('staff.can:apartment.list');
    Route::get('apartment/{id}/custom-prices', [CalenderController::class, 'getCustomPrices'])->middleware('staff.can:apartment.list');
    Route::post('apartment/{id}/custom-price', [CalenderController::class, 'saveCustomPrice'])->middleware('staff.can:apartment.update');
    Route::delete('apartment/{id}/custom-price/{priceId}', [CalenderController::class, 'deleteCustomPrice'])->middleware('staff.can:apartment.update');
    Route::post('apartment/{id}/preview-pricing', [CalenderController::class, 'previewPricing'])->middleware('staff.can:apartment.list');

    Route::crud('category', 'CategoryCrudController');
    Route::crud('onboarding', 'OnboardingCrudController');

    // تعارضات قنوات الحجز
    Route::crud('booking-channel-conflicts', 'BookingChannelConflictController');
    Route::get('booking-channel-conflicts/{id}/resolve-conflict', 'BookingChannelConflictController@resolveConflict');
    Route::get('booking-channel-conflicts/resolve-all-conflicts', 'BookingChannelConflictController@resolveAllConflicts');

    // OwnerRez OAuth
    Route::get('ownerrez/oauth', 'OwnerRezOAuthController@index')->name('admin.ownerrez.oauth')->middleware('staff.can:role.update');
    Route::get('ownerrez/oauth/authorize', 'OwnerRezOAuthController@authorize')->name('admin.ownerrez.oauth.authorize')->middleware('staff.can:role.update');
    Route::delete('ownerrez/oauth/disconnect', 'OwnerRezOAuthController@disconnect')->name('admin.ownerrez.oauth.disconnect')->middleware('staff.can:role.update');
});
