<?php

namespace App\Http\Controllers\Admin\Charts;
use Backpack\CRUD\app\Http\Controllers\ChartController;
use ConsoleTVs\Charts\Classes\Chartjs\Chart;
use Carbon\Carbon;
use App\Models\Booking;
use App\Models\Apartment;

class UnitsAvailableChartController extends ChartController
{
    public function setup()
    {
        $this->chart = new Chart();
        $this->chart->load(backpack_url('charts/units-available'));
    }

    public function data()
    {
        $current_date = Carbon::today();
        $total_units = Apartment::count();
        $ongoing_bookings = Booking::where('check_in', '<=', $current_date)
            ->where('check_out', '>=', $current_date)
            ->count();
        $units_available = $total_units - $ongoing_bookings;

        $this->chart->labels(['Available Units']);
        $this->chart->dataset('Units Available', 'doughnut', [$units_available, $ongoing_bookings])
            ->backgroundColor(['rgba(75, 192, 192, 0.4)','rgba(201, 203, 207, 0.4)'])
            ->color(['rgba(75, 192, 192, 1)','rgba(201, 203, 207, 1)']) ;
            $this->chart->displayLegend(true);

        return $this->chart->api();
    }
}
