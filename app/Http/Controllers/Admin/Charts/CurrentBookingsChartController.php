<?php

namespace App\Http\Controllers\Admin\Charts;

use Backpack\CRUD\app\Http\Controllers\ChartController;
use ConsoleTVs\Charts\Classes\Chartjs\Chart;
use Carbon\Carbon;
use App\Models\Booking; // Make sure you have this model
use Illuminate\Http\Request;

/**
 * Class CurrentBookingsChartController
 * @package App\Http\Controllers\Admin\Charts
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class CurrentBookingsChartController extends ChartController
{
    public function setup()
    {
        $this->chart = new Chart();

        // Set an empty array for labels; we'll populate them in the data() method
        $this->chart->labels([]);

        // Set the AJAX route for fetching data
        $this->chart->load(backpack_url('charts/current-bookings'));

        // Optional configurations
        $this->chart->minimalist(false);
        $this->chart->displayLegend(true);
    }

    /**
     * Respond to AJAX calls with all the chart data points.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function data()
    {
        // Initialize arrays to hold dates and booking counts
        $dates = [];
        $bookingCounts = [];

        // Get data for the last 7 days
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $formattedDate = $date->format('Y-m-d');

            // Count bookings for each day
            $count = Booking::whereDate('created_at', $formattedDate)->count();

            // Populate the arrays
            $dates[] = $date->format('M d'); // e.g., "Oct 01"
            $bookingCounts[] = $count;
        }

        // Set labels and dataset
        $this->chart->labels($dates);

        $this->chart->dataset('Bookings in the Last 7 Days', 'line', $bookingCounts)
            ->color('rgba(54, 162, 235, 1)')
            ->backgroundColor('rgba(54, 162, 235, 0.4)');

        // Return the chart's JSON data
        return $this->chart->api();
    }
}
