<?php

namespace App\Http\Controllers\Admin\Charts;

use Backpack\CRUD\app\Http\Controllers\ChartController;
use ConsoleTVs\Charts\Classes\Chartjs\Chart;
use App\Models\Review;

class AverageRatingChartController extends ChartController
{
    public function setup()
    {
        $this->chart = new Chart();
        $this->chart->load(backpack_url('charts/average-rating'));
    }

    public function data()
    {
        $average_rating = Review::avg('rating') ?? 0;
        
        $this->chart->labels(['Average Rating']);
        $this->chart->dataset('Average Rating', 'bar', [round($average_rating, 2)])
            ->color('rgba(153, 102, 255, 1)')
            ->backgroundColor('rgba(153, 102, 255, 0.4)');

        return $this->chart->api();
    }
}
