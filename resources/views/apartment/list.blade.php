@extends('layouts.master')


@section('content')
 
<section class="container py-8 lg:hidden cursor-pointer search-button" data-aos="zoom-in">
    <div class="px-6 py-3 bg-white shadow-xl rounded-full border border-border">
        <img src="{{asset('assets/img/search-black.svg')}}" class="float-left rtl:float-right w-4 mr-5 py-2" />
        <div class="float-left rtl:float-right">
            <p class="font-semibold text-xs">
                {{ __('site.search') }}
            </p>
            <p class="text-sm">
                {{ __('site.search_mobile_desc') }}
            </p>
        </div>
        <div class="clear-both"></div>
    </div>
</section>


<section class="search lg:container z-40 xl:px-40 lg:py-16 h-[100vh] lg:h-auto fixed lg:relative rtl:right-0 left-0 bottom-0 right-0 bg-blackopacity lg:bg-[transparent]" data-aos="zoom-out">
    <form action="{{ route('apartments.search') }}" method="GET" class="absolute lg:relative bottom-0 lg:bottom-auto rtl:right-0 left-0 margin-0 w-full lg:w-auto lg:grid grid-cols-2 lg:grid-cols-5 gap-1 max-w-full py-5 lg:pl-10 pl-5 pr-5 bg-white shadow-xl rounded-xl lg:rounded-full border border-border" id="date-range-picker">
      {{-- Preserve the applied filters when re-searching with new dates/city/guests. --}}
      @foreach (['price_min', 'price_max', 'area_min', 'area_max', 'rate'] as $keepKey)
          @if (request()->filled($keepKey))
              <input type="hidden" name="{{ $keepKey }}" value="{{ request($keepKey) }}">
          @endif
      @endforeach
      @foreach (['rooms', 'beds', 'building_id'] as $keepKey)
          @foreach ((array) request($keepKey, []) as $keepVal)
              <input type="hidden" name="{{ $keepKey }}[]" value="{{ $keepVal }}">
          @endforeach
      @endforeach
      
      <!-- العنوان والإغلاق -->
      <div class="mb-5 lg:hidden">
        <p class="float-left rtl:float-right font-semibold">
            {{ __('site.search_mobile') }}
        </p>
        <button type="button" class="float-right rtl:float-left close-button"><img src="{{asset('assets/img/close.svg')}}" /></button>
        <div class="clear-both"></div>
      </div>
      
      <!-- حقل اختيار المدينة -->
      <div class="shadow-xl lg:shadow-none p-4 lg:p-0 rounded-lg mb-3 lg:mb-0 lg:rounded-none ">
        <p class="font-normal text-xs text-black">
            {{ __('site.filters_city_id') }}
        </p>
        <select name="city_id" class="select2 w-full border-0 font-semibold text-sm">
          @foreach ($cities as $item)
            <option value="{{ $item->id }}" {{ old('city_id', request('city_id')) == $item->id ? 'selected' : '' }}>
              {{ $item->ml('name') }}
            </option>   
          @endforeach
        </select>
      </div>
      
      @php
          // Only echo a requested date back into the field if it is valid and
          // reasonable — never re-display a mangled value like "10/09/0191".
          $validDate = function ($v) {
              if (empty($v)) { return ''; }
              try {
                  $d = \Carbon\Carbon::parse($v);
                  return ($d->year >= 2000 && $d->year <= 2100) ? $v : '';
              } catch (\Throwable $e) { return ''; }
          };
      @endphp
      <!-- حقل تسجيل الدخول (Check In) -->
      <div class="shadow-xl lg:shadow-none p-4 lg:p-0 rounded-lg mb-3 lg:mb-0 lg:rounded-none lg:px-4 lg:border-s border-blackopacity cursor-pointer ">
        <p class="font-normal text-xs text-black">  
            {{ __('site.filters_check_in') }}
        </p>
        <input
          id="datepicker-range-start"
          name="check_in"
          type="text"
          autocomplete="off"
          readonly
          class="cursor-pointer p-0 pt-1 text-black font-semibold text-sm block w-full border-0"
          placeholder="{{now()->format('Y-m-d')}}"
          value="{{ old('check_in', $validDate(request('check_in'))) }}"
        />
      </div>
      
      <!-- حقل تسجيل الخروج (Check Out) -->
      <div class="shadow-xl lg:shadow-none p-4 lg:p-0 rounded-lg mb-3 lg:mb-0 lg:rounded-none lg:px-4 lg:border-s border-blackopacity cursor-pointer ">
        <p class="font-normal text-xs text-black">  
            {{ __('site.filters_check_out') }}
        </p>
        <input
          id="datepicker-range-end"
          name="check_out"
          type="text"
          autocomplete="off"
          readonly
          class="cursor-pointer p-0 pt-1 text-black font-semibold text-sm block w-full border-0"
          placeholder="{{ now()->addDay()->format('Y-m-d') }}"
          value="{{ old('check_out', $validDate(request('check_out'))) }}"
        />
      </div>
      
      <!-- حقل إضافة الضيوف (Guests) -->
      <div class="shadow-xl lg:shadow-none p-4 lg:p-0 rounded-lg mb-3 lg:mb-0 lg:rounded-none lg:px-4 lg:border-s border-blackopacity cursor-pointer persons relative ">
        <p class="font-normal text-xs text-black">
            {{ __('site.filters_guests') }}
        </p>
        <p class="font-semibold text-sm text-black py-1 content">  
            {{ __('site.add_guests') }}
        </p>
        <ul class="hidden lg:absolute w-full bg-white p-3">
          
          <!-- عدد البالغين -->
          <li class="border-b border-blackopacity pb-3 mb-3">
            <p class="inline-block w-24">
                {{ __('site.filters_adults') }}
            </p>
            <div class="inline-block">
              <div class="relative flex items-center">
                <button type="button" id="decrement-button" data-input-counter-decrement="counter-input" class="flex-shrink-0 bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 dark:border-gray-600 hover:bg-gray-200 inline-flex items-center justify-center border border-gray-300 rounded-md h-5 w-5 focus:ring-gray-100 dark:focus:ring-gray-700 focus:ring-2 focus:outline-none">
                  <svg class="w-2.5 h-2.5 text-gray-900 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 2">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h16" />
                  </svg>
                </button>
                <input type="text" id="counter-input" name="adults" data-input-counter class="flex-shrink-0 text-black border-0 bg-transparent text-sm font-normal max-w-[2.5rem] text-center p-1" value="{{ old('adults', request('adults', 1)) }}" required />
                <button type="button" id="increment-button" data-input-counter-increment="counter-input" class="flex-shrink-0 bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 dark:border-gray-600 hover:bg-gray-200 inline-flex items-center justify-center border border-gray-300 rounded-md h-5 w-5 focus:ring-gray-100 dark:focus:ring-gray-700 focus:ring-2 focus:outline-none">
                  <svg class="w-2.5 h-2.5 text-gray-900 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 18">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 1v16M1 9h16" />
                  </svg>
                </button>
              </div>
            </div>
          </li>
          
          <!-- عدد الأطفال -->
          <li>
            <p class="inline-block w-24">
                {{ __('site.filters_children') }}
            </p>
            <div class="inline-block">
              <div class="relative flex items-center">
                <button type="button" id="decrement-button" data-input-counter-decrement="counter-input1" class="flex-shrink-0 bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 dark:border-gray-600 hover:bg-gray-200 inline-flex items-center justify-center border border-gray-300 rounded-md h-5 w-5 focus:ring-gray-100 dark:focus:ring-gray-700 focus:ring-2 focus:outline-none">
                  <svg class="w-2.5 h-2.5 text-gray-900 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 2">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h16" />
                  </svg>
                </button>
                <input type="text" id="counter-input1" name="children" data-input-counter class="flex-shrink-0 text-black border-0 bg-transparent text-sm font-normal max-w-[2.5rem] text-center p-1" value="{{ old('children', request('children', 0)) }}" required />
                <button type="button" id="increment-button" data-input-counter-increment="counter-input1" class="flex-shrink-0 bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 dark:border-gray-600 hover:bg-gray-200 inline-flex items-center justify-center border border-gray-300 rounded-md h-5 w-5 focus:ring-gray-100 dark:focus:ring-gray-700 focus:ring-2 focus:outline-none">
                  <svg class="w-2.5 h-2.5 text-gray-900 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 18">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 1v16M1 9h16" />
                  </svg>
                </button>
              </div>
            </div>
          </li>
        </ul>
      </div>
      
      <!-- زر البحث -->
      <div class="lg:col-span-1 col-span-2">
        <button type="submit" class="bg-price text-white w-full h-11 text-center rounded-lg lg:rounded-full hover:bg-black ease-in-out duration-200">
          <img class="inline-block -translate-y-0.5 mr-2" src="{{asset('assets/img/search.svg')}}" />
          {{ __('site.search') }}
        </button>
      </div>
    </form>
</section>


@include('apartment.filter')




  <section class="list pt-2 sm:pt-20 pb-2 sm:pb-20">
    <div class="container grid-container">
        @if ($apartments->isEmpty())
            <div class="flex flex-col items-center justify-center text-center py-16 sm:py-24">
                <svg class="w-14 h-14 mb-4 text-reviews" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8" />
                    <path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                </svg>
                <h3 class="font-semibold text-lg text-title mb-2">@lang('apartment.no_results')</h3>
                <p class="font-normal text-sm text-reviews max-w-md">@lang('apartment.no_results_hint')</p>
            </div>
        @else
            <div class="grid grid-items grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 max-w-full mx-0">
                @foreach($apartments as $apartment)
                    @include('apartment.card', ['apartment' => $apartment, 'showNightlyPrice' => true])
                @endforeach
            </div>
        @endif
    </div>

    <div id="list-links">
        {!! $apartments->links() !!}
        </div>
</section>

@endsection

@push('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    #list-links{
        display:none;
    }
</style>
@endpush
@push('js')

<script src="{{ asset('assets/js/infinite-scroll.pkgd.min.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>

// Initialize Infinite Scroll
$('.grid-container .grid-items').infiniteScroll({
        path: '#list-links a[aria-label="pagination.next"]',
        append: '.apartment-card',
        history: 'push',
        //prefill: true,
    }).on('append.infiniteScroll', function (event, response, path, items) {
        // Find the newly added items that contain sliders
        $(items).find('.slider').each(function () {
            // Destroy existing Slick instance if any
            if ($(this).hasClass('slick-initialized')) {
                $(this).slick('unslick');
            }
            // Reinitialize Slick on the new content
            $(this).slick({
                dots: true,
                @if(config('app.locale') == 'ar')
                rtl: true, 
                @endif
            });
        });
    });
    </script>
<script>
    function updateRange() {
        const minPrice = document.getElementById('min-price');
        const maxPrice = document.getElementById('max-price');
        const minPriceLabel = document.getElementById('min-price-label');
        const maxPriceLabel = document.getElementById('max-price-label');
        const rangeHighlight = document.getElementById('range-highlight');
        if (parseInt(minPrice.value) > parseInt(maxPrice.value)) {
            minPrice.value = maxPrice.value;
        }
        if (parseInt(maxPrice.value) < parseInt(minPrice.value)) {
            maxPrice.value = minPrice.value;
        }
        minPriceLabel.textContent = `${minPrice.value} SAR`;
        maxPriceLabel.textContent = `${maxPrice.value} SAR`;
        const minPos = (minPrice.value - minPrice.min) / (minPrice.max - minPrice.min) * 100;
        const maxPos = (maxPrice.value - maxPrice.min) / (maxPrice.max - maxPrice.min) * 100;
        rangeHighlight.style.left = `${minPos}%`;
        rangeHighlight.style.width = `${maxPos - minPos}%`;
    }
    updateRange();
</script>

<script>
    (function () {
        var ciEl = document.getElementById('datepicker-range-start');
        var coEl = document.getElementById('datepicker-range-end');

        // Clear any browser-restored/invalid value (e.g. a mangled "10/09/0191")
        // before flatpickr initialises, so only clean Y-m-d dates remain.
        [ciEl, coEl].forEach(function (el) {
            if (el && el.value && !/^20\d{2}-\d{2}-\d{2}$/.test(el.value.trim())) { el.value = ''; }
        });

        var addDay = function (ymd) {
            var d = new Date(ymd);
            d.setDate(d.getDate() + 1);
            return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        };

        // Normalize an out-of-order range (e.g. a hand-edited URL): check-out must be after check-in.
        if (ciEl && coEl && ciEl.value && coEl.value && coEl.value <= ciEl.value) {
            coEl.value = addDay(ciEl.value);
        }

        // Reliable date picker: flatpickr with Y-m-d (the format the backend
        // parses), replacing the previous picker that produced corrupt dates.
        if (window.flatpickr && ciEl && coEl) {
            var coMin = /^20\d{2}-\d{2}-\d{2}$/.test(ciEl.value) ? new Date(addDay(ciEl.value)) : new Date(Date.now() + 86400000);
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
                    // Auto-fill / bump check-out to check-in + 1 when it's empty or invalid.
                    if (! checkoutFp.selectedDates[0] || checkoutFp.selectedDates[0] <= sel[0]) {
                        checkoutFp.setDate(next);
                    }
                }
            });
        }

        // Guarantee the search always carries dates: if none were picked, fall
        // back to today / tomorrow (valid Y-m-d) at submit time.
        document.getElementById('date-range-picker')?.addEventListener('submit', function () {
            if (ciEl && !ciEl.value.trim()) { ciEl.value = @json(now()->format('Y-m-d')); }
            if (coEl && !coEl.value.trim()) {
                var base = (ciEl && /^20\d{2}-\d{2}-\d{2}$/.test(ciEl.value.trim())) ? new Date(ciEl.value) : new Date();
                base.setDate(base.getDate() + 1);
                coEl.value = base.getFullYear() + '-' + String(base.getMonth() + 1).padStart(2, '0') + '-' + String(base.getDate()).padStart(2, '0');
            }
        });
    })();
</script>    


@endpush