<section class="container py-8 lg:hidden cursor-pointer search-button -translate-y-[50%]">
    <div class="flex items-center px-6 py-3 bg-white shadow-xl rounded-full border border-border">
        <img src="{{ asset('assets/img/search-black.svg') }}" class="w-4 me-5 py-2" alt="" width="16" height="16" />
        <div>
            <p class="font-semibold text-xs">
                {{ __('site.search_mobile') }}
            </p>
            <p class="text-sm">
                {{ __('site.search_mobile_desc') }}
            </p>
        </div>
    </div>
</section>

<section class="search lg:container z-40 xl:px-40 h-[100vh] lg:h-auto fixed lg:relative left-0 bottom-0 right-0 bg-blackopacity lg:bg-[transparent] lg:-mt-12">
    <form  action="{{ route('apartments.search') }}"
        method="GET"
        class="absolute lg:relative bottom-0 lg:bottom-auto left-0 margin-0 w-full lg:w-auto lg:grid grid-cols-2 lg:grid-cols-5 gap-1 max-w-full py-5 lg:pl-10 pl-5 pr-5 bg-white shadow-xl rounded-xl lg:rounded-full border border-border"
        id="date-range-picker">
        <div class="flex items-center justify-between mb-5 lg:hidden">
            <p class="font-semibold">
                {{ __('site.search_mobile') }}
            </p>
            <button type="button" class="close-button"><img src="{{ asset('assets/img/close.svg') }}" alt="{{ __('site.close') }}" /></button>
        </div>
        <div class="shadow-xl lg:shadow-none p-4 lg:p-0 rounded-lg mb-3 lg:mb-0 lg:rounded-none ">
            <label for="city_id" class="font-normal text-xs text-black">{{ __('site.filters_city_id') }}</label>
            <select name="city_id" id="city_id" aria-label="{{ __('site.filters_city_id') }}" class="select2 w-full border-0 font-semibold text-sm">
              @foreach ($cities as $item)
                <option value="{{ $item->id }}">{{ $item->ml('name') }}</option>
              @endforeach
            </select>
        </div>
        <div
            class="shadow-xl lg:shadow-none p-4 lg:p-0 rounded-lg mb-3 lg:mb-0 lg:rounded-none lg:px-4 lg:border-s border-blackopacity cursor-pointer ">
            <p class="font-normal text-xs text-black">{{ __('site.filters_check_in') }}</p>
            <input id="datepicker-range-start" name="check_in" type="text" readonly
                aria-label="{{ __('site.filters_check_in') }}"
                class="cursor-pointer p-0 pt-1 text-black font-semibold text-sm block w-full border-0"
                placeholder="{{now()->format('Y-m-d')}}" autocomplete="off"
                value="{{ request('check_in') }}" />
        </div>
        <div
            class="shadow-xl lg:shadow-none p-4 lg:p-0 rounded-lg mb-3 lg:mb-0 lg:rounded-none lg:px-4 lg:border-s border-blackopacity cursor-pointer ">
            <p class="font-normal text-xs text-black">{{ __('site.filters_check_out') }}</p>
            <input id="datepicker-range-end" name="check_out" type="text" readonly
                aria-label="{{ __('site.filters_check_out') }}"
                class="cursor-pointer p-0 pt-1 text-black font-semibold text-sm block w-full border-0"
                placeholder="{{ now()->addDay()->format('Y-m-d') }}"  autocomplete="off"
                value="{{ request('check_out') }}" />
        </div>
        <div
            class="shadow-xl lg:shadow-none p-4 lg:p-0 rounded-lg mb-3 lg:mb-0 lg:rounded-none lg:px-4 lg:border-s border-blackopacity cursor-pointer persons relative ">
            <p class="font-normal text-xs text-black">{{ __('site.filters_guests') }}</p>
            <p class="font-semibold text-sm text-black py-1 content">{{ __('site.add_guests') }}</p>
            <ul class="hidden lg:absolute w-72 bg-white p-4 border border-border rounded-lg">
                <li class="border-b border-blackopacity pb-4 mb-4">
                    <p class="inline-block w-36 text-lg">{{ __('site.filters_adults') }}<span class="block text-xs opacity-50">
                        {{ __('site.abrove_12') }}     
                    </span></p>
                    <div class="inline-block">
                        <div class="relative flex items-center">
                            <button type="button" id="decrement-button" data-input-counter-decrement="counter-input"
                                aria-label="{{ __('site.filters_adults') }} -"
                                class="flex-shrink-0 inline-flex items-center justify-center border border-gray-300 rounded-full h-8 w-8 hover:border-title">
                                <svg class="w-2.5 h-2.5 text-gray-900 dark:text-white" aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 2">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="M1 1h16" />
                                </svg>
                            </button>
                            <input type="text" id="counter-input" name="adults" data-input-counter
                                aria-label="{{ __('site.filters_adults') }}"
                                class="flex-shrink-0 text-black border-0 bg-transparent text-sm font-normal max-w-[2.5rem] text-center p-1"
                                placeholder="" value="{{ request('adults', 1) }}" required />
                            <button type="button" id="increment-button" data-input-counter-increment="counter-input"
                            aria-label="{{ __('site.filters_adults') }} +"
                            class="flex-shrink-0 inline-flex items-center justify-center border border-gray-300 rounded-full h-8 w-8 hover:border-title">
                                <svg class="w-2.5 h-2.5 text-gray-900 dark:text-white" aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 18">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="M9 1v16M1 9h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </li>
                <li>
                    <p class="inline-block w-36">{{ __('site.filters_children') }}
                        <span class="block text-xs opacity-50">
                        {{ __('site.below_12') }} </span>
                    </p>   
                    
                    <div class="inline-block">
                        <div class="relative flex items-center">
                            <button type="button" id="decrement-button1" data-input-counter-decrement="counter-input1"
                            aria-label="{{ __('site.filters_children') }} -"
                            class="flex-shrink-0 inline-flex items-center justify-center border border-gray-300 rounded-full h-8 w-8 hover:border-title">
                                <svg class="w-2.5 h-2.5 text-gray-900 dark:text-white" aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 2">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="M1 1h16" />
                                </svg>
                            </button>
                            <input type="text" id="counter-input1" name="children" data-input-counter
                                aria-label="{{ __('site.filters_children') }}"
                                class="flex-shrink-0 text-black border-0 bg-transparent text-sm font-normal max-w-[2.5rem] text-center p-1"
                                placeholder="" value="{{ request('children', 0) }}" required />
                            <button type="button" id="increment-button1" data-input-counter-increment="counter-input1"
                            aria-label="{{ __('site.filters_children') }} +"
                            class="flex-shrink-0 inline-flex items-center justify-center border border-gray-300 rounded-full h-8 w-8 hover:border-title">
                                <svg class="w-2.5 h-2.5 text-gray-900 dark:text-white" aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 18">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="M9 1v16M1 9h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
        <div class="lg:col-span-1 col-span-2">
            <button
                class="bg-price  w-full h-11 text-center rounded-lg lg:rounded-full hover:bg-black ease-in-out duration-200">
                <img class="inline-block -translate-y-0.5 me-2" src="{{ asset('assets/img/search2.svg') }}" alt="" />{{ __('site.search') }}
            </button>
        </div>
    </form>
</section>

@push('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

@push('js')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    (function () {
        // Home search uses flatpickr with Y-m-d — the SAME format the apartments
        // filter page expects — so the selected city/dates/guests carry over
        // cleanly. (Previously the Flowbite range picker emitted MM/DD/YYYY, which
        // the filter page rejected and cleared.)
        var ciEl = document.getElementById('datepicker-range-start');
        var coEl = document.getElementById('datepicker-range-end');

        // Drop any value that is not a clean Y-m-d before the picker initialises.
        [ciEl, coEl].forEach(function (el) {
            if (el && el.value && !/^20\d{2}-\d{2}-\d{2}$/.test(el.value.trim())) { el.value = ''; }
        });

        if (window.flatpickr && ciEl && coEl) {
            var addDay = function (ymd) {
                var d = new Date(ymd);
                d.setDate(d.getDate() + 1);
                return d;
            };
            var coMin = /^20\d{2}-\d{2}-\d{2}$/.test(ciEl.value) ? addDay(ciEl.value) : addDay(new Date());
            var checkoutFp = flatpickr(coEl, { dateFormat: 'Y-m-d', minDate: coMin, disableMobile: true });
            flatpickr(ciEl, {
                dateFormat: 'Y-m-d',
                minDate: 'today',
                disableMobile: true,
                onChange: function (sel) {
                    if (!sel[0]) { return; }
                    var next = new Date(sel[0]);
                    next.setDate(next.getDate() + 1);
                    checkoutFp.set('minDate', next);
                    if (! checkoutFp.selectedDates[0] || checkoutFp.selectedDates[0] <= sel[0]) {
                        checkoutFp.setDate(next);
                    }
                }
            });
        }

        // Only carry the guest counts when the user changed them from the defaults
        // (adults=1, children=0). A disabled field is not submitted, so an untouched
        // search keeps a clean URL — unlike dates, which are always sent.
        document.getElementById('date-range-picker')?.addEventListener('submit', function () {
            var adultsEl = document.getElementById('counter-input');
            var childrenEl = document.getElementById('counter-input1');
            if (adultsEl && adultsEl.value.trim() === '1') { adultsEl.disabled = true; }
            if (childrenEl && childrenEl.value.trim() === '0') { childrenEl.disabled = true; }
        });
    })();
</script>
@endpush