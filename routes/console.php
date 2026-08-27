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

// إعادة محاولة عمليات كود الدخول الفاشلة (توليد أو إلغاء) كل 10 دقائق
Schedule::command('passcode:retry-failed')->cron('*/10 * * * *');

// كشف الحجوزات المعتمدة بدون كود دخول كل 10 دقائق
Schedule::command('booking:check-missing-passcodes')->cron('*/10 * * * *');

// إعادة مصادقة حسابات Sciener التي انتهت صلاحية التوكن الخاص بها أو ستنتهي قريباً —
// يكتشف تدهور بيانات الاعتماد بشكل دوري بدل انتظار فشل حجز حقيقي
Schedule::command('sciener:refresh-tokens')->daily();

// Schedule::command('import:airbnb-ics')->cron('*/10 * * * *');
// Schedule::command('ownerrez:sync-bookings')->everyFiveMinutes();
Schedule::command('ownerrez:warm-cache')->everyFiveMinutes();
Schedule::command('ownerrez:warm-daily-report')->everyFifteenMinutes();

// إرسال إشعارات الخروج يومياً في الساعة 10 صباحاً
Schedule::command('notifications:send-checkout-reminders')->dailyAt('10:00');

// تنظيف سجلات Activity Log القديمة يومياً
Schedule::command('activitylog:clean')->daily();

// تنظيف ملفات اللوج القديمة (أكثر من 60 يوم) يومياً
Schedule::command('log:clean')->daily();

Schedule::command('delete:pending-bookings')->everyTenMinutes();

// إعادة محاولة/تسوية عمليات الاسترداد العالقة (processing/failed) في جيديا
Schedule::command('refunds:reconcile')->everyTenMinutes();

// إعادة محاولة/تسوية استرداد فروق تعديل التواريخ العالقة (processing/failed)
Schedule::command('date-changes:reconcile-refunds')->everyTenMinutes();
Schedule::command('date-changes:reconcile-awaiting-payment')->everyTenMinutes();

// تحرير طلبات تعديل التواريخ العالقة بانتظار الدفع (يحرّر النافذة المحجوزة)
Schedule::command('date-changes:expire-unpaid')->everyTenMinutes();
