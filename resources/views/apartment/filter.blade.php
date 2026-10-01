@once
@push('css')
<style>
    /* Slider follows the page direction: LTR → min on the left, RTL (Arabic) → min on the right. */
    .dual-range { position: relative; height: 28px; }
    .dual-range__track {
        position: absolute; top: 50%; inset-inline: 0; height: 6px;
        transform: translateY(-50%); background: #ececec; border-radius: 999px;
    }
    .dual-range__fill { position: absolute; top: 0; height: 100%; background: #f7bb8e; border-radius: 999px; }
    .dual-range input[type="range"] {
        position: absolute; top: 0; left: 0; width: 100%; height: 28px; margin: 0;
        background: transparent; -webkit-appearance: none; appearance: none; pointer-events: none;
    }
    .dual-range input[type="range"]::-webkit-slider-thumb {
        -webkit-appearance: none; appearance: none; pointer-events: auto; width: 16px; height: 16px;
        border-radius: 50%; background: #f7bb8e; border: 2px solid #fff;
        box-shadow: 0 1px 3px rgba(0,0,0,.35); cursor: pointer; margin-top: 0;
    }
    .dual-range input[type="range"]::-moz-range-thumb {
        pointer-events: auto; width: 16px; height: 16px; border: 2px solid #fff;
        border-radius: 50%; background: #f7bb8e; cursor: pointer;
    }
    .dual-range input[type="range"]::-webkit-slider-runnable-track { background: transparent; height: 28px; }
    .dual-range input[type="range"]::-moz-range-track { background: transparent; }
    /* Filter chips sit on a light bg (grey default, peach when hovered/open), so the
       label must always be dark. Force it in every state so the text never vanishes. */
    .filter [data-dropdown-toggle],
    .filter [data-dropdown-toggle] span {
        color: #1f2937 !important;
    }
    /* Active/open chip: solid brand bg (text stays dark via the rule above). */
    .filter [data-dropdown-toggle][aria-expanded="true"] {
        background-color: #f7bb8e;
    }
</style>
@endpush
@endonce
<section class="filter">
    <form id="apartment-filter-form" action="{{ route('apartments.search') }}" method="GET">
        <input type="hidden" name="check_in" value="{{ request('check_in') }}">
        <input type="hidden" name="check_out" value="{{ request('check_out') }}">
        <input type="hidden" name="city_id" value="{{ request('city_id') }}">
        <input type="hidden" name="adults" value="{{ request('adults') }}">
        <input type="hidden" name="children" value="{{ request('children') }}">
    
        <div class="container">
            <div class="rtl:float-right float-left rounded-xl bg-filterbackground border border-filterborder px-5 py-3 xl:py-1.5 buttons mb-2 xl:mb-0 grid grid-cols-2 items-center gap-2 xl:flex xl:flex-wrap">
                @php
                    $activeFilterCount = collect([
                        request()->anyFilled(['price_min', 'price_max']),
                        request()->anyFilled(['area_min', 'area_max']),
                        request()->filled('rate'),
                        request()->filled('rooms'),
                        request()->filled('beds'),
                        request()->filled('building_id'),
                    ])->filter()->count();
                @endphp
                <p class="font-semibold text-base text-black col-span-2 xl:col-auto">
                    {{ __('site.filters') }}
                    @if ($activeFilterCount)
                        <span class="inline-block bg-price text-white text-xs rounded-full px-2 py-0.5 align-middle">{{ $activeFilterCount }}</span>
                    @endif
                </p>
                
                <!-- Price Range Filter -->
                <div class="relative">
                    <button id="dropdownPriceButton" data-dropdown-toggle="dropdownPrice" class="flex w-full items-center justify-between px-4 py-2 text-sm font-medium text-black bg-filteritem rounded-lg hover:bg-filterhover" type="button">
                        <span class="rtl:mr-2 ml-2">{{ __('filters.price') }}</span>
                        <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4" />
                        </svg>
                    </button>
                    
                    <div id="dropdownPrice" class="z-50 hidden bg-white rounded-lg shadow w-60 max-w-[calc(100vw-2rem)] px-4 py-4">
                        <label class="block text-sm font-medium text-gray-700 mb-3">{{ __('filters.price_range') }}</label>
                        <div class="dual-range" data-dual-range data-min-label="#minPriceValue" data-max-label="#maxPriceValue">
                            <div class="dual-range__track"><div class="dual-range__fill"></div></div>
                            <input type="range" class="dual-range__min" name="price_min"
                                   min="{{ $filter_keys['min_price'] }}" max="{{ $filter_keys['max_price'] }}"
                                   value="{{ request('price_min', $filter_keys['min_price']) }}">
                            <input type="range" class="dual-range__max" name="price_max"
                                   min="{{ $filter_keys['min_price'] }}" max="{{ $filter_keys['max_price'] }}"
                                   value="{{ request('price_max', $filter_keys['max_price']) }}">
                        </div>
                        <div class="flex justify-between text-sm text-gray-700 mt-3 dual-range-labels">
                            <span><span id="minPriceValue">{{ request('price_min', $filter_keys['min_price']) }}</span> <x-riyal /></span>
                            <span><span id="maxPriceValue">{{ request('price_max', $filter_keys['max_price']) }}</span> <x-riyal /></span>
                        </div>
                    </div>
                </div>
    
                <!-- Rate Filter -->
                <div class="relative">
                    <button id="dropdownRateButton" data-dropdown-toggle="dropdownRate" class="flex w-full items-center justify-between px-4 py-2 text-sm font-medium text-black bg-filteritem rounded-lg hover:bg-filterhover" type="button">
                        <span class="rtl:mr-2 ml-2">{{ __('filters.rate') }}</span>
                        <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4" />
                        </svg>
                    </button>
                    
                    <div id="dropdownRate" class="z-50 hidden bg-white rounded-lg shadow w-60 max-w-[calc(100vw-2rem)]">
                        <ul class="h-48 px-3 py-3 overflow-y-auto text-sm text-gray-700" aria-labelledby="dropdownRateButton">
                            @for ($i = 5; $i >= 1; $i--)
                                <li>
                                    <div class="flex items-center p-2 rounded hover:bg-gray-100">
                                        <input id="rate-{{ $i }}" type="radio" value="{{ $i }}"
                                        name="rate" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300"
                                        {{ (string) request('rate') === (string) $i ? 'checked' : '' }}>
                                        <label for="rate-{{ $i }}" class="w-full ms-2 text-sm font-medium text-gray-900 rounded flex items-center">
                                            @for ($j = 1; $j <= $i; $j++)
                                                <img class="inline-block -translate-y-0.5" src="{{ asset('assets/img/star.svg') }}" alt="{{ __('filters.star') }}">
                                            @endfor
                                            @if ($i < 5)
                                                <span class="ms-1.5 text-gray-500">{{ __('filters.rate_and_up') }}</span>
                                            @endif
                                        </label>
                                    </div>
                                </li>
                            @endfor

                        </ul>
                    </div>
                </div>
    
                <!-- Area Range Filter -->
                <div class="relative">
                    <button id="dropdownAreaButton" data-dropdown-toggle="dropdownArea" class="flex w-full items-center justify-between px-4 py-2 text-sm font-medium text-black bg-filteritem rounded-lg hover:bg-filterhover" type="button">
                        <span class="rtl:mr-2 ml-2">{{ __('filters.area') }}</span>
                        <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4" />
                        </svg>
                    </button>
    
                    <div id="dropdownArea" class="z-50 hidden bg-white rounded-lg shadow w-60 max-w-[calc(100vw-2rem)] px-4 py-4">
                        <label class="block text-sm font-medium text-gray-700 mb-3">{{ __('filters.area_range') }}</label>
                        <div class="dual-range" data-dual-range data-min-label="#minAreaValue" data-max-label="#maxAreaValue">
                            <div class="dual-range__track"><div class="dual-range__fill"></div></div>
                            <input type="range" class="dual-range__min" name="area_min"
                                   min="{{ $filter_keys['min_area'] }}" max="{{ $filter_keys['max_area'] }}"
                                   value="{{ request('area_min', $filter_keys['min_area']) }}">
                            <input type="range" class="dual-range__max" name="area_max"
                                   min="{{ $filter_keys['min_area'] }}" max="{{ $filter_keys['max_area'] }}"
                                   value="{{ request('area_max', $filter_keys['max_area']) }}">
                        </div>
                        <div class="flex justify-between text-sm text-gray-700 mt-3 dual-range-labels">
                            <span><span id="minAreaValue">{{ request('area_min', $filter_keys['min_area']) }}</span> m²</span>
                            <span><span id="maxAreaValue">{{ request('area_max', $filter_keys['max_area']) }}</span> m²</span>
                        </div>
                    </div>
                </div>
    
                <!-- Rooms Filter -->
                <div class="relative">
                    <button id="dropdownRoomsButton" data-dropdown-toggle="dropdownSearch3" class="flex w-full items-center justify-between px-4 py-2 text-sm font-medium text-black bg-filteritem rounded-lg hover:bg-filterhover" type="button">
                        <span class="rtl:mr-2 ml-2">{{ __('filters.rooms') }}</span>
                        <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4" />
                        </svg>
                    </button>
                    
                    <div id="dropdownSearch3" class="z-50 hidden bg-white rounded-lg shadow w-60 max-w-[calc(100vw-2rem)]">
                        <ul class="h-48 px-3 py-3 overflow-y-auto text-sm text-gray-700" aria-labelledby="dropdownSearchButton">
                            @foreach($filter_keys['rooms_options'] as $roomLabel)
                                <li>
                                    <div class="flex items-center p-2 rounded hover:bg-gray-100">
                                        <input name="rooms[]" id="checkbox-room-{{ $roomLabel }}" type="checkbox" value="{{ $roomLabel }}" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded" {{ in_array((string) $roomLabel, (array) request('rooms', [])) ? 'checked' : '' }}>
                                        <label for="checkbox-room-{{ $roomLabel }}" class="w-full ms-2 text-sm font-medium text-gray-900 rounded">
                                            {{ $roomLabel }} {{ __('filters.room_unit') }}
                                        </label>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
    
                <!-- Beds Filter -->
                <div class="relative">
                    <button id="dropdownBedsButton" data-dropdown-toggle="dropdownSearch4" class="flex w-full items-center justify-between px-4 py-2 text-sm font-medium text-black bg-filteritem rounded-lg hover:bg-filterhover" type="button">
                        <span class="rtl:mr-2 ml-2">{{ __('filters.beds') }}</span>
                        <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4" />
                        </svg>
                    </button>
                    
                    <div id="dropdownSearch4" class="z-50 hidden bg-white rounded-lg shadow w-60 max-w-[calc(100vw-2rem)]">
                        <ul class="h-48 px-3 py-3 overflow-y-auto text-sm text-gray-700" aria-labelledby="dropdownSearchButton">
                            @foreach($filter_keys['beds_options'] as $bedsCount)
                                <li>
                                    <div class="flex items-center p-2 rounded hover:bg-gray-100">
                                        <input id="checkbox-bed-{{ $bedsCount }}" type="checkbox" name="beds[]" value="{{ $bedsCount }}" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded" {{ in_array((string) $bedsCount, (array) request('beds', [])) ? 'checked' : '' }}>
                                        <label for="checkbox-bed-{{ $bedsCount }}" class="w-full ms-2 text-sm font-medium text-gray-900 rounded">
                                            {{ $bedsCount }} {{ __('filters.bed_unit') }}
                                        </label>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <!-- Building Filter -->
                <div class="relative">
                    <button id="dropdownBuildingButton" data-dropdown-toggle="dropdownBuilding" class="flex w-full items-center justify-between px-4 py-2 text-sm font-medium text-black bg-filteritem rounded-lg hover:bg-filterhover" type="button">
                        <span class="rtl:mr-2 ml-2">{{ __('filters.building') }}</span>
                        <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4" />
                        </svg>
                    </button>

                    <div id="dropdownBuilding" class="z-50 hidden bg-white rounded-lg shadow w-60 max-w-[calc(100vw-2rem)]">
                        <ul class="h-48 px-3 py-3 overflow-y-auto text-sm text-gray-700" aria-labelledby="dropdownBuildingButton">
                            @foreach($filter_keys['buildings_options'] as $id => $name)
                                <li>
                                    <div class="flex items-center p-2 rounded hover:bg-gray-100">
                                        <input id="checkbox-building-{{ $id }}" type="checkbox" name="building_id[]" value="{{ $id }}" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded" {{ in_array($id, (array) request('building_id', [])) ? 'checked' : '' }}>
                                        <label for="checkbox-building-{{ $id }}" class="w-full ms-2 text-sm font-medium text-gray-900 rounded">
                                            {{ $name }}
                                        </label>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                {{-- Clear-filters (X) at the opposite end of the bar; keeps the search. --}}
                @if (request()->anyFilled(['price_min', 'price_max', 'area_min', 'area_max', 'rate', 'rooms', 'beds', 'building_id']))
                    <a href="{{ route('apartments.search', collect(request()->only(['city_id', 'check_in', 'check_out']))->filter(fn ($v) => $v !== null && $v !== '')->all()) }}"
                       title="{{ __('filters.clear_filters') }}"
                       class="col-span-2 justify-center xl:col-auto xl:ms-auto inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-price border border-price rounded-lg hover:bg-price hover:text-white ease-in-out duration-300">
                        <svg class="w-3 h-3" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M1 1l12 12M13 1 1 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        </svg>
                        {{ __('filters.clear_filters') }}
                    </a>
                @endif
            </div>

            <button class="float-right rtl:float-left rounded-xl h-12 bg-price font-semibold text-base text-white w-full xl:w-48 flex items-center justify-center gap-2">
                <img class="inline-block" src="{{asset('assets/img/filter-icon.svg')}}" />
                {{ __('filters.apply_filters') }}
            </button>

            <div class="clear-both"></div>
        </div>
    </form>
    
    
</section>
 
@push('js')
    <script>
        // Dual-handle range sliders (price, area): two thumbs = min..max.
        document.querySelectorAll('[data-dual-range]').forEach(function (wrap) {
            var minInput = wrap.querySelector('.dual-range__min');
            var maxInput = wrap.querySelector('.dual-range__max');
            var fill = wrap.querySelector('.dual-range__fill');
            var minLabel = wrap.dataset.minLabel ? document.querySelector(wrap.dataset.minLabel) : null;
            var maxLabel = wrap.dataset.maxLabel ? document.querySelector(wrap.dataset.maxLabel) : null;
            var floor = parseFloat(minInput.min);
            var ceil = parseFloat(minInput.max);
            var span = (ceil - floor) || 1;

            function render() {
                var lo = parseFloat(minInput.value);
                var hi = parseFloat(maxInput.value);
                var loPct = (lo - floor) / span * 100;
                var hiPct = (hi - floor) / span * 100;
                // Logical offset: measured from the left in LTR, from the right in RTL —
                // so the fill sits between the handles in both directions.
                fill.style.insetInlineStart = loPct + '%';
                fill.style.width = Math.max(0, hiPct - loPct) + '%';
                if (minLabel) { minLabel.textContent = Math.round(lo); }
                if (maxLabel) { maxLabel.textContent = Math.round(hi); }
            }

            function markTouched() {
                // Either handle moving makes the whole min..max range an active filter.
                minInput.dataset.touched = '1';
                maxInput.dataset.touched = '1';
            }

            minInput.addEventListener('input', function () {
                if (parseFloat(minInput.value) > parseFloat(maxInput.value)) {
                    minInput.value = maxInput.value;
                }
                markTouched();
                render();
            });
            maxInput.addEventListener('input', function () {
                if (parseFloat(maxInput.value) < parseFloat(minInput.value)) {
                    maxInput.value = minInput.value;
                }
                markTouched();
                render();
            });

            // Re-applied filter from the URL (values narrower than the full range) stays active.
            if (parseFloat(minInput.value) !== floor || parseFloat(maxInput.value) !== ceil) {
                markTouched();
            }
            render();
        });
    </script>
   
    <script>
        // Scope the "drop unused fields on submit" cleanup to THIS filter form only.
        // (Previously used document.querySelector('form'), which grabbed the first
        // form on the page — the search bar — not the filter form.)
        const apartmentFilterForm = document.getElementById('apartment-filter-form');
        if (apartmentFilterForm) {
            // Range sliders always carry a value, so submit them only if the user
            // actually moved them (marked "touched"); otherwise their default value
            // would pollute the URL even when the user didn't filter by price/area.
            apartmentFilterForm.querySelectorAll('input[type="range"]').forEach(function (range) {
                range.addEventListener('input', function () { range.dataset.touched = '1'; });
            });

            apartmentFilterForm.addEventListener('submit', function () {
                // Carry the dates currently chosen in the search bar into this filter
                // form, so applying filters keeps the dates even when the search itself
                // wasn't submitted first (the two live in separate forms). City is left
                // to the URL so a default city selection doesn't over-constrain filters.
                [
                    ['#datepicker-range-start', 'check_in'],
                    ['#datepicker-range-end', 'check_out'],
                    ['#counter-input', 'adults'],
                    ['#counter-input1', 'children'],
                ].forEach(function (pair) {
                    var src = document.querySelector(pair[0]);
                    var dst = apartmentFilterForm.querySelector('[name="' + pair[1] + '"]');
                    if (src && dst && src.value) { dst.value = src.value; }
                });

                // Drop unchecked checkboxes
                this.querySelectorAll('input[type="checkbox"]:not(:checked)').forEach(function (checkbox) {
                    checkbox.disabled = true;
                });
                // Drop empty text inputs and untouched range sliders
                this.querySelectorAll('input').forEach(function (input) {
                    if (input.type === 'range') {
                        if (!input.dataset.touched) {
                            input.disabled = true;
                        }
                    } else if (!input.value.trim()) {
                        input.disabled = true;
                    }
                });
            });
        }
    </script>

@endpush