<?php

namespace App\Http\Controllers\Admin\Charts;

use Backpack\CRUD\app\Http\Controllers\ChartController;
use ConsoleTVs\Charts\Classes\Chartjs\Chart;
use Carbon\Carbon;
use App\Models\Booking;

class MonthlyBookingsChartController extends ChartController
{
    public function setup()
    {
        $this->chart = new Chart();
        $this->chart->load(backpack_url('charts/monthly-bookings'));
    }

    public function data()
    {
        $month_start = Carbon::now()->startOfMonth();
        $month_end = Carbon::now()->endOfMonth();
        $monthly_bookings = Booking::whereBetween('created_at', [$month_start, $month_end])->count();

        $this->chart->labels([ $month_start->format('F Y') ]);
        $this->chart->dataset('Monthly Bookings', 'line', [$monthly_bookings])
            ->color('rgba(255, 159, 64, 1)')
            ->backgroundColor('rgba(255, 159, 64, 0.4)');

        return $this->chart->api();
    }
}
