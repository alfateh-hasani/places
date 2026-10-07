@extends('layouts.master')


@section('content')

<section class="container py-8 lg:hidden cursor-pointer search-button" data-aos="zoom-in">
    <div class="px-6 py-3 bg-white shadow-xl rounded-full border border-border">
        <img src="{{ asset('assets/img/search-black.svg') }}" class="float-left rtl:float-right w-4 mr-5 py-2" alt="" width="16" height="16" />
        <div class="float-left rtl:float-right">
            <p class="font-semibold text-xs">
                {{ __('site.search') }}
            </p>
            <p class="text-sm">{{ __('site.search_mobile_desc') }}</p>
        </div>
        <div class="clear-both"></div>
    </div>
</section>


<section class="search lg:container z-40 xl:px-40 lg:py-16 h-[100vh] lg:h-auto fixed lg:relative left-0 bottom-0 right-0 bg-blackopacity lg:bg-[transparent]" data-aos="zoom-out">
    <form action="{{ route('apartments.search') }}" method="GET" class="absolute lg:relative bottom-0 lg:bottom-auto left-0 margin-0 w-full lg:w-auto lg:grid grid-cols-2 lg:grid-cols-5 gap-1 max-w-full py-5 lg:pl-10 pl-5 pr-5 bg-white shadow-xl rounded-xl lg:rounded-full border border-border" id="date-range-picker">
      
      <!-- العنوان والإغلاق -->
      <div class="mb-5 lg:hidden">
        <p class="float-left rtl:float-right font-semibold">{{ __('site.search_mobile') }}</p>
        <button type="button" class="float-right close-button"><img src="{{ asset('assets/img/close.svg') }}" alt="{{ __('site.close') }}" /></button>
        <div class="clear-both"></div>
      </div>
      
      <!-- حقل اختيار المدينة -->
      <div class="shadow-xl lg:shadow-none p-4 lg:p-0 rounded-lg mb-3 lg:mb-0 lg:rounded-none ">
        <label for="city_id" class="font-normal text-xs text-black">
            {{ __('site.filters_city_id') }}
        </label>
        <select name="city_id" id="city_id" aria-label="{{ __('site.filters_city_id') }}" class="select2 w-full border-0 font-semibold text-sm">
          @foreach ($cities as $item)
            <option value="{{ $item->id }}" {{ old('city_id', request('city_id')) == $item->id ? 'selected' : '' }}>
              {{ $item->ml('name') }}
            </option>   
          @endforeach
        </select>
      </div>
      
      <!-- حقل تسجيل الدخول (Check In) -->
      <div class="shadow-xl lg:shadow-none p-4 lg:p-0 rounded-lg mb-3 lg:mb-0 lg:rounded-none lg:px-4 lg:border-s border-blackopacity cursor-pointer ">
        <label for="datepicker-range-start" class="font-normal text-xs text-black">
            {{ __('site.filters_check_in') }}
        </label>
        <input
          id="datepicker-range-start"
          name="check_in"
          type="text"
          readonly
          autocomplete="off"
          aria-label="{{ __('site.filters_check_in') }}"
          class="cursor-pointer p-0 pt-1 text-black font-semibold text-sm block w-full border-0"
          placeholder="{{ now()->format('Y-m-d') }}"
          value="{{ old('check_in', request('check_in')) }}"
        />
      </div>
      
      <!-- حقل تسجيل الخروج (Check Out) -->
      <div class="shadow-xl lg:shadow-none p-4 lg:p-0 rounded-lg mb-3 lg:mb-0 lg:rounded-none lg:px-4 lg:border-s border-blackopacity cursor-pointer ">
        <label for="datepicker-range-end" class="font-normal text-xs text-black">
            {{ __('site.filters_check_out') }}
        </label>
        <input
          id="datepicker-range-end"
          name="check_out"
          type="text"
          readonly
          autocomplete="off"
          aria-label="{{ __('site.filters_check_out') }}"
          class="cursor-pointer p-0 pt-1 text-black font-semibold text-sm block w-full border-0"
          placeholder="{{ now()->addDay()->format('Y-m-d') }}"
          value="{{ old('check_out', request('check_out')) }}"
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
                <button type="button" id="decrement-button" data-input-counter-decrement="counter-input" aria-label="{{ __('site.filters_adults') }} -" class="flex-shrink-0 bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 dark:border-gray-600 hover:bg-gray-200 inline-flex items-center justify-center border border-gray-300 rounded-md h-5 w-5 focus:ring-gray-100 dark:focus:ring-gray-700 focus:ring-2 focus:outline-none">
                  <svg class="w-2.5 h-2.5 text-gray-900 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 2">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h16" />
                  </svg>
                </button>
                <input type="text" id="counter-input" name="adults" data-input-counter aria-label="{{ __('site.filters_adults') }}" class="flex-shrink-0 text-black border-0 bg-transparent text-sm font-normal max-w-[2.5rem] text-center p-1" value="{{ old('adults', request('adults', 1)) }}" required />
                <button type="button" id="increment-button" data-input-counter-increment="counter-input" aria-label="{{ __('site.filters_adults') }} +" class="flex-shrink-0 bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 dark:border-gray-600 hover:bg-gray-200 inline-flex items-center justify-center border border-gray-300 rounded-md h-5 w-5 focus:ring-gray-100 dark:focus:ring-gray-700 focus:ring-2 focus:outline-none">
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
                <button type="button" id="decrement-button1" data-input-counter-decrement="counter-input1" aria-label="{{ __('site.filters_children') }} -" class="flex-shrink-0 bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 dark:border-gray-600 hover:bg-gray-200 inline-flex items-center justify-center border border-gray-300 rounded-md h-5 w-5 focus:ring-gray-100 dark:focus:ring-gray-700 focus:ring-2 focus:outline-none">
                  <svg class="w-2.5 h-2.5 text-gray-900 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 2">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h16" />
                  </svg>
                </button>
                <input type="text" id="counter-input1" name="children" data-input-counter aria-label="{{ __('site.filters_children') }}" class="flex-shrink-0 text-black border-0 bg-transparent text-sm font-normal max-w-[2.5rem] text-center p-1" value="{{ old('children', request('children', 0)) }}" required />
                <button type="button" id="increment-button1" data-input-counter-increment="counter-input1" aria-label="{{ __('site.filters_children') }} +" class="flex-shrink-0 bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 dark:border-gray-600 hover:bg-gray-200 inline-flex items-center justify-center border border-gray-300 rounded-md h-5 w-5 focus:ring-gray-100 dark:focus:ring-gray-700 focus:ring-2 focus:outline-none">
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
  



<section class="list pt-2 sm:pt-20 pb-2 sm:pb-20">
    <div class="container">
        <h1 class="font-semibold text-xl sm:text-3xl text-black mb-6 sm:mb-10 text-center md:text-start rtl:md:text-right">
            {{ $city->ml('name') }}
        </h1>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 max-w-full mx-0">
            @if($apartments->isNotEmpty())
              @foreach($apartments as $apartment)
                  @include('apartment.card', ['apartment' => $apartment])
              @endforeach
            @else
              <div class="text-center w-full">
                  <p class="text-2xl font-semibold text-black"> {{ __('site.no_apartments') }}</p>
              </div>
            @endif
            
        </div>
        <div class="mt-6">
            {{ $apartments->links() }}
        </div>
    </div>
</section>

@endsection

@push('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

@push('js')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    (function () {
        // Use flatpickr with Y-m-d (the format the apartments filter page expects)
        // so the selected city/dates carry over cleanly, instead of the Flowbite
        // range picker that emitted MM/DD/YYYY and was rejected downstream.
        var ciEl = document.getElementById('datepicker-range-start');
        var coEl = document.getElementById('datepicker-range-end');

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
    })();
</script>
@endpush