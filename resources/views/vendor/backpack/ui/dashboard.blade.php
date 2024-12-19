@extends(backpack_view('blank'))

@section('content')
<div class="row">
    <div class="col-md-6">
        {{-- Daily Occupancy Chart --}}
        @include('backpack.theme-coreuiv2::inc.widgets.chart', ['chart' => app()->make(\App\Http\Controllers\Admin\Charts\DailyOccupancyChartController::class)->chart])
    </div>
    <div class="col-md-6">
        {{-- Ongoing Bookings Chart --}}
        @include('backpack.theme-coreuiv2::inc.widgets.chart', ['chart' => app()->make(\App\Http\Controllers\Admin\Charts\OngoingBookingsChartController::class)->chart])
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        {{-- Units Available Chart --}}
        @include('backpack.theme-coreuiv2::inc.widgets.chart', ['chart' => app()->make(\App\Http\Controllers\Admin\Charts\UnitsAvailableChartController::class)->chart])
    </div>
    <div class="col-md-6">
        {{-- Average Rating Chart --}}
        @include('backpack.theme-coreuiv2::inc.widgets.chart', ['chart' => app()->make(\App\Http\Controllers\Admin\Charts\AverageRatingChartController::class)->chart])
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        {{-- Monthly Bookings Chart --}}
        @include('backpack.theme-coreuiv2::inc.widgets.chart', ['chart' => app()->make(\App\Http\Controllers\Admin\Charts\MonthlyBookingsChartController::class)->chart])
    </div>
</div>
@endsection
