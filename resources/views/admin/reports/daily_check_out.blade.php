@extends(backpack_view('layouts.top_left'))

@section('content')
<div class="container">
    <h2>📋 تقرير الخروج بعد 12 منتصف الليل</h2>

    @if($reports->isEmpty())
        <div class="alert alert-warning text-center">لا يوجد بيانات متاحة</div>
    @else
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>رقم الحجز</th>
                    <th>اسم العميل</th>
                    <th>الشقة</th>
                    <th>المبنى</th>
                    <th>تاريخ الخروج</th>
                    <th>وقت الخروج</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reports as $report)
                    <tr>
                        <td>{{ $report->number_of_booking }}</td>
                        <td>{{ $report->customer_full_name }}</td>
                        <td>{{ $report->apartment?->name_ar ?? 'غير محدد' }}</td>
                        <td>{{ $report->apartment?->building?->name_ar ?? 'غير محدد' }}</td>
                        <td>{{ $report->check_out->format('Y-m-d') }}</td>
                        <td>{{ $report->check_out_time ?? 'غير محدد' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

@endsection
