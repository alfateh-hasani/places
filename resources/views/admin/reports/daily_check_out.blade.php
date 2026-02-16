@extends(backpack_view('layouts.top_left'))

@section('content')
<div class="container">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>📋 تقرير الخروج بعد 12 منتصف الليل</h2>
        </div>
        <div class="col-md-6 text-right">
            <form method="GET" action="{{ route('admin.reports.daily-checkout') }}" class="form-inline justify-content-end">
                <div class="form-group mr-2">
                    <label for="source" class="mr-2">فلتر المصدر:</label>
                    <select name="source" id="source" class="form-control" onchange="this.form.submit()">
                        <option value="all" {{ $source === 'all' ? 'selected' : '' }}>جميع الحجوزات</option>
                        <option value="app" {{ $source === 'app' ? 'selected' : '' }}>من التطبيق</option>
                        <option value="airbnb" {{ $source === 'airbnb' ? 'selected' : '' }}>من Airbnb</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="site" class="mr-2">الموقع:</label>
                    <select name="site" id="site" class="form-control" onchange="this.form.submit()">
                        <option value="all" {{ $site === 'all' ? 'selected' : '' }}>الكل</option>
                        @foreach($availableSites as $siteOption)
                            <option value="{{ $siteOption }}" {{ $site === $siteOption ? 'selected' : '' }}>{{ $siteOption }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    @if($reports->isEmpty())
        <div class="alert alert-warning text-center">لا يوجد بيانات متاحة</div>
    @else
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>رقم الحجز</th>
                        <th>اسم العميل</th>
                        <th>الشقة</th>
                        <th>المبنى</th>
                        <th>تاريخ الخروج</th>
                        <th>وقت الخروج</th>
                        <th>المصدر</th>
                        <th>الموقع (Site)</th>
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
                            <td>
                                @if($report->check_out_time)
                                    {{ $report->check_out_time->format('H:i') }}
                                @else
                                    <span class="text-muted">غير محدد</span>
                                @endif
                            </td>
                            <td>
                                @if($report->booking_source)
                                    <span class="badge badge-success">{{ $report->booking_source }}</span>
                                @elseif($report->ownerrez_booking_id)
                                    <span class="badge badge-info">ownerrez</span>
                                @else
                                    <span class="text-muted">غير محدد</span>
                                @endif
                            </td>
                            <td>
                                @if($report->site)
                                    <span class="badge badge-info">{{ $report->site }}</span>
                                @else
                                    <span class="text-muted">غير محدد</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection
