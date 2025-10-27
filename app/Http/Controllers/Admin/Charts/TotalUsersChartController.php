<?php

namespace App\Http\Controllers\Admin\Charts;

use Backpack\CRUD\app\Http\Controllers\ChartController;
use ConsoleTVs\Charts\Classes\Chartjs\Chart;
use App\Models\Customer;
use Carbon\Carbon;

class TotalUsersChartController extends ChartController
{
    public function setup()
    {
        $this->chart = new Chart();

        $labels = [];
        $data = [];
        $monthsToLookBack = 12;

        Carbon::setLocale('ar'); 

        for ($i = $monthsToLookBack - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);

            $labels[] = $date->isoFormat('MMM YYYY'); 

            $endOfMonth = $date->copy()->endOfMonth()->toDateTimeString();

            $count = Customer::where('created_at', '<=', $endOfMonth)->count(); 
            $data[] = $count;
        }
        
        $this->chart->options([
            'scales' => [
                'yAxes' => [
                    [
                        'ticks' => [
                            'beginAtZero' => true,
                            'stepSize' => 1, 
                        ]
                    ]
                ]
            ]
        ]);
        
        $this->chart->title('إجمالي العملاء التراكمي على مدى آخر 12 شهرًا');

        $this->chart->dataset('إجمالي العملاء', 'bar', $data) 
             ->backgroundColor('rgba(70, 127, 208, 0.7)')
             ->options([
                 'borderColor' => 'rgb(70, 127, 208)',
                 'borderWidth' => 1,
             ]);

        $this->chart->displayAxes(true);
        $this->chart->displayLegend(true);
        
        $this->chart->labels($labels);
    }
}
