<?php

namespace App\Http\Controllers\Admin\Widgets;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\ServiceBooking;
use App\Models\Customer;
use App\Models\Review;
use Illuminate\Http\Request;
use Carbon\Carbon;

class RecentActivitiesWidgetController extends Controller
{
    public function data()
    {
        $activities = collect();

        // أحدث الحجوزات
        $recentBookings = Booking::with(['customer', 'apartment.building'])
            ->whereHas('customer')
            ->whereHas('apartment')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($booking) {
                return [
                    'type' => 'booking',
                    'icon' => 'la-calendar',
                    'color' => 'primary',
                    'title' => 'حجز جديد',
                    'description' => "حجز جديد من " . ($booking->customer->full_name ?? 'عميل غير معروف') . " للعقار " . ($booking->apartment->name_ar ?? 'عقار غير معروف'),
                    'time' => $booking->created_at,
                    'url' => backpack_url('booking/' . $booking->id . '/show'),
                ];
            });

        // أحدث طلبات الخدمات
        $recentServiceBookings = ServiceBooking::with(['customer', 'service', 'apartment.building'])
            ->whereHas('customer')
            ->whereHas('service')
            ->whereHas('apartment')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($serviceBooking) {
                return [
                    'type' => 'service',
                    'icon' => 'la-concierge-bell',
                    'color' => 'warning',
                    'title' => 'طلب خدمة جديد',
                    'description' => "طلب خدمة " . ($serviceBooking->service->name_ar ?? 'خدمة غير معروفة') . " من " . ($serviceBooking->customer->full_name ?? 'عميل غير معروف'),
                    'time' => $serviceBooking->created_at,
                    'url' => backpack_url('service-booking/' . $serviceBooking->id . '/show'),
                ];
            });

        // أحدث التقييمات
        $recentReviews = Review::with(['customer', 'apartment.building'])
            ->whereHas('customer')
            ->whereHas('apartment')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($review) {
                return [
                    'type' => 'review',
                    'icon' => 'la-star',
                    'color' => 'success',
                    'title' => 'تقييم جديد',
                    'description' => "تقييم جديد من " . ($review->customer->full_name ?? 'عميل غير معروف') . " للعقار " . ($review->apartment->name_ar ?? 'عقار غير معروف'),
                    'time' => $review->created_at,
                    'url' => backpack_url('review/' . $review->id . '/show'),
                ];
            });

        // أحدث العملاء المسجلين
        $recentCustomers = Customer::orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($customer) {
                return [
                    'type' => 'customer',   
                    'icon' => 'la-user-plus',
                    'color' => 'info',
                    'title' => 'عميل جديد',
                    'description' => "انضمام عميل جديد: " . ($customer->full_name ?? 'عميل غير معروف'),
                    'time' => $customer->created_at,
                    'url' => backpack_url('customer/' . $customer->id . '/show'),
                ];
            });

        // دمج جميع الأنشطة وترتيبها حسب الوقت
        $activities = $activities
            ->merge($recentBookings)
            ->merge($recentServiceBookings)
            ->merge($recentReviews)
            ->merge($recentCustomers)
            ->sortByDesc('time')
            ->take(10);

        return [
            'activities' => $activities->values()->toArray(),
        ];
    }
}
