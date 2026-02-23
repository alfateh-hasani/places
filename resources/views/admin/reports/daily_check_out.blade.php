@extends(backpack_view('layouts.top_left'))

@section('content')
<div class="container">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>📋 تقرير الخروج بعد 12 منتصف الليل</h2>
        </div>
        <div class="col-md-6 text-right" id="local-filters">
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

    {{-- التابات --}}
    <ul class="nav nav-tabs mb-3" id="checkoutTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="local-tab" data-toggle="tab" href="#local" role="tab">
                حجوزات النظام
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="ownerrez-tab" data-toggle="tab" href="#ownerrez" role="tab">
                حجوزات OwnerRez
            </a>
        </li>
    </ul>

    <div class="tab-content" id="checkoutTabsContent">

        {{-- تاب النظام --}}
        <div class="tab-pane fade show active" id="local" role="tabpanel">
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
                                <th>تاريخ الدخول</th>
                                <th>تاريخ الخروج</th>
                                <th>وقت الخروج</th>
                                <th>المصدر</th>
                                <th>القناة</th>
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
                                    <td>{{ $report->check_in?->format('Y-m-d') ?? 'غير محدد' }}</td>
                                    <td>{{ $report->check_out?->format('Y-m-d') ?? 'غير محدد' }}</td>
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
                                        @if($report->channel_name)
                                            <span class="badge badge-warning">{{ $report->channel_name }}</span>
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

        {{-- تاب OwnerRez --}}
        <div class="tab-pane fade" id="ownerrez" role="tabpanel">
            <div id="ownerrez-loading" class="text-center py-4" style="display:none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">جارٍ التحميل...</span>
                </div>
                <p class="mt-2">جارٍ جلب البيانات من OwnerRez...</p>
            </div>
            <div id="ownerrez-error" class="alert alert-danger" style="display:none;"></div>
            <div id="ownerrez-content" style="display:none;">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>رقم الحجز</th>
                                <th>اسم الضيف</th>
                                <th>العقار</th>
                                <th>تاريخ الدخول</th>
                                <th>تاريخ الخروج</th>
                                <th>القناة</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody id="ownerrez-tbody"></tbody>
                    </table>
                </div>
            </div>
            <div id="ownerrez-empty" class="alert alert-warning text-center" style="display:none;">
                لا توجد حجوزات خروج اليوم في OwnerRez
            </div>
        </div>

    </div>
</div>

@push('after_scripts')
<script>
(function () {
    var loaded = false;

    $('#ownerrez-tab').on('shown.bs.tab', function () {
        // إخفاء فلاتر النظام عند التبديل لتاب OwnerRez
        $('#local-filters').hide();

        if (loaded) return;
        loaded = true;

        $('#ownerrez-loading').show();
        $('#ownerrez-error').hide();
        $('#ownerrez-content').hide();
        $('#ownerrez-empty').hide();

        $.ajax({
            url: '{{ route('admin.reports.daily-checkout.ownerrez') }}',
            method: 'GET',
            success: function (data) {
                $('#ownerrez-loading').hide();

                if (!data.success) {
                    $('#ownerrez-error').text(data.message || 'حدث خطأ غير متوقع').show();
                    return;
                }

                if (data.warning) {
                    $('#ownerrez-error').removeClass('alert-danger').addClass('alert-warning')
                        .text(data.warning).show();
                    return;
                }

                if (!data.bookings || data.bookings.length === 0) {
                    $('#ownerrez-empty').show();
                    return;
                }

                var rows = '';
                $.each(data.bookings, function (i, b) {
                    var guestName = (b.guest && (b.guest.first_name || b.guest.last_name))
                        ? ((b.guest.first_name || '') + ' ' + (b.guest.last_name || '')).trim()
                        : (b.guest_name || '—');

                    var channel = b.channel_name || b.source || '—';
                    var status  = b.status || '—';
                    var propId  = b.property_id || b.property || '—';

                    rows += '<tr>'
                        + '<td>' + (b.id || '—') + '</td>'
                        + '<td>' + guestName + '</td>'
                        + '<td>' + propId + '</td>'
                        + '<td>' + (b.arrival || '—') + '</td>'
                        + '<td>' + (b.departure || '—') + '</td>'
                        + '<td><span class="badge badge-warning">' + channel + '</span></td>'
                        + '<td><span class="badge badge-info">' + status + '</span></td>'
                        + '</tr>';
                });

                $('#ownerrez-tbody').html(rows);
                $('#ownerrez-content').show();
            },
            error: function (xhr) {
                $('#ownerrez-loading').hide();
                var msg = 'فشل الاتصال بـ OwnerRez';
                try {
                    var resp = JSON.parse(xhr.responseText);
                    if (resp.message) msg = resp.message;
                } catch (e) {}
                $('#ownerrez-error').text(msg).show();
            }
        });
    });

    // إظهار الفلاتر عند العودة لتاب النظام
    $('#local-tab').on('shown.bs.tab', function () {
        $('#local-filters').show();
    });
})();
</script>
@endpush

@endsection
