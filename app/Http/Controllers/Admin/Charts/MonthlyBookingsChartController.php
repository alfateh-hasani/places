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

        // Set the chart type and the AJAX data loading URL
        $this->chart->type('line'); // يمكنك تغييره إلى 'bar'
        $this->chart->load(backpack_url('charts/monthly-bookings'));
    }

    public function data()
    {
        $start_of_month = Carbon::now()->startOfMonth();
        $end_of_month = Carbon::now()->endOfMonth();

        $labels = [];
        $data = [];

        for ($date = $start_of_month->copy(); $date->lte($end_of_month); $date->addDay()) {
            $labels[] = $date->format('d M'); // يوم وشهر
            $data[] = Booking::whereDate('created_at', $date->format('Y-m-d'))->count();
        }

        if (empty($data)) {
            $labels[] = 'No Data';
            $data[] = 0;
        }

        $this->chart->labels($labels);
        $this->chart->dataset('Bookings This Month', 'line', $data)
            ->color('rgba(75, 192, 192, 1)')
            ->backgroundColor('rgba(75, 192, 192, 0.4)');

        return $this->chart->api();
    }
}
