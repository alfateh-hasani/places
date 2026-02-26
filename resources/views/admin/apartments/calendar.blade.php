@extends(backpack_view('layouts.top_left'))

@section('after_styles')
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .fc-event { cursor: pointer; }
        .legend-box {
            width: 20px; height: 20px;
            display: inline-block; margin-right: 5px; border-radius: 3px;
        }
        button.btn-close { margin-left: 0 !important; }
    </style>
@endsection

@section('content')
    <div class="container">
        <div class="card">
            <div class="card-header bg-primary text-white">
                @php
                    $apartment = \App\Models\Apartment::find($apartmentId);
                    $lang = app()->getLocale();
                    $apartmentName = $apartment ? ($lang === 'ar' ? $apartment->name_ar : $apartment->name_en) : 'غير معروف';
                @endphp
                <h3 class="mb-0 text-white"><i class="la la-calendar-check"></i> تقويم الحجوزات - شقة {{ $apartmentName }}</h3>
            </div>

            <!-- التابات -->
            <div class="card-header border-bottom-0 pb-0 pt-3 d-flex justify-content-start">
                <ul class="nav nav-tabs" id="calendarTabs">
                    <li class="nav-item">
                        <button class="nav-link active" id="tab-calendar" data-target="pane-calendar">
                            <i class="la la-calendar"></i> التقويم
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="tab-list" data-target="pane-list">
                            <i class="la la-list"></i> القائمة
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body">
                <!-- دليل الألوان (مشترك) -->
                <div class="mb-3">
                    <strong>دليل الألوان:</strong>
                    <div>
                        <span class="legend-box" style="background-color: #FF5733;"></span> حجوزات Airbnb
                        <span class="legend-box" style="background-color: #FF8C00;"></span> Airbnb غير مزامن
                        <span class="legend-box" style="background-color: #8B0000;"></span> ⚠ تعارض Airbnb
                        <span class="legend-box" style="background-color: #2ECC71;"></span> حجوزات الموقع
                        <span class="legend-box" style="background-color: #FFC107;"></span> معلق
                        <span class="legend-box" style="background-color: #28A745;"></span> مقبول
                        <span class="legend-box" style="background-color: #DC3545;"></span> مرفوض
                        <span class="legend-box" style="background-color: #007BFF;"></span> مؤكد
                    </div>
                </div>

                <!-- تاب التقويم -->
                <div id="pane-calendar">
                    <div id="calendar"></div>
                </div>

                <!-- تاب القائمة -->
                <div id="pane-list" style="display:none;">
                    <div class="d-flex gap-2 mb-3">
                        <input type="text" id="bookingSearch" class="form-control form-control-sm" placeholder="بحث..." style="width:220px;">
                        <select id="bookingFilter" class="form-select form-select-sm" style="width:200px;">
                            <option value="">الكل</option>
                            <option value="booking">حجوزات الموقع / Airbnb</option>
                            <option value="ownerrez_unsynced">Airbnb غير مزامن</option>
                            <option value="ownerrez_conflict">⚠ تعارض Airbnb</option>
                        </select>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0 text-center">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>رقم الحجز</th>
                                    <th>تاريخ الوصول</th>
                                    <th>تاريخ المغادرة</th>
                                    <th>المصدر</th>
                                    <th>الحالة</th>
                                    <th>العميل</th>
                                    <th>الإجمالي</th>
                                </tr>
                            </thead>
                            <tbody id="bookingsTableBody">
                                <tr><td colspan="8" class="text-center py-3">جاري التحميل...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-muted small mt-2" id="bookingsCount"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- نافذة تفاصيل الحجز -->
    <div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bookingModalLabel">تفاصيل الحجز</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>رقم الحجز:</strong> <span id="bookingNumber"></span></p>
                    <p><strong>العميل:</strong> <span id="customerName"></span></p>
                    <p><strong>البريد الإلكتروني:</strong> <span id="customerEmail"></span></p>
                    <p><strong>المصدر:</strong> <span id="bookingSource"></span></p>
                    <p><strong>الحالة:</strong> <span id="bookingStatus"></span></p>
                    <p><strong>الإجمالي:</strong> <span id="totalPrice"></span> ريال</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('after_scripts')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const apartmentId = {{ $apartmentId }};
            let calendar;
            let allEvents = [];
            let calendarLoaded = false;

            // ===== تبديل التابات =====
            document.querySelectorAll('#calendarTabs .nav-link').forEach(btn => {
                btn.addEventListener('click', function () {
                    document.querySelectorAll('#calendarTabs .nav-link').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');

                    const target = this.dataset.target;
                    document.getElementById('pane-calendar').style.display = target === 'pane-calendar' ? '' : 'none';
                    document.getElementById('pane-list').style.display      = target === 'pane-list'     ? '' : 'none';

                    // render التقويم عند العودة إليه (يحل مشكلة الحجم)
                    if (target === 'pane-calendar' && calendar) {
                        calendar.updateSize();
                    }
                });
            });

            // ===== جلب البيانات (مرة واحدة) =====
            fetch(`/admin/apartment/${apartmentId}/bookings`)
                .then(r => r.json())
                .then(events => {
                    allEvents = events.sort((a, b) => (a.start ?? '').localeCompare(b.start ?? ''));

                    // بناء التقويم
                    const calendarEl = document.getElementById('calendar');
                    calendar = new FullCalendar.Calendar(calendarEl, {
                        initialView: 'dayGridMonth',
                        locale: 'ar',
                        direction: 'rtl',
                        events: allEvents,
                        editable: false,
                        eventClick: function (info) {
                            const ev = info.event.extendedProps;
                            document.getElementById('bookingNumber').textContent  = info.event.title;
                            document.getElementById('customerName').textContent   = ev.customer_name ?? '-';
                            document.getElementById('customerEmail').textContent  = ev.customer_email ?? '-';
                            document.getElementById('bookingSource').textContent  = ev.source ?? '-';
                            document.getElementById('bookingStatus').textContent  = ev.status ?? '-';
                            document.getElementById('totalPrice').textContent     = ev.total_price ?? '-';
                            new bootstrap.Modal(document.getElementById('bookingModal')).show();
                        }
                    });
                    calendar.render();

                    // بناء الجدول
                    renderTable(allEvents);
                })
                .catch(err => console.error('Error fetching bookings:', err));

            // ===== منطق الجدول =====
            const typeLabels = {
                booking:           { label: 'حجز',              badge: 'secondary' },
                ownerrez_unsynced: { label: 'Airbnb غير مزامن', badge: 'warning'   },
                ownerrez_conflict: { label: '⚠ تعارض Airbnb',   badge: 'danger'    },
            };

            const statusLabels = {
                pending:  { label: 'معلق',   badge: 'warning'   },
                approved: { label: 'مقبول',  badge: 'success'   },
                rejected: { label: 'مرفوض', badge: 'danger'    },
                booked:   { label: 'مؤكد',   badge: 'primary'   },
                canceled: { label: 'ملغي',   badge: 'secondary' },
            };

            const rowColors = {
                ownerrez_unsynced: '#fff3e0',
                ownerrez_conflict: '#fde8e8',
            };

            function formatDate(val) {
                if (!val) { return '-'; }
                return val.includes('T') ? val.split('T')[0] : val;
            }

            function renderTable(events) {
                const tbody = document.getElementById('bookingsTableBody');
                const count = document.getElementById('bookingsCount');

                if (!events.length) {
                    tbody.innerHTML = '<tr><td colspan="8" class="text-center py-3 text-muted">لا توجد نتائج</td></tr>';
                    count.textContent = '';
                    return;
                }

                tbody.innerHTML = events.map((e, i) => {
                    const type       = e.extendedProps?.type ?? 'booking';
                    const typeInfo   = typeLabels[type] ?? { label: type, badge: 'secondary' };
                    const statusKey  = (e.extendedProps?.status ?? '').toLowerCase();
                    const statusInfo = statusLabels[statusKey] ?? { label: e.extendedProps?.status ?? '-', badge: 'secondary' };
                    const bg         = rowColors[type] ?? '';
                    const source     = e.extendedProps?.source ?? '-';

                    return `<tr style="background:${bg}">
                        <td>${i + 1}</td>
                        <td><strong>${e.title}</strong></td>
                        <td>${formatDate(e.start)}</td>
                        <td>${formatDate(e.end)}</td>
                        <td><span class="badge bg-${typeInfo.badge}">${typeInfo.label}</span><br><small class="text-muted">${source}</small></td>
                        <td>${type === 'booking' ? `<span class="badge bg-${statusInfo.badge}">${statusInfo.label}</span>` : '-'}</td>
                        <td>${e.extendedProps?.customer_name ?? '-'}</td>
                        <td>${e.extendedProps?.total_price ? e.extendedProps.total_price + ' ر.س' : '-'}</td>
                    </tr>`;
                }).join('');

                count.textContent = `إجمالي: ${events.length} حجز`;
            }

            function filterEvents() {
                const search     = document.getElementById('bookingSearch').value.toLowerCase();
                const filterType = document.getElementById('bookingFilter').value;

                renderTable(allEvents.filter(e => {
                    const type      = e.extendedProps?.type ?? 'booking';
                    const matchType = !filterType || type === filterType;
                    const matchText = !search
                        || e.title.toLowerCase().includes(search)
                        || (e.extendedProps?.customer_name ?? '').toLowerCase().includes(search)
                        || formatDate(e.start).includes(search);
                    return matchType && matchText;
                }));
            }

            document.getElementById('bookingSearch').addEventListener('input', filterEvents);
            document.getElementById('bookingFilter').addEventListener('change', filterEvents);
        });

        $(document).ready(function () {
            $(document).on('shown.bs.modal', '.modal', function () {
                $('.modal-backdrop').before($(this));
            });
        });
    </script>
@endsection
