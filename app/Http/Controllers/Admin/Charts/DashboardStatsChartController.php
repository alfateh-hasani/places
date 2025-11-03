<?php

namespace App\Http\Controllers\Admin\Charts;

use Backpack\CRUD\app\Http\Controllers\ChartController;
use ConsoleTVs\Charts\Classes\Chartjs\Chart;
use Carbon\Carbon;

// تأكد من وجود هذه الموديلات (تعديل أسماء الموديلات والمسارات حسب احتياجك)
use App\Models\Booking;
use App\Models\Apartment as Unit;
use App\Models\User;
use App\Models\Rating;
use App\Models\Visit; // في حال كان لديك جدول للزيارات

class DashboardStatsChartController extends ChartController
{
    public function setup()
    {
        $this->chart = new Chart();
        $this->chart->labels([]);
        // تحديد أن الشارت سيقوم بجلب البيانات عبر AJAX
        $this->chart->load(backpack_url('charts/dashboard-stats'));
        $this->chart->type('line');

        // Optional configurations
        $this->chart->minimalist(false);
        $this->chart->displayLegend(true);
    }

    /**
     * دالة إحضار البيانات عبر AJAX
     */
    public function data()
    {
        // مثال: تحديد الفترة الزمنية (الأيام السبعة الأخيرة)
        $startDate = Carbon::now()->subDays(6);
        $endDate = Carbon::now();

        // 1. معدل الإشغال اليومي (%)
        // نفترض أن لدينا عدد وحدات ثابت total_units 
        // ونحسب عدد الوحدات المحجوزة يومياً / total_units * 100
        $total_units = Unit::count();
        $daily_occupancy = [];
        $dates = [];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $booked_units_count = Booking::whereDate('created_at', $date->format('Y-m-d'))
                // نفترض أن الحجز الجاري يعني Check_in <= date <= check_out
                ->where('check_in', '<=', $date->format('Y-m-d'))
                ->where('check_out', '>=', $date->format('Y-m-d'))
                ->count();

            $occupancy_percent = $total_units > 0 ? ($booked_units_count / $total_units) * 100 : 0;
            $daily_occupancy[] = round($occupancy_percent, 2);
            $dates[] = $date->format('d M');
        }

        // 2. عدد الحجوزات الجارية (مثال: اليوم)
        $current_date = Carbon::today();
        $ongoing_bookings = Booking::where('check_in', '<=', $current_date)
            ->where('check_out', '>=', $current_date)
            ->count();

        // 3. عدد الوحدات المتاحة
        // الوحدات المتاحة = total_units - الوحدات المحجوزة حالياً
        $units_available = $total_units - $ongoing_bookings;

        // 4. متوسط تقييم العملاء
        // نفترض وجود جدول ratings به حقل rating
        $average_rating =   0;

        // 5. إجمالي العملاء
        $total_customers = User::count();

        // 6. نسبة الإشغال على مدى فترة زمنية (مثلاً الشهر الحالي)
        // نحسب عدد الأيام المشغولة على مدى الشهر / عدد الأيام المتاحة
        $month_start = Carbon::now()->startOfMonth();
        $month_end = Carbon::now()->endOfMonth();
        $month_days = $month_start->diffInDays($month_end) + 1;

        $occupied_days = 0;
        for ($d = $month_start->copy(); $d->lte($month_end); $d->addDay()) {
            $booked_units_today = Booking::where('check_in', '<=', $d->format('Y-m-d'))
                ->where('check_out', '>=', $d->format('Y-m-d'))
                ->count();
            if ($booked_units_today > 0) {
                $occupied_days++;
            }
        }
        $monthly_occupancy_rate = $month_days > 0 ? ($occupied_days / $month_days) * 100 : 0;

        // 7. عدد الحجوزات اليومية أو الشهرية
        // الحجوزات اليومية (اليوم)
        $daily_bookings = Booking::whereDate('created_at', $current_date)->count();
        // الحجوزات الشهرية
        $monthly_bookings = Booking::whereBetween('created_at', [$month_start, $month_end])->count();

        // 8. مدة الإقامة المتوسطة لكل حجز
        // نفترض أن مدة الإقامة = difference بين check_in و check_out
        $avg_stay = Booking::selectRaw('AVG(DATEDIFF(check_out, check_in)) as avg_stay')->first()->avg_stay ?? 0;

        // 9. نسبة الحجوزات المكررة: 
        // العملاء الذين لديهم أكثر من حجز واحد بالنسبة لإجمالي العملاء
        $repeat_bookings_count = Booking::select('customer_id')
            ->groupBy('customer_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();
        $repeat_bookings_rate = $total_customers > 0 ? ($repeat_bookings_count / $total_customers) * 100 : 0;

        // 10. عدد الحجوزات الملغاة وحصتها من إجمالي الحجوزات
        $total_bookings = Booking::count();
        $canceled_bookings_count = Booking::whereIn('status', ['canceled', 'customer_canceled'])->count();
        $canceled_bookings_rate = $total_bookings > 0 ? ($canceled_bookings_count / $total_bookings) * 100 : 0;

        // 11. عدد زوار الموقع 
        // نفترض وجود جدول Visits يسجل الزيارات
        // مثلاً زوار الشهر
        $monthly_visitors = 0;

        // الآن نرجع البيانات كـ JSON
        // يمكنكم دمج هذه الإحصائيات في رسوم بيانية متعددة، أو إعادة JSON به بيانات مختلفة.
        
        // سنرجع بيانات متعددة، مثلاً:
        // - شارت معدل الاشغال اليومي
        // - القيم الأخرى كـ data إضافية
        return response()->json([
            'daily_occupancy_chart' => [
                'labels' => $dates,
                'data' => $daily_occupancy,
            ],
            'ongoing_bookings' => $ongoing_bookings,
            'units_available' => $units_available,
            'average_rating' => round($average_rating, 2),
            'total_customers' => $total_customers,
            'monthly_occupancy_rate' => round($monthly_occupancy_rate, 2),
            'daily_bookings' => $daily_bookings,
            'monthly_bookings' => $monthly_bookings,
            'avg_stay' => round($avg_stay, 2),
            'repeat_bookings_rate' => round($repeat_bookings_rate, 2),
            'canceled_bookings_count' => $canceled_bookings_count,
            'canceled_bookings_rate' => round($canceled_bookings_rate, 2),
            'monthly_visitors' => $monthly_visitors,
        ]);
    }
}
