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
                <div class="mt-3">
                    {{ $reports->links() }}
                </div>
            @endif
        </div>

        {{-- تاب OwnerRez --}}
        <div class="tab-pane fade" id="ownerrez" role="tabpanel">
            <div class="text-left mb-2" id="ownerrez-refresh-bar" style="display:none;">
                <button id="ownerrez-refresh-btn" class="btn btn-sm btn-outline-secondary">
                    &#x21bb; تحديث
                </button>
            </div>
            <div id="ownerrez-loading" class="text-center py-4" style="display:none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">جارٍ التحميل...</span>
                </div>
                <p class="mt-2" id="ownerrez-loading-text">جارٍ جلب البيانات من OwnerRez...</p>
            </div>
            <div id="ownerrez-error" class="alert alert-danger" style="display:none;">
                <span id="ownerrez-error-msg"></span>
                <div class="mt-2">
                    <button id="ownerrez-retry-btn" class="btn btn-sm btn-danger">&#x21bb; إعادة المحاولة</button>
                </div>
            </div>
            <div id="ownerrez-content" style="display:none;">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>رقم الحجز</th>
                                <th>رقم الحجز الخارجي</th>
                                <th>اسم الضيف</th>
                                <th>اسم الشقة</th>
                                <th>معرف العقار</th>
                                <th>تاريخ الدخول</th>
                                <th>وقت الدخول</th>
                                <th>تاريخ الخروج</th>
                                <th>وقت الخروج</th>
                                <th>النزلاء</th>
                                <th>القناة</th>
                                <th>المبلغ</th>
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
    var loaded           = false;
    var activeXhr        = null;
    var failedOffset     = 0; // الـ offset الذي فشل، للرجوع إليه عند الضغط على إعادة المحاولة
    var propertyNameMap  = {}; // property_id → اسم الشقة
    var baseUrl          = '{{ route('admin.reports.daily-checkout.ownerrez') }}';

    function updateLoadingText(text) {
        $('#ownerrez-loading-text').text(text);
    }

    function showError(msg, offset) {
        failedOffset = offset;
        $('#ownerrez-loading').hide();
        $('#ownerrez-error-msg').text(msg);
        $('#ownerrez-error').removeClass('alert-warning').addClass('alert-danger').show();
        loaded = false;
    }

    function buildRows(bookings) {
        var rows = '';
        $.each(bookings, function (i, b) {
            function val(v) {
                if (v !== null && v !== undefined && v !== '') { return v; }
                return b.listing_site || '—';
            }

            var guestName = (b.guest && (b.guest.first_name || b.guest.last_name))
                ? ((b.guest.first_name || '') + ' ' + (b.guest.last_name || '')).trim()
                : '—';

            var sourceDomain = null;
            if (b.fields && b.fields.length) {
                var f = b.fields.find(function (x) { return x.code === 'BXSOURCEDOMAIN'; });
                if (f) { sourceDomain = f.value || null; }
            }
            var channel = sourceDomain || b.listing_site || '—';
            var status  = b.status || '—';
            var guests  = (b.adults || 0) + ' بالغ' + (b.children > 0 ? ' / ' + b.children + ' أطفال' : '');
            var amount  = b.total_amount ? b.total_amount + ' ' + (b.currency_code || '') : '—';
            var extRef  = val(b.platform_reservation_number);

            var channelBadge = channel !== '—'
                ? '<span class="badge badge-warning">' + channel + '</span>'
                : '<span class="text-muted">—</span>';
            var statusBadge = '<span class="badge badge-' + (status === 'active' ? 'success' : 'secondary') + '">' + status + '</span>';

            var aptName = (b.property_id && propertyNameMap[String(b.property_id)])
                ? propertyNameMap[String(b.property_id)]
                : '<span class="text-muted">—</span>';

            rows += '<tr>'
                + '<td>' + val(b.id) + '</td>'
                + '<td>' + extRef + '</td>'
                + '<td>' + guestName + '</td>'
                + '<td>' + aptName + '</td>'
                + '<td>' + val(b.property_id) + '</td>'
                + '<td>' + val(b.arrival) + '</td>'
                + '<td>' + val(b.check_in) + '</td>'
                + '<td>' + val(b.departure) + '</td>'
                + '<td>' + val(b.check_out) + '</td>'
                + '<td>' + guests + '</td>'
                + '<td>' + channelBadge + '</td>'
                + '<td>' + amount + '</td>'
                + '<td>' + statusBadge + '</td>'
                + '</tr>';
        });
        return rows;
    }

    // جلب صفحة واحدة بـ offset محدد، وعند الفشل تظهر رسالة + زر إعادة المحاولة
    function fetchPage(offset, bust) {
        var params = { offset: offset };
        if (bust && offset === 0) { params.bust = 1; }

        activeXhr = $.ajax({
            url: baseUrl,
            method: 'GET',
            data: params,
            timeout: 35000,
            success: function (data) {
                activeXhr = null;

                if (! data.success) {
                    showError(data.message || 'حدث خطأ غير متوقع', offset);
                    return;
                }

                // تخزين خريطة أسماء الشقق إن وُجدت
                if (data.property_name_map) {
                    $.extend(propertyNameMap, data.property_name_map);
                }

                if (data.warning) {
                    $('#ownerrez-loading').hide();
                    $('#ownerrez-error-msg').text(data.warning);
                    $('#ownerrez-error').removeClass('alert-danger').addClass('alert-warning').show();
                    $('#ownerrez-retry-btn').hide();
                    return;
                }

                if (data.bookings && data.bookings.length > 0) {
                    $('#ownerrez-tbody').append(buildRows(data.bookings));
                    $('#ownerrez-content').show();
                }

                if (data.has_more && data.next_offset !== null) {
                    // استمرار جلب الصفحة التالية تلقائياً
                    var count = $('#ownerrez-tbody tr').length;
                    updateLoadingText('جارٍ تحميل المزيد... (' + count + ' سجل حتى الآن)');
                    fetchPage(data.next_offset, false);
                } else {
                    // انتهى التحميل
                    $('#ownerrez-loading').hide();
                    if ($('#ownerrez-tbody tr').length === 0) {
                        $('#ownerrez-empty').show();
                    }
                }
            },
            error: function (xhr, status) {
                activeXhr = null;
                if (status === 'abort') { return; }

                var msg = status === 'timeout'
                    ? 'انتهت مهلة الاتصال بـ OwnerRez، حاول مرة أخرى'
                    : 'فشل الاتصال بـ OwnerRez';
                try {
                    var resp = JSON.parse(xhr.responseText);
                    if (resp && resp.message) { msg = resp.message; }
                } catch (e) {}
                showError(msg, offset);
            }
        });
    }

    function startFetch(bust) {
        if (activeXhr) {
            activeXhr.abort();
            activeXhr = null;
        }

        loaded = true;
        $('#ownerrez-loading').show();
        updateLoadingText('جارٍ جلب البيانات من OwnerRez...');
        $('#ownerrez-error').hide();
        $('#ownerrez-retry-btn').show();
        $('#ownerrez-content').hide();
        $('#ownerrez-empty').hide();
        $('#ownerrez-tbody').empty();
        $('#ownerrez-refresh-bar').show();

        fetchPage(0, bust || false);
    }

    $('#ownerrez-tab').on('shown.bs.tab', function () {
        $('#local-filters').hide();
        if (loaded) { return; }
        startFetch(false);
    });

    $('#ownerrez-refresh-btn').on('click', function () {
        startFetch(true);
    });

    // زر إعادة المحاولة يكمل من الـ offset الذي فشل
    $('#ownerrez-retry-btn').on('click', function () {
        $('#ownerrez-error').hide();
        $('#ownerrez-loading').show();
        updateLoadingText('جارٍ إعادة المحاولة...');
        loaded = true;
        fetchPage(failedOffset, false);
    });

    $('#local-tab').on('shown.bs.tab', function () {
        $('#local-filters').show();
    });
})();
</script>
@endpush

@endsection
