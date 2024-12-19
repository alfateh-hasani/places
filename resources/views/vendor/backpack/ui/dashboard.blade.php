@extends(backpack_view('blank'))

@section('content')

@php
    // إضافة ودجت معدل الإشغال اليومي
    Widget::add([
        'type'       => 'chart',
        'controller' => \App\Http\Controllers\Admin\Charts\DailyOccupancyChartController::class,
        'class'   => 'card mb-2',
        'wrapper' => ['class'=> 'col-md-6'],
        'content' => [
             'header' => 'Daily Occupancy (%)',
             'body'   => 'This chart shows the daily occupancy rate for the last 7 days.',
        ],
    ]);

    // إضافة ودجت الحجوزات الجارية
    Widget::add([
        'type'       => 'chart',
        'controller' => \App\Http\Controllers\Admin\Charts\OngoingBookingsChartController::class,
        'class'   => 'card mb-2',
        'wrapper' => ['class'=> 'col-md-6'],
        'content' => [
             'header' => 'Ongoing Bookings',
             'body'   => 'This chart shows the number of ongoing bookings for today.',
        ],
    ]);

    // إضافة ودجت عدد الوحدات المتاحة
    Widget::add([
        'type'       => 'chart',
        'controller' => \App\Http\Controllers\Admin\Charts\UnitsAvailableChartController::class,
        'class'   => 'card mb-2',
        'wrapper' => ['class'=> 'col-md-6'],
        'content' => [
             'header' => 'Units Available',
             'body'   => 'This chart shows the current number of available units.',
        ],
    ]);

    // إضافة ودجت متوسط تقييم العملاء
    Widget::add([
        'type'       => 'chart',
        'controller' => \App\Http\Controllers\Admin\Charts\AverageRatingChartController::class,
        'class'   => 'card mb-2',
        'wrapper' => ['class'=> 'col-md-6'],
        'content' => [
             'header' => 'Average Customer Rating',
             'body'   => 'This chart shows the average customer rating.',
        ],
    ]);

    // إضافة ودجت الحجوزات الشهرية
    Widget::add([
        'type'       => 'chart',
        'controller' => \App\Http\Controllers\Admin\Charts\MonthlyBookingsChartController::class,
        'class'   => 'card mb-2',
        'wrapper' => ['class'=> 'col-md-6'],
        'content' => [
             'header' => 'Monthly Bookings',
             'body'   => 'This chart shows the total number of bookings this month.',
        ],
    ]);
@endphp


 

@endsection
