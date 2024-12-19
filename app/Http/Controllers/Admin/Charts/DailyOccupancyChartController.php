<?php

namespace App\Http\Controllers\Admin\Charts;

use Backpack\CRUD\app\Http\Controllers\ChartController;
use ConsoleTVs\Charts\Classes\Chartjs\Chart;
use Carbon\Carbon;
use App\Models\Booking;
use App\Models\Apartment;

class DailyOccupancyChartController extends ChartController
{
    public function setup()
    {
        $this->chart = new Chart();
        $this->chart->load(backpack_url('charts/daily-occupancy'));
    }

    public function data()
    {
        $startDate = Carbon::today()->subDays(6);
        $endDate = Carbon::today();
        $total_units = Apartment::count();
        $dates = [];
        $daily_occupancy = [];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $booked_units_count = Booking::where('check_in', '<=', $date->format('Y-m-d'))
                ->where('check_out', '>=', $date->format('Y-m-d'))
                ->count();

            $occupancy_percent = $total_units > 0 ? ($booked_units_count / $total_units) * 100 : 0;
            $daily_occupancy[] = round($occupancy_percent, 2);
            $dates[] = $date->format('d M');
        }

        $this->chart->labels($dates);
        $this->chart->dataset('Daily Occupancy (%)', 'line', $daily_occupancy)
            ->color('rgba(54, 162, 235, 1)')
            ->backgroundColor('rgba(54, 162, 235, 0.4)');

        return $this->chart->api();
    }
}
