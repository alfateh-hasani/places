@php 
Widget::add([
    'type'       => 'chart',
    'controller' => \App\Http\Controllers\Admin\Charts\DashboardStatsChartController::class,

    // OPTIONALS

    // 'class'   => 'card mb-2',
    // 'wrapper' => ['class'=> 'col-md-6'] ,
    // 'content' => [
         // 'header' => 'New Users',
         // 'body'   => 'This chart should make it obvious how many new users have signed up in the past 7 days.<br><br>',
    // ],
]);
@endphp