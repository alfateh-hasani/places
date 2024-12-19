<?php

namespace App\Http\Controllers\Admin\Charts;

use Backpack\CRUD\app\Http\Controllers\ChartController;
use ConsoleTVs\Charts\Classes\Chartjs\Chart;
use App\Models\Customer;
use Carbon\Carbon;

/**
 * Class WeeklyCustomersChartController
 * @package App\Http\Controllers\Admin\Charts
 */
class TotalUsersChartController extends ChartController
{
    public function setup()
    {
        $this->chart = new Chart();

        // Set the chart type and the AJAX data loading URL
        $this->chart->type('line'); // نوع الرسم البياني 'line'
        $this->chart->load(backpack_url('charts/total-users'));
    }

    /**
     * Respond to AJAX calls with the chart data.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function data()
    {
        // Get the current date
        $end_date = Carbon::now();
        // Get the start date (7 weeks ago)
        $start_date = Carbon::now()->subWeeks(55);

        // Group customers by week and count the registrations
        $registrations_by_week = Customer::whereBetween('created_at', [$start_date, $end_date])
            ->selectRaw('YEARWEEK(created_at) as week, COUNT(*) as count')
            ->groupBy('week')
            ->orderBy('week')
            ->get();

        // Prepare data for the chart
        $labels = [];
        $data = [];

        foreach ($registrations_by_week as $week) {
            $week_number = $week->week;
            $labels[] = "Week " . substr($week_number, -2); // عرض الأسبوع بصيغة "Week XX"
            $data[] = $week->count;
        }

        // If no data, provide placeholder values
        if (empty($labels)) {
            $labels = ['No Data'];
            $data = [0];
        }

        // Set the chart data
        $this->chart->labels($labels);
        $this->chart->dataset('Customer Registrations by Week', 'line', $data)
            ->color('rgba(54, 162, 235, 1)')
            ->backgroundColor('rgba(54, 162, 235, 0.4)');

        return $this->chart->api();
    }
}
