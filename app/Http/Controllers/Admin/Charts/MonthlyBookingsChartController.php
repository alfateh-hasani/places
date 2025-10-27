<?php

namespace App\Http\Controllers\Admin\Charts;

use Backpack\CRUD\app\Http\Controllers\ChartController;
use ConsoleTVs\Charts\Classes\Chartjs\Chart;
use App\Models\Booking;
use Carbon\Carbon;

class MonthlyBookingsChartController extends ChartController
{
    public function setup()
    {
        $this->chart = new Chart();

        // نوع المخطط (يمكن تغييره إلى 'bar' أو 'pie')
        $this->chart->type('line');

        // تحميل البيانات عبر Ajax
        $this->chart->load(backpack_url('charts/monthly-bookings'));
    }

    public function data()
    {
        $start_of_month = Carbon::now()->startOfMonth();
        $end_of_month = Carbon::now()->endOfMonth();

        // 📊 استعلام واحد لجلب عدد الحجوزات لكل يوم
        $bookings = Booking::selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->whereBetween('created_at', [$start_of_month, $end_of_month])
            ->groupBy('date')
            ->pluck('total', 'date'); // يُرجع [ '2025-10-01' => 5, '2025-10-02' => 7, ... ]

        $labels = [];
        $data = [];

        // نمرّ على كل أيام الشهر الحالي
        for ($date = $start_of_month->copy(); $date->lte($end_of_month); $date->addDay()) {
            $dateString = $date->format('Y-m-d');
            $labels[] = $date->format('d M'); // مثال: "01 Oct"
            $data[] = $bookings[$dateString] ?? 0; // إذا لا يوجد حجز في اليوم = صفر
        }

        // معالجة حالة عدم وجود بيانات
        if (empty($data)) {
            $labels[] = 'No Data';
            $data[] = 0;
        }

        // إعداد بيانات المخطط
        $this->chart->labels($labels);
        $this->chart->dataset(__('cms.monthly_bookings'), 'line', $data)
            ->color('rgba(75, 192, 192, 1)')
            ->backgroundColor('rgba(75, 192, 192, 0.4)');

        return $this->chart->api();
    }
}
