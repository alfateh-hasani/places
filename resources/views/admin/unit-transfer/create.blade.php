@extends(backpack_view('layouts.top_left'))

@php
    $appLocale = app()->getLocale();
    $nameField = $appLocale === 'ar' ? 'name_ar' : 'name_en';
@endphp

@section('after_styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <style>
        .ut-hidden { display: none !important; }
        .ut-note { border-radius: .5rem; }
        .select2-container { width: 100% !important; }
        .select2-container--default .select2-selection--single { height: 38px; padding: 4px; border-color: #ced4da; }
    </style>
@endsection

@section('content')
    <div class="container-fluid">
        <h2 class="mb-3"><i class="la la-exchange-alt"></i> {{ __('cms.transfer_unit') }}</h2>

        <div class="row">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header"><i class="la la-info-circle"></i> {{ __('cms.booking_management') }}</div>
                    <div class="card-body">
                        <table class="table table-sm table-bordered mb-0">
                            <tr><th>{{ __('cms.booking_number') }}</th><td><bdo dir="ltr">{{ $booking->number_of_booking }}</bdo></td></tr>
                            <tr><th>{{ __('cms.customer') }}</th><td>{{ $booking->customer_full_name }}</td></tr>
                            <tr><th>{{ __('cms.current_unit') }}</th><td>{{ optional($booking->apartment)->{$nameField} ?? optional($booking->apartment)->name_ar }}</td></tr>
                            <tr><th>{{ __('cms.dates') }}</th><td><bdo dir="ltr">{{ $booking->check_in?->format('Y-m-d') }} → {{ $booking->check_out?->format('Y-m-d') }}</bdo></td></tr>
                            <tr><th>{{ __('cms.transfer_original_price') }}</th><td><bdo dir="ltr">{{ number_format((float) $booking->final_price, 2) }}</bdo></td></tr>
                            <tr><th>{{ __('cms.guests') }}</th><td>{{ (int) $booking->adults_count }} + {{ (int) $booking->children_count }}</td></tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header"><i class="la la-building"></i> {{ __('cms.transfer_choose_destination') }}</div>
                    <div class="card-body">
                        <div id="utAlert" class="alert ut-hidden" role="alert"></div>

                        <form id="utForm" method="POST" action="{{ route('admin.booking.transfer-unit.store', $booking->id) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-bold">{{ __('cms.building') }}</label>
                                <select id="utBuilding" class="form-control">
                                    <option value="">— {{ __('cms.select') }} —</option>
                                    @foreach($buildings as $b)
                                        <option value="{{ $b->id }}" @selected((int) $b->id === (int) optional($booking->apartment)->building_id)>{{ $b->{$nameField} ?? $b->name_ar }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">{{ __('cms.apartment') }} <span class="text-danger">*</span></label>
                                <select id="utApartment" name="apartment_id" class="form-control" required disabled>
                                    <option value="">— {{ __('cms.select') }} —</option>
                                </select>
                            </div>

                            <div id="utPreview" class="alert alert-secondary ut-note ut-hidden"></div>

                            <button type="submit" id="utSubmit" class="btn btn-primary" disabled>
                                <i class="la la-paper-plane"></i> {{ __('cms.transfer_initiate') }}
                            </button>
                            <a href="{{ backpack_url('booking/'.$booking->id.'/show') }}" class="btn btn-link">{{ __('cms.cancel') }}</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('after_scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        (function () {
            const apartments = @json($apartments);
            const nameField = @json($nameField);
            const selectLabel = @json(__('cms.select'));
            const previewUrl = @json(route('admin.booking.transfer-unit.price-preview', $booking->id));
            const csrf = @json(csrf_token());
            const i18n = {
                even: @json(__('cms.transfer_note_even')),
                absorb: @json(__('cms.transfer_note_absorb')),
                refund: @json(__('cms.transfer_note_refund')),
                unavailable: @json(__('cms.transfer_note_unavailable')),
                checking: @json(__('cms.transfer_checking')),
            };

            const buildingSel = document.getElementById('utBuilding');
            const aptSel = document.getElementById('utApartment');
            const preview = document.getElementById('utPreview');
            const submit = document.getElementById('utSubmit');
            const $ = window.jQuery;

            function resetPreview() {
                preview.classList.add('ut-hidden');
                submit.disabled = true;
            }

            function refreshSelect2(el) {
                if ($) { $(el).trigger('change.select2'); }
            }

            function populateApartments(bid) {
                aptSel.innerHTML = '<option value="">— ' + selectLabel + ' —</option>';
                resetPreview();
                const list = apartments.filter(a => String(a.building_id) === String(bid));
                list.forEach(a => {
                    const opt = document.createElement('option');
                    opt.value = a.id;
                    opt.textContent = a[nameField] || a.name_ar;
                    aptSel.appendChild(opt);
                });
                aptSel.disabled = list.length === 0;
                refreshSelect2(aptSel);
            }

            function runPreview(id) {
                resetPreview();
                if (!id) { return; }

                preview.className = 'alert alert-secondary ut-note';
                preview.textContent = i18n.checking;

                fetch(previewUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    body: JSON.stringify({ apartment_id: id }),
                }).then(r => r.json().then(j => ({ ok: r.ok, j })))
                  .then(({ ok, j }) => {
                      if (!ok || !j.ok) {
                          preview.className = 'alert alert-danger ut-note';
                          preview.textContent = (j && j.message) ? j.message : i18n.unavailable;
                          submit.disabled = true;
                          return;
                      }
                      var price = Number(j.new_price).toFixed(2);
                      let msg = '';
                      if (j.direction === 'even') { msg = i18n.even.replace(':price', price); preview.className = 'alert alert-success ut-note'; }
                      else if (j.direction === 'surcharge') { msg = i18n.absorb.replace(':price', price).replace(':amount', Math.abs(j.price_delta).toFixed(2)); preview.className = 'alert alert-info ut-note'; }
                      else { msg = i18n.refund.replace(':price', price).replace(':amount', Number(j.refund_amount).toFixed(2)); preview.className = 'alert alert-warning ut-note'; }
                      preview.textContent = msg;
                      submit.disabled = false;
                  })
                  .catch(() => {
                      preview.className = 'alert alert-danger ut-note';
                      preview.textContent = i18n.unavailable;
                      submit.disabled = true;
                  });
            }

            // Bind via jQuery when present: select2 dispatches its change through jQuery, which a
            // native addEventListener('change') would NOT catch — so the preview never ran.
            function onChange(el, handler) {
                if ($) { $(el).on('change', handler); } else { el.addEventListener('change', handler); }
            }
            onChange(buildingSel, function () { populateApartments(buildingSel.value); });
            onChange(aptSel, function () { runPreview(aptSel.value); });

            // Make both selects searchable (select2), then honour the default building
            // (the booking's current building) by pre-populating its apartments.
            function init() {
                if ($ && $.fn.select2) {
                    $(buildingSel).select2({ width: '100%' });
                    $(aptSel).select2({ width: '100%' });
                    // Focus the search box the moment the dropdown opens, so staff can type
                    // immediately without clicking into the search field first.
                    $(document).on('select2:open', function () {
                        const search = document.querySelector('.select2-container--open .select2-search__field');
                        if (search) { search.focus(); }
                    });
                }
                if (buildingSel.value) {
                    populateApartments(buildingSel.value);
                    refreshSelect2(buildingSel);
                }
            }

            if ($) { $(init); } else { document.addEventListener('DOMContentLoaded', init); }
        })();
    </script>
@endsection
