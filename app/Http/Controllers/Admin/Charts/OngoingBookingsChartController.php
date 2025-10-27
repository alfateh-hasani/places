<?php

namespace App\Http\Controllers\Admin\Charts;

use Backpack\CRUD\app\Http\Controllers\ChartController;
use ConsoleTVs\Charts\Classes\Chartjs\Chart;
use Carbon\Carbon;
use App\Models\Booking;

class OngoingBookingsChartController extends ChartController
{
    public function setup()
    {
        $this->chart = new Chart();
        $this->chart->load(backpack_url('charts/ongoing-bookings'));
    }

    public function data()
    {
        $current_date = Carbon::today();
        $ongoing_bookings = Booking::where('check_in', '=', $current_date)
            // ->where('check_out', '>=', $current_date)
            ->count();

        // في هذه الحالة مجرد قيمة واحدة (مثلاً يعرض عدد الحجوزات الجارية كرقم)
        $this->chart->labels([ 'Today' ]);
        $this->chart->dataset(__('cms.ongoing_bookings'), 'bar', [$ongoing_bookings])
            ->color('rgba(255, 99, 132, 1)')
            ->backgroundColor('rgba(255, 99, 132, 0.4)');

        return $this->chart->api();
    }
}
