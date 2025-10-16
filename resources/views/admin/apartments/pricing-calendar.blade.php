@extends(backpack_view('layouts.top_left'))

@section('after_styles')
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .fc-event { cursor: pointer; }
        .legend-box {
            width: 20px; height: 20px;
            display: inline-block;
            margin-right: 5px;
            border-radius: 3px;
        }
        button.btn-close { margin-left: 0 !important; }
        .pricing-info-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .pricing-info-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        .pricing-info-item:last-child { border-bottom: none; }
    </style>
@endsection

@section('content')
    <div class="container">
        <div class="pricing-info-card">
            <h4><i class="la la-info-circle"></i> إعدادات التسعير الأساسية</h4>
            <div class="pricing-info-item">
                <span><strong>السعر الأساسي:</strong></span>
                <span>{{ optional($apartment->pricing)->base_price ?? $apartment->price }} ر.س / ليلة</span>
            </div>
            <div class="pricing-info-item">
                <span><strong>سعر نهاية الأسبوع:</strong></span>
                <span>{{ optional($apartment->pricing)->weekend_price ?? 'غير محدد' }} {{ optional($apartment->pricing)->weekend_price ? 'ر.س / ليلة' : '' }}</span>
            </div>
            <div class="pricing-info-item">
                <span><strong>خصم الإقامة الطويلة:</strong></span>
                <span>{{ optional($apartment->pricing)->long_stay_discount ?? '0' }}%</span>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-success text-white">
                <h3 class="mb-0"><i class="la la-dollar"></i> تقويم الأسعار المخصصة - {{ $apartment->name_ar }}</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong><i class="la la-lightbulb"></i> تعليمات الاستخدام:</strong>
                    <ul class="mb-0 mt-2">
                        <li>انقر على أي يوم لإضافة سعر مخصص له</li>
                        <li>انقر على السعر الموجود لتعديله أو حذفه</li>
                        <li>الأسعار المخصصة تظهر باللون الأزرق <span class="legend-box" style="background-color: #17A2B8;"></span></li>
                        <li>الأيام بدون أسعار مخصصة ستستخدم السعر الأساسي أو سعر نهاية الأسبوع</li>
                    </ul>
                </div>
                <div id="calendar"></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="customPriceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="customPriceModalLabel">
                        <i class="la la-dollar"></i> تحديد سعر مخصص
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="customPriceForm">
                        <input type="hidden" id="selectedDate">
                        <input type="hidden" id="priceId">
                        <div class="mb-3">
                            <label class="form-label"><strong><i class="la la-calendar"></i> التاريخ:</strong></label>
                            <input type="text" class="form-control" id="selectedDateDisplay" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><strong><i class="la la-money"></i> السعر المخصص (ر.س):</strong></label>
                            <input type="number" step="0.01" min="0" class="form-control form-control-lg" id="customPrice" required placeholder="أدخل السعر">
                            <small class="text-muted">هذا السعر سيُستخدم بدلاً من السعر الأساسي لهذا اليوم</small>
                        </div>
                        <div class="alert alert-warning" id="weekendNotice" style="display:none;">
                            <i class="la la-exclamation-triangle"></i> هذا اليوم نهاية أسبوع
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="deletePriceBtn" style="display:none;"><i class="la la-trash"></i> حذف</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="la la-times"></i> إلغاء</button>
                    <button type="button" class="btn btn-success" id="savePriceBtn"><i class="la la-save"></i> حفظ</button>
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
            let apartmentId = {{ $apartmentId }};
            let calendar;

            fetch(`/admin/apartment/${apartmentId}/custom-prices`)
                .then(response => response.json())
                .then(events => {
                    calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
                        initialView: 'dayGridMonth',
                        locale: 'ar',
                        direction: 'rtl',
                        events: events,
                        selectable: true,
                        height: 'auto',
                        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,dayGridWeek' },
                        dateClick: function(info) { openPriceModal(info.dateStr, null, null); },
                        eventClick: function(info) {
                            let event = info.event.extendedProps;
                            if (event.type === 'custom_price') {
                                openPriceModal(event.date, event.custom_price, event.price_id);
                            }
                        }
                    });
                    calendar.render();
                });

            function openPriceModal(date, price, priceId) {
                document.getElementById('selectedDate').value = date;
                document.getElementById('selectedDateDisplay').value = date;
                document.getElementById('customPrice').value = price || '';
                document.getElementById('priceId').value = priceId || '';
                
                let dayOfWeek = new Date(date).getDay();
                document.getElementById('weekendNotice').style.display = (dayOfWeek === 5 || dayOfWeek === 6) ? 'block' : 'none';
                document.getElementById('deletePriceBtn').style.display = priceId ? 'inline-block' : 'none';
                document.getElementById('customPriceModalLabel').innerHTML = priceId ? '<i class="la la-edit"></i> تعديل السعر' : '<i class="la la-plus"></i> إضافة سعر';
                
                new bootstrap.Modal(document.getElementById('customPriceModal')).show();
            }

            document.getElementById('savePriceBtn').addEventListener('click', function() {
                let price = document.getElementById('customPrice').value;
                if (!price || price <= 0) { alert('الرجاء إدخال سعر صحيح'); return; }
                
                fetch(`/admin/apartment/${apartmentId}/custom-price`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ date: document.getElementById('selectedDate').value, custom_price: price })
                })
                .then(response => response.json())
                .then(data => data.success ? location.reload() : alert('حدث خطأ'))
                .catch(() => alert('حدث خطأ'));
            });

            document.getElementById('deletePriceBtn').addEventListener('click', function() {
                if (confirm('هل أنت متأكد من الحذف؟')) {
                    fetch(`/admin/apartment/${apartmentId}/custom-price/${document.getElementById('priceId').value}`, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                    })
                    .then(response => response.json())
                    .then(data => data.success ? location.reload() : alert('حدث خطأ'))
                    .catch(() => alert('حدث خطأ'));
                }
            });
        });
    </script>


    <script>

    $(document).ready(function () {
        $(document).on('shown.bs.modal', '.modal', function () {
            $('.modal-backdrop').before($(this));
        });
    });

</script>
@endsection
