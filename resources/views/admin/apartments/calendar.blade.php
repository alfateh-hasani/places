@extends(backpack_view('layouts.top_left'))

@section('after_styles')
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        /* ✅ تحسين شكل المؤشر عند التمرير على الحجز */
        .fc-event {
            cursor: pointer;
        }
        /* ✅ إضافة ألوان دليل الحالات */
        .legend-box {
            width: 20px;
            height: 20px;
            display: inline-block;
            margin-right: 5px;
            border-radius: 3px;
        }
        button.btn-close {
            margin-left: 0 !important;
        }
    </style>
@endsection

@section('content')
    <div class="container">
        <div class="card">
            <div class="card-header bg-primary text-white">
                @php
                    $apartment = \App\Models\Apartment::find($apartmentId);
                    $apartmentName = $apartment ? ($apartment->name_ar ?? $apartment->name_en) : 'غير معروف';
                    $lang = app()->getLocale(); // Get the current language
                    $apartmentName = $lang === 'ar' ? $apartment->name_ar : $apartment->name_en;
                @endphp
                <h3 class="mb-0 text-white"><i class="la la-calendar-check"></i> تقويم الحجوزات - شقة {{ $apartmentName }}</h3>
            </div>
            <div class="card-body">
                <!-- ✅ دليل الألوان -->
                <div class="mb-3">
                    <strong>دليل الألوان:</strong>
                    <div>
                        <span class="legend-box" style="background-color: #FF5733;"></span> حجوزات Airbnb
                        <span class="legend-box" style="background-color: #2ECC71;"></span> حجوزات الموقع
                        <span class="legend-box" style="background-color: #FFC107;"></span> معلق
                        <span class="legend-box" style="background-color: #28A745;"></span> مقبول
                        <span class="legend-box" style="background-color: #DC3545;"></span> مرفوض
                        <span class="legend-box" style="background-color: #007BFF;"></span> مؤكد
                    </div>
                </div>

                <div id="calendar"></div>
            </div>
        </div>
    </div>

    <!-- ✅ نافذة تفاصيل الحجز (Modal) -->
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
            let calendarEl = document.getElementById('calendar');
            let apartmentId = {{ $apartmentId }};
            let calendar;

            function loadCalendar() {
                fetch(`/admin/apartment/${apartmentId}/bookings`)
                    .then(response => response.json())
                    .then(events => {
                        calendar = new FullCalendar.Calendar(calendarEl, {
                            initialView: 'dayGridMonth',
                            locale: 'ar',
                            direction: 'rtl',
                            events: events,
                            editable: false,
                            
                            // النقر على حجز لعرض تفاصيله
                            eventClick: function(info) {
                                let event = info.event.extendedProps;

                                document.getElementById("bookingNumber").textContent = info.event.title;
                                document.getElementById("customerName").textContent = event.customer_name;
                                document.getElementById("customerEmail").textContent = event.customer_email;
                                document.getElementById("bookingSource").textContent = event.source;
                                document.getElementById("bookingStatus").textContent = event.status;
                                document.getElementById("totalPrice").textContent = event.total_price;

                                var myModal = new bootstrap.Modal(document.getElementById('bookingModal'));
                                myModal.show();
                            }
                        });

                        calendar.render();
                    })
                    .catch(error => console.error('Error fetching booking data:', error));
            }

            loadCalendar();
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