@extends(backpack_view('blank'))

@section('content')

@php
    // معدل الإشغال اليومي
    Widget::add([
        'type'       => 'chart',
        'controller' => \App\Http\Controllers\Admin\Charts\DailyOccupancyChartController::class,
        'class'   => 'card mb-2',
        'wrapper' => ['class'=> 'col-md-4'],
        'group' => 'content',
        'content' => [
             'header' => 'معدل الإشغال اليومي (%)',
             'body'   => 'يوضح هذا المخطط معدل الإشغال اليومي خلال آخر 7 أيام.',
        ],
    ]) ;

    // الحجوزات الجارية
    Widget::add([
        'type'       => 'chart',
        'controller' => \App\Http\Controllers\Admin\Charts\OngoingBookingsChartController::class,
        'class'   => 'card mb-2',
        'wrapper' => ['class'=> 'col-md-4'],
        'group' => 'content',
        'content' => [
             'header' => 'الحجوزات الجارية',
             'body'   => 'يوضح هذا المخطط عدد الحجوزات الجارية لليوم الحالي.',
        ],
    ]) ;

    // عدد الوحدات المتاحة
    Widget::add([
        'type'       => 'chart',
        'controller' => \App\Http\Controllers\Admin\Charts\UnitsAvailableChartController::class,
        'class'   => 'card mb-2',
        'wrapper' => ['class'=> 'col-md-4'],
        'group' => 'content',
        'content' => [
             'header' => 'الوحدات المتاحة',
             'body'   => 'يوضح هذا المخطط عدد الوحدات المتاحة حاليًا.',
        ],
    ]) ;

    // متوسط تقييم العملاء
    Widget::add([
        'type'       => 'chart',
        'controller' => \App\Http\Controllers\Admin\Charts\AverageRatingChartController::class,
        'class'   => 'card mb-2',
        'wrapper' => ['class'=> 'col-md-4'],
        'group' => 'content',
        'content' => [
             'header' => 'متوسط تقييم العملاء',
             'body'   => 'يوضح هذا المخطط متوسط تقييم العملاء.',
        ],
    ]) ;

    // الحجوزات الشهرية
    Widget::add([
        'type'       => 'chart',
        'controller' => \App\Http\Controllers\Admin\Charts\MonthlyBookingsChartController::class,
        'class'   => 'card mb-2',
        'wrapper' => ['class'=> 'col-md-4'],
        'group' => 'content',
        'content' => [
             'header' => 'الحجوزات الشهرية',
             'body'   => 'يوضح هذا المخطط إجمالي عدد الحجوزات لهذا الشهر.',
        ],
    ]) ;

     // عدد المستخدمين المسجلين
     Widget::add([
        'type'       => 'chart',
        'controller' => \App\Http\Controllers\Admin\Charts\TotalUsersChartController::class,
        'class'      => 'card mb-2',
        'wrapper'    => ['class' => 'col-md-4'],
        'group'      => 'content',
        'content'    => [
            'header' => 'إجمالي المستخدمين المسجلين',
            'body'   => 'يوضح هذا المخطط العدد الإجمالي للمستخدمين المسجلين في النظام.',
        ],
    ]);

    // أحدث الأنشطة
    Widget::add([
        'type'       => 'view',
        'view'       => 'vendor.backpack.ui.widgets.recent_activities',
        'class'      => 'card mb-2',
        'wrapper'    => ['class' => 'col-md-12'],
        'group'      => 'content',
        'content'    => [
            'activities' => app(\App\Http\Controllers\Admin\Widgets\RecentActivitiesWidgetController::class)->data()['activities'],
        ],
    ]);
@endphp

@endsection

@section('before_content_widgets')
<div class="row">
	@include(backpack_view('inc.widgets'), [ 'widgets' => app('widgets')->where('section', 'before_content')->toArray() ])
</div>
@endsection
