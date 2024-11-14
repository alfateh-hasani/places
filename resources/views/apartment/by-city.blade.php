@extends('layouts.master')


@section('content')
 
<section class="container py-8 lg:hidden cursor-pointer search-button" data-aos="zoom-in">
    <div class="px-6 py-3 bg-white shadow-xl rounded-full border border-border">
        <img src="assets/img/search-black.svg" class="float-left rtl:float-right w-4 mr-5 py-2" />
        <div class="float-left rtl:float-right">
            <p class="font-semibold text-xs">
                {{ __('site.search') }}
            </p>
            <p class="text-sm">Check In . Check Out . Add Guest</p>
        </div>
        <div class="clear-both"></div>
    </div>
</section>


<section class="search lg:container z-40 xl:px-40 lg:py-16 h-[100vh] lg:h-auto fixed lg:relative left-0 bottom-0 right-0 bg-blackopacity lg:bg-[transparent]" data-aos="zoom-out">
    <form action="{{ route('apartments.search') }}" method="GET" class="absolute lg:relative bottom-0 lg:bottom-auto left-0 margin-0 w-full lg:w-auto lg:grid grid-cols-2 lg:grid-cols-5 gap-1 max-w-full py-5 lg:pl-10 pl-5 pr-5 bg-white shadow-xl rounded-xl lg:rounded-full border border-border" id="date-range-picker" date-rangepicker>
      
      <!-- العنوان والإغلاق -->
      <div class="mb-5 lg:hidden">
        <p class="float-left rtl:float-right font-semibold">Stays</p>
        <button type="button" class="float-right close-button"><img src="assets/img/close.svg" /></button>
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
      
      <!-- حقل تسجيل الدخول (Check In) -->
      <div class="shadow-xl lg:shadow-none p-4 lg:p-0 rounded-lg mb-3 lg:mb-0 lg:rounded-none lg:px-4 lg:border-l border-blackopacity cursor-pointer ">
        <p class="font-normal text-xs text-black">  
            {{ __('site.filters_check_in') }}
        </p>
        <input
          id="datepicker-range-start"
          name="check_in"
          type="text"
          class="cursor-pointer p-0 pt-1 text-black font-semibold text-sm block w-full border-0"
          placeholder="27/09/2024"
          value="{{ old('check_in', request('check_in')) }}"
        />
      </div>
      
      <!-- حقل تسجيل الخروج (Check Out) -->
      <div class="shadow-xl lg:shadow-none p-4 lg:p-0 rounded-lg mb-3 lg:mb-0 lg:rounded-none lg:px-4 lg:border-l border-blackopacity cursor-pointer ">
        <p class="font-normal text-xs text-black">  
            {{ __('site.filters_check_out') }}
        </p>
        <input
          id="datepicker-range-end"
          name="check_out"
          type="text"
          class="cursor-pointer p-0 pt-1 text-black font-semibold text-sm block w-full border-0"
          placeholder="29/09/2024"
          value="{{ old('check_out', request('check_out')) }}"
        />
      </div>
      
      <!-- حقل إضافة الضيوف (Guests) -->
      <div class="shadow-xl lg:shadow-none p-4 lg:p-0 rounded-lg mb-3 lg:mb-0 lg:rounded-none lg:px-4 lg:border-l border-blackopacity cursor-pointer persons relative ">
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
  



<section class="list pt-2 sm:pt-20 pb-2 sm:pb-20">
    <div class="container">
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