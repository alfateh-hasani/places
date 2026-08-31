@extends(backpack_view('layouts.top_left'))

@section('after_styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@17.0.19/build/css/intlTelInput.min.css">
    <style>
        .dbk-hidden { display: none !important; }
        .iti { width: 100%; }
        .flatpickr-calendar.inline { box-shadow: none; margin: 0 auto; }
        .flatpickr-day.flatpickr-disabled { text-decoration: line-through; background: #f8d7da33; }
        .dbk-legend span { display: inline-block; width: 14px; height: 14px; border-radius: 3px; vertical-align: middle; margin-inline-end: 4px; }
        .select2-container { width: 100% !important; }
        .select2-container--default .select2-selection--single { height: 38px; padding: 4px; border-color: #ced4da; }
    </style>
@endsection

@section('content')
    <div class="container-fluid">
        <h2 class="mb-3"><i class="la la-plus-circle"></i> حجز مباشر (تحويل بنكي)</h2>

        <div id="dbkAlert" class="alert dbk-hidden" role="alert"></div>

        <form id="directBookingForm" enctype="multipart/form-data">
            <div class="row">
                {{-- Availability calendars — order-last so they sit on the LEFT in this RTL layout --}}
                <div class="col-lg-7 order-lg-last">
                    <div class="card">
                        <div class="card-header"><i class="la la-calendar"></i> اختيار التواريخ (التوفّر)</div>
                        <div class="card-body">
                            <div id="calHint" class="text-muted text-center py-4">اختر الشقة أولاً لعرض التوفّر.</div>

                            <div id="calWrap" class="dbk-hidden">
                                <div class="dbk-legend mb-2 small text-muted">
                                    <span style="background:#28a745;"></span> متاح
                                    <span style="background:#f8d7da; border:1px solid #dc3545;"></span> غير متاح
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">الوصول <span class="text-danger">*</span></label>
                                        <input type="text" name="check_in" id="check_in" class="form-control mb-1" placeholder="اختر تاريخ الوصول" readonly required>
                                        <div id="checkinCal"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">المغادرة <span class="text-danger">*</span></label>
                                        <input type="text" name="check_out" id="check_out" class="form-control mb-1" placeholder="اختر تاريخ المغادرة" readonly required>
                                        <div id="checkoutCal"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right: booking details --}}
                <div class="col-lg-5">
                    <div class="card">
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">المبنى</label>
                                <select id="building_id" class="form-select">
                                    <option value="">— كل المباني —</option>
                                    @foreach ($buildings as $building)
                                        <option value="{{ $building->id }}">
                                            {{ app()->getLocale() === 'ar' ? $building->name_ar : $building->name_en }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">الوحدة (الشقة) <span class="text-danger">*</span></label>
                                <select name="apartment_id" id="apartment_id" class="form-select" required>
                                    <option value="">— اختر الوحدة —</option>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label">عدد البالغين <span class="text-danger">*</span></label>
                                    <input type="number" name="number_of_adults" id="number_of_adults" class="form-control" min="1" value="1" required>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">عدد الأطفال</label>
                                    <input type="number" name="number_of_children" id="number_of_children" class="form-control" min="0" value="0" required>
                                </div>
                            </div>

                            <hr>

                            {{-- Customer --}}
                            <label class="form-label d-block">العميل <span class="text-danger">*</span></label>
                            <div class="btn-group mb-2" role="group">
                                <input type="radio" class="btn-check" name="customer_mode" id="mode_existing" value="existing" checked>
                                <label class="btn btn-outline-primary btn-sm" for="mode_existing">عميل موجود</label>
                                <input type="radio" class="btn-check" name="customer_mode" id="mode_new" value="new">
                                <label class="btn btn-outline-primary btn-sm" for="mode_new">عميل جديد</label>
                            </div>

                            <div id="existingCustomerBox" class="mb-3">
                                <select name="customer_id" id="customer_id" class="form-select"></select>
                            </div>

                            <div id="newCustomerBox" class="mb-3 dbk-hidden">
                                <div class="row">
                                    <div class="col-6 mb-2"><input type="text" name="new_customer[first_name]" class="form-control" placeholder="الاسم الأول"></div>
                                    <div class="col-6 mb-2"><input type="text" name="new_customer[last_name]" class="form-control" placeholder="الاسم الأخير"></div>
                                    <div class="col-6 mb-2"><input type="tel" id="new_customer_phone" name="new_customer[phone]" class="form-control" placeholder="رقم الجوال"></div>
                                    <div class="col-6 mb-2"><input type="email" name="new_customer[email]" class="form-control" placeholder="البريد (اختياري)"></div>
                                </div>
                            </div>

                            <hr>

                            <div id="priceInfo" class="alert alert-info dbk-hidden py-2">
                                <div>عدد الليالي: <strong id="pi_nights">-</strong></div>
                                <div>السعر المقترح: <strong id="pi_total">-</strong> ر.س (ضريبة تقديرية: <strong id="pi_vat">-</strong>)</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">السعر النهائي (قابل للتعديل)</label>
                                <input type="number" step="0.01" min="0" name="final_price" id="final_price" class="form-control" placeholder="سيُحسب تلقائياً — يمكنك تعديله">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">رقم التحويل (اختياري)</label>
                                <input type="text" name="transfer_number" class="form-control" placeholder="مرجع التحويل البنكي">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">صورة إيصال التحويل (اختياري)</label>
                                <input type="file" name="receipt" class="form-control" accept="image/*">
                            </div>

                            <button type="submit" id="submitBtn" class="btn btn-success w-100">
                                <i class="la la-check"></i> إنشاء الحجز
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('after_scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/ar.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@17.0.19/build/js/intlTelInput.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const CSRF = '{{ csrf_token() }}';
            const LOCALE = '{{ app()->getLocale() }}';
            const APARTMENTS = @json($apartments);
            const urls = {
                customers: '{{ route('admin.direct-booking.customers') }}',
                pricePreview: '{{ route('admin.direct-booking.price-preview') }}',
                store: '{{ route('admin.direct-booking.store') }}',
                blockedTemplate: '{{ route('apartments.blocked-dates', ['id' => 'APT_ID']) }}',
            };
            const $id = (x) => document.getElementById(x);
            const aptName = (a) => (LOCALE === 'ar' ? a.name_ar : a.name_en) || a.name_ar || a.name_en;

            const alertBox = $id('dbkAlert');
            function showAlert(type, msg) { alertBox.className = 'alert alert-' + type; alertBox.textContent = msg; alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); }
            function clearAlert() { alertBox.className = 'alert dbk-hidden'; alertBox.textContent = ''; }

            // ---------- Searchable selects ----------
            // minimumResultsForSearch:0 keeps the search box visible even with few options.
            $('#building_id').select2({ placeholder: 'كل المباني', allowClear: true, dir: 'rtl', minimumResultsForSearch: 0 });
            $('#apartment_id').select2({ placeholder: 'اختر الوحدة', dir: 'rtl', minimumResultsForSearch: 0 });
            $('#customer_id').select2({
                placeholder: 'ابحث بالاسم أو الجوال أو البريد...',
                dir: 'rtl',
                minimumInputLength: 0,
                ajax: {
                    url: urls.customers,
                    dataType: 'json',
                    delay: 250,
                    data: (params) => ({ q: params.term || '' }),
                    processResults: (data) => ({ results: data }),
                },
            });

            // Focus the search field as soon as any select2 opens, so you can type immediately
            // without clicking into the search box first.
            $(document).on('select2:open', function () {
                setTimeout(function () {
                    const search = document.querySelector('.select2-container--open .select2-search__field');
                    if (search) { search.focus(); }
                }, 0);
            });

            function renderApartments() {
                const buildingId = $('#building_id').val();
                const list = APARTMENTS.filter(a => !buildingId || String(a.building_id) === String(buildingId));
                const sel = $id('apartment_id');
                sel.innerHTML = '<option value="">— اختر الوحدة —</option>';
                list.forEach(a => {
                    const o = document.createElement('option');
                    o.value = a.id;
                    o.textContent = aptName(a);
                    o.dataset.adults = a.adults_count;
                    o.dataset.children = a.children_count;
                    sel.appendChild(o);
                });
                $('#apartment_id').val('').trigger('change.select2');
                onApartmentChange();
            }
            $('#building_id').on('change', renderApartments);
            renderApartments();

            // ---------- Customer mode toggle ----------
            document.querySelectorAll('input[name="customer_mode"]').forEach(r => {
                r.addEventListener('change', function () {
                    const isNew = this.value === 'new';
                    $id('newCustomerBox').classList.toggle('dbk-hidden', !isNew);
                    $id('existingCustomerBox').classList.toggle('dbk-hidden', isNew);
                    if (isNew) { $('#customer_id').val(null).trigger('change'); }
                });
            });

            // ---------- New-customer phone: country code (default Saudi +966) ----------
            const phoneEl = $id('new_customer_phone');
            const phoneIti = window.intlTelInput(phoneEl, {
                initialCountry: 'sa',
                separateDialCode: true,
                preferredCountries: ['sa', 'ye', 'ae', 'eg'],
                utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@17.0.19/build/js/utils.js',
            });

            // ---------- Flatpickr availability calendars ----------
            let disabledDates = [];
            let checkoutDisabled = [];

            function buildDisabledDates(bookings) {
                const out = [];
                (bookings || []).forEach(b => {
                    const ci = new Date(b.check_in), co = new Date(b.check_out);
                    const cur = new Date(ci);
                    while (cur < co) { out.push(cur.toISOString().split('T')[0]); cur.setDate(cur.getDate() + 1); }
                });
                return out;
            }
            // A previous guest's check-in day is usable as the next guest's checkout day.
            function buildCheckoutDisabled(bookings) {
                const checkInDays = new Set((bookings || []).map(b => new Date(b.check_in).toISOString().split('T')[0]));
                return buildDisabledDates(bookings).filter(d => !checkInDays.has(d));
            }
            function firstOccupiedNightAfter(checkinDate) {
                const t = checkinDate.getTime();
                let res = null;
                disabledDates.forEach(s => { const d = new Date(s + 'T00:00:00'); if (d.getTime() > t && (res === null || d < res)) { res = d; } });
                return res;
            }

            const baseOpts = { dateFormat: 'Y-m-d', minDate: 'today', locale: (flatpickr.l10ns && flatpickr.l10ns.ar) ? 'ar' : 'default', inline: true };

            const checkinPicker = flatpickr('#check_in', {
                ...baseOpts,
                appendTo: $id('checkinCal'),
                onChange: function (dates) {
                    if (!dates.length) { return; }
                    const ci = dates[0];
                    const minCo = new Date(ci); minCo.setDate(minCo.getDate() + 1);
                    checkoutPicker.set('minDate', minCo);
                    checkoutPicker.set('maxDate', firstOccupiedNightAfter(ci) || null);
                    const cur = checkoutPicker.selectedDates[0];
                    const max = firstOccupiedNightAfter(ci);
                    if (!cur || cur <= ci || (max && cur > max)) { checkoutPicker.setDate(minCo, true); }
                    maybePreviewPrice();
                },
            });
            const checkoutPicker = flatpickr('#check_out', {
                ...baseOpts,
                appendTo: $id('checkoutCal'),
                onChange: () => maybePreviewPrice(),
            });

            function resetCalendars() {
                checkinPicker.clear(); checkoutPicker.clear();
                checkinPicker.set('disable', disabledDates);
                checkoutPicker.set('disable', checkoutDisabled);
                checkoutPicker.set('minDate', 'today');
                checkoutPicker.set('maxDate', null);
            }

            function loadAvailability(apartmentId) {
                fetch(urls.blockedTemplate.replace('APT_ID', apartmentId), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.json())
                    .then(res => {
                        const days = res.booked_days || [];
                        disabledDates = buildDisabledDates(days);
                        checkoutDisabled = buildCheckoutDisabled(days);
                        resetCalendars();
                    })
                    .catch(() => { disabledDates = []; checkoutDisabled = []; resetCalendars(); });
            }

            // ---------- Apartment change ----------
            function onApartmentChange() {
                const sel = $id('apartment_id');
                const opt = sel.options[sel.selectedIndex];
                const id = sel.value;
                if (opt && opt.dataset.adults) { $id('number_of_adults').max = opt.dataset.adults; }
                if (opt && opt.dataset.children) { $id('number_of_children').max = opt.dataset.children; }
                if (!id) { $id('calHint').classList.remove('dbk-hidden'); $id('calWrap').classList.add('dbk-hidden'); return; }
                $id('calHint').classList.add('dbk-hidden');
                $id('calWrap').classList.remove('dbk-hidden');
                loadAvailability(id);
            }
            $('#apartment_id').on('change', onApartmentChange);

            // ---------- Price preview ----------
            let userEditedPrice = false;
            $id('final_price').addEventListener('input', () => { userEditedPrice = true; });
            function maybePreviewPrice() {
                const apartment_id = $id('apartment_id').value;
                const check_in = $id('check_in').value;
                const check_out = $id('check_out').value;
                if (!apartment_id || !check_in || !check_out) { return; }
                fetch(urls.pricePreview, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify({ apartment_id, check_in, check_out }),
                })
                    .then(r => r.ok ? r.json() : Promise.reject(r))
                    .then(res => {
                        if (!res.ok) { return; }
                        $id('priceInfo').classList.remove('dbk-hidden');
                        $id('pi_nights').textContent = res.nights;
                        $id('pi_total').textContent = res.total_price;
                        $id('pi_vat').textContent = res.vat;
                        if (!userEditedPrice) { $id('final_price').value = res.suggested_final_price; }
                    })
                    .catch(() => {});
            }

            // ---------- Submit ----------
            $id('directBookingForm').addEventListener('submit', function (e) {
                e.preventDefault();
                clearAlert();
                const btn = $id('submitBtn');
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> جاري الإنشاء...';

                const formData = new FormData(this);
                if (document.querySelector('input[name="customer_mode"]:checked').value === 'existing') {
                    ['new_customer[first_name]', 'new_customer[last_name]', 'new_customer[phone]', 'new_customer[email]'].forEach(n => formData.delete(n));
                } else {
                    formData.delete('customer_id');
                    // Send the full international number (e.g. +9665…) instead of the national part.
                    const fullPhone = phoneIti.getNumber();
                    if (fullPhone) { formData.set('new_customer[phone]', fullPhone); }
                }
                formData.delete('customer_mode');

                fetch(urls.store, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData,
                })
                    .then(async r => ({ status: r.status, body: await r.json() }))
                    .then(({ status, body }) => {
                        if (status >= 200 && status < 300 && body.ok) {
                            showAlert('success', body.message || 'تم إنشاء الحجز.');
                            window.location.href = body.redirect;
                            return;
                        }
                        let msg = body.message || 'تعذّر إنشاء الحجز.';
                        if (body.errors) { msg = Object.values(body.errors).flat().join(' — '); }
                        showAlert('danger', msg);
                        btn.disabled = false;
                        btn.innerHTML = '<i class="la la-check"></i> إنشاء الحجز';
                    })
                    .catch(() => {
                        showAlert('danger', 'حدث خطأ غير متوقع. حاول مرة أخرى.');
                        btn.disabled = false;
                        btn.innerHTML = '<i class="la la-check"></i> إنشاء الحجز';
                    });
            });
        });
    </script>
@endsection
