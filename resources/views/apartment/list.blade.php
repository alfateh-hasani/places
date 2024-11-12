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
  

{{-- <section class="filter">
    <form action="{{ route('apartments.search') }}" method="GET">
        <div class="container">
            <div class="float-left rtl:float-right  rounded-xl bg-filterbackground border border-filterborder px-5 py-1.5 buttons mb-2 xl:mb-0" data-aos="fade-right">
                <p class="float-left rtl:float-right mr-2 font-semibold text-base text-black pt-1.5 mb-1 lg:mb-0">
                    {{ __('site.filters') }}
                </p>
                <div class="float-left rtl:float-right bg-blackopacity mr-2 mb-1 lg:mb-0">
                    <button id="dropdownSearchButton" data-dropdown-toggle="dropdownSearch" class="inline-flex items-center px-4 py-2 text-sm font-medium text-center text-black bg-filteritem rounded-lg hover:bg-filterhover hover:text-white" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18.047" height="18" viewBox="0 0 18.047 18" fill="currentColor">
                            <g id="business" transform="translate(-2 -2.054)">
                                <path id="Path_4477" data-name="Path 4477" d="M11.023,20.054A9.034,9.034,0,0,1,2,11.031c.5-11.971,17.553-11.967,18.047,0a9.034,9.034,0,0,1-9.023,9.023Zm0-17.187a8.173,8.173,0,0,0-8.164,8.164c.448,10.831,15.881,10.827,16.328,0a8.173,8.173,0,0,0-8.164-8.164Z" transform="translate(0 0)" />
                                <path id="Path_4478" data-name="Path 4478" d="M12.4,17.109H8.934v-.855H12.4a2.137,2.137,0,1,0,0-4.273h-.867a2.991,2.991,0,1,1,0-5.982H15v.855H11.535a2.137,2.137,0,1,0,0,4.273l.985,0a2.991,2.991,0,0,1-.117,5.98Z" transform="translate(-0.945 -0.524)" />
                                <rect id="Rectangle_19418" data-name="Rectangle 19418" width="0.867" height="12.852" transform="translate(10.59 4.609)"/>
                            </g>
                        </svg>
                        <span class="ml-2">
                            {{__('apartment.price')}}
                        </span>
                        <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                        </svg>
                    </button>

                    <div id="dropdownSearch" class="z-10 hidden bg-white rounded-lg shadow w-60">
                        <div class="relative pt-2">
                            <input type="range" id="min-price" min="40" max="600" value="40" step="10" class="absolute w-full h-1 bg-gray-200 appearance-none pointer-events-none" oninput="updateRange()">
                            <input type="range" id="max-price" min="40" max="600" value="600" step="10" class="absolute w-full h-1 bg-gray-200 appearance-none pointer-events-none" oninput="updateRange()">
                            <div class="range-track h-1 bg-gray-200 rounded"></div>
                            <div id="range-highlight" class="range-highlight absolute top-0 h-1 bg-black rounded"></div>
                        </div>

                        <!-- عرض القيم الأدنى والأعلى -->
                        <div class="flex justify-between mt-4">
                            <div class="text-sm text-gray-600">
                                {{ __('filters.minimum') }} <span id="min-price-label" class="font-semibold text-gray-800">40 SAR</span>
                            </div>
                            <div class="text-sm text-gray-600">
                                {{ __('filters.maximum') }} <span id="max-price-label" class="font-semibold text-gray-800">600 SAR</span>
                            </div>
                        </div>
                    </div>
              
                </div>
                <div class="float-left rtl:float-right bg-blackopacity mr-2 mb-1 lg:mb-0">
                    <button id="dropdownSearchButton" data-dropdown-toggle="dropdownSearch1" class="inline-flex items-center px-4 py-2 text-sm font-medium text-center text-black bg-filteritem rounded-lg hover:bg-filterhover hover:text-white" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" width="17.741" height="17.017" viewBox="0 0 17.741 17.017">
                          <path id="Icon_awesome-star" data-name="Icon awesome-star" d="M8.9.556,6.863,4.693,2.3,5.358a1,1,0,0,0-.553,1.706l3.3,3.218-.781,4.546a1,1,0,0,0,1.45,1.053L9.8,13.735l4.084,2.147a1,1,0,0,0,1.45-1.053l-.781-4.546,3.3-3.218A1,1,0,0,0,17.3,5.358l-4.565-.666L10.7.556A1,1,0,0,0,8.9.556Z" transform="translate(-0.929 0.501)" fill="none" stroke="currentColor" stroke-width="1"/>
                        </svg>
                        <span class="ml-2">Rate</span>
                        <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                        </svg>
                    </button>

                    <div id="dropdownSearch1" class="z-10 hidden bg-white rounded-lg shadow w-60">
                        <ul class="h-48 px-3 py-3 overflow-y-auto text-sm text-gray-700" aria-labelledby="dropdownSearchButton">
                            @for ($i = 1; $i <= 5; $i++)
                                <li>
                                    <div class="flex items-center p-2 rounded hover:bg-gray-100">
                                        <input id="checkbox-item-5" type="checkbox" value="" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded">
                                        <label for="checkbox-item-5" class="w-full ms-2 text-sm font-medium text-gray-900 rounded"> 
                                            @for ($j = 0; $j < $i; $j++)
                                                <img class="inline-block -translate-y-0.5" src="{{asset('assets/img/star.svg')}}" />
                                            @endfor 
                                        </label>
                                    </div>
                                </li>
                            @endfor
                        </ul>
                    </div>
                </div>
                <div class="float-left rtl:float-right bg-blackopacity mr-2 mb-1 lg:mb-0">
                    <button id="dropdownSearchButton" data-dropdown-toggle="dropdownSearch2" class="inline-flex items-center px-4 py-2 text-sm font-medium text-center text-black bg-filteritem rounded-lg hover:bg-filterhover hover:text-white" type="button">
                        <svg fill="currentColor" id="group" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">
                            <path id="Path_4479" data-name="Path 4479" d="M3.333,4H.667A.63.63,0,0,1,0,3.333V.667A.63.63,0,0,1,.667,0H3.333A.63.63,0,0,1,4,.667V3.333A.63.63,0,0,1,3.333,4Zm-2-1.333H2.667V1.333H1.333ZM15.333,4H12.667A.63.63,0,0,1,12,3.333V.667A.63.63,0,0,1,12.667,0h2.667A.63.63,0,0,1,16,.667V3.333A.63.63,0,0,1,15.333,4Zm-2-1.333h1.333V1.333H13.333ZM3.333,16H.667A.63.63,0,0,1,0,15.333V12.667A.63.63,0,0,1,.667,12H3.333A.63.63,0,0,1,4,12.667v2.667A.63.63,0,0,1,3.333,16Zm-2-1.333H2.667V13.333H1.333Zm14,1.333H12.667A.63.63,0,0,1,12,15.333V12.667A.63.63,0,0,1,12.667,12h2.667a.63.63,0,0,1,.667.667v2.667A.63.63,0,0,1,15.333,16Zm-2-1.333h1.333V13.333H13.333Z"/>
                            <path id="Path_4480" data-name="Path 4480" d="M2.6,12.8a.567.567,0,0,1-.6-.6V3.8a.567.567,0,0,1,.6-.6.567.567,0,0,1,.6.6v8.4A.567.567,0,0,1,2.6,12.8ZM12.2,14H3.8a.6.6,0,1,1,0-1.2h8.4a.6.6,0,0,1,0,1.2Zm1.2-1.2a.567.567,0,0,1-.6-.6V3.8a.6.6,0,1,1,1.2,0v8.4A.567.567,0,0,1,13.4,12.8ZM12.2,3.2H3.8a.567.567,0,0,1-.6-.6A.567.567,0,0,1,3.8,2h8.4a.567.567,0,0,1,.6.6A.567.567,0,0,1,12.2,3.2Z" transform="translate(0 0)"/>
                        </svg>
                        <span class="ml-2">Aria</span>
                        <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                        </svg>
                    </button>

                    <div id="dropdownSearch2" class="z-10 hidden bg-white rounded-lg shadow w-60">
                        <ul class="h-48 px-3 py-3 overflow-y-auto text-sm text-gray-700" aria-labelledby="dropdownSearchButton">
                            <li>
                                <div class="flex items-center p-2 rounded hover:bg-gray-100">
                                    <input id="checkbox-item-1" type="checkbox" value="" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded">
                                    <label for="checkbox-item-1" class="w-full ms-2 text-sm font-medium text-gray-900 rounded">0$ - 1.000$</label>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center p-2 rounded hover:bg-gray-100">
                                    <input id="checkbox-item-2" type="checkbox" value="" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded">
                                    <label for="checkbox-item-2" class="w-full ms-2 text-sm font-medium text-gray-900 rounded">2.000$ - 3.000$</label>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center p-2 rounded hover:bg-gray-100">
                                    <input checked id="checkbox-item-3" type="checkbox" value="" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded">
                                    <label for="checkbox-item-3" class="w-full ms-2 text-sm font-medium text-gray-900 rounded">2.000$ - 3.000$</label>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center p-2 rounded hover:bg-gray-100">
                                    <input id="checkbox-item-4" type="checkbox" value="" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded">
                                    <label for="checkbox-item-4" class="w-full ms-2 text-sm font-medium text-gray-900 rounded">2.000$ - 3.000$</label>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center p-2 rounded hover:bg-gray-100">
                                    <input id="checkbox-item-5" type="checkbox" value="" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded">
                                    <label for="checkbox-item-5" class="w-full ms-2 text-sm font-medium text-gray-900 rounded">2.000$ - 3.000$</label>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center p-2 rounded hover:bg-gray-100">
                                    <input checked id="checkbox-item-6" type="checkbox" value="" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded">
                                    <label for="checkbox-item-6" class="w-full ms-2 text-sm font-medium text-gray-900 rounded">2.000$ - 3.000$</label>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center p-2 rounded hover:bg-gray-100">
                                    <input id="checkbox-item-7" type="checkbox" value="" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded">
                                    <label for="checkbox-item-7" class="w-full ms-2 text-sm font-medium text-gray-900 rounded">2.000$ - 3.000$</label>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center p-2 rounded hover:bg-gray-100">
                                    <input id="checkbox-item-8" type="checkbox" value="" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded">
                                    <label for="checkbox-item-8" class="w-full ms-2 text-sm font-medium text-gray-900 rounded">2.000$ - 3.000$</label>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center p-2 rounded hover:bg-gray-100">
                                    <input checked id="checkbox-item-9" type="checkbox" value="" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded">
                                    <label for="checkbox-item-9" class="w-full ms-2 text-sm font-medium text-gray-900 rounded">2.000$ - 3.000$</label>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="float-left rtl:float-right bg-blackopacity mr-2 mb-1 lg:mb-0">
                    <button id="dropdownSearchButton" data-dropdown-toggle="dropdownSearch3" class="inline-flex items-center px-4 py-2 text-sm font-medium text-center text-black bg-filteritem rounded-lg hover:bg-filterhover hover:text-white" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="currentColor">
                            <path id="home" d="M9,0a9,9,0,1,0,9,9A9,9,0,0,0,9,0ZM9,.375A8.625,8.625,0,1,1,.375,9,8.622,8.622,0,0,1,9,.375ZM3.934,3.75a.188.188,0,0,0-.187.188V14.063a.188.188,0,0,0,.187.187H14.066a.188.188,0,0,0,.188-.187V3.938a.187.187,0,0,0-.188-.187H3.934Zm.187.375H7.48l-.04.339A7.469,7.469,0,0,1,5.352,8.813H4.121Zm3.737,0h2.284l.045.383a7.755,7.755,0,0,0,.222,1.117H7.592a7.753,7.753,0,0,0,.221-1.117Zm2.662,0h3.352V8.813H12.648a7.47,7.47,0,0,1-2.089-4.348ZM7.479,6h3.041c.041.126.08.252.127.375H9.559a.188.188,0,0,0-.188.187V7.5h-.75V6.562a.188.188,0,0,0-.187-.187H7.352C7.4,6.252,7.439,6.126,7.479,6Zm-.287.75H8.246V7.5H6.815A7.875,7.875,0,0,0,7.192,6.75Zm2.554,0h1.061a7.788,7.788,0,0,0,.377.75H9.746ZM6.581,7.875h4.838a7.785,7.785,0,0,0,.959,1.2V9.7a9.073,9.073,0,0,0-.63,1.546h-5.5A9.076,9.076,0,0,0,5.621,9.7V9.073A7.767,7.767,0,0,0,6.581,7.875ZM4.121,9.188H5.246v.375H4.121Zm8.632,0h1.117v.375H12.754Zm-8.632.75H5.315a8.7,8.7,0,0,1,.89,2.853l.141,1.085H4.121Zm8.563,0h1.186v3.938H11.654l.141-1.085A8.7,8.7,0,0,1,12.685,9.938ZM6.352,11.625h5.3a9.1,9.1,0,0,0-.225,1.117l-.05.383h-.315v-.562a.188.188,0,0,0-.188-.187H7.121a.188.188,0,0,0-.187.187v.563H6.626l-.05-.383A9.1,9.1,0,0,0,6.352,11.625Zm.957,1.125h3.375v.563a.188.188,0,0,0,.187.188h.454l-.049.375H6.724L6.675,13.5h.447a.188.188,0,0,0,.188-.187Z"/>
                        </svg>
                        <span class="ml-2">Rooms</span>
                        <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                        </svg>
                    </button>

                    <div id="dropdownSearch3" class="z-10 hidden bg-white rounded-lg shadow w-60">
                        <ul class="h-48 px-3 py-3 overflow-y-auto text-sm text-gray-700" aria-labelledby="dropdownSearchButton">
                            <li>
                                <div class="flex items-center p-2 rounded hover:bg-gray-100">
                                    <input id="checkbox-item-1" type="checkbox" value="" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded">
                                    <label for="checkbox-item-1" class="w-full ms-2 text-sm font-medium text-gray-900 rounded">0$ - 1.000$</label>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center p-2 rounded hover:bg-gray-100">
                                    <input id="checkbox-item-2" type="checkbox" value="" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded">
                                    <label for="checkbox-item-2" class="w-full ms-2 text-sm font-medium text-gray-900 rounded">2.000$ - 3.000$</label>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center p-2 rounded hover:bg-gray-100">
                                    <input checked id="checkbox-item-3" type="checkbox" value="" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded">
                                    <label for="checkbox-item-3" class="w-full ms-2 text-sm font-medium text-gray-900 rounded">2.000$ - 3.000$</label>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center p-2 rounded hover:bg-gray-100">
                                    <input id="checkbox-item-4" type="checkbox" value="" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded">
                                    <label for="checkbox-item-4" class="w-full ms-2 text-sm font-medium text-gray-900 rounded">2.000$ - 3.000$</label>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center p-2 rounded hover:bg-gray-100">
                                    <input id="checkbox-item-5" type="checkbox" value="" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded">
                                    <label for="checkbox-item-5" class="w-full ms-2 text-sm font-medium text-gray-900 rounded">2.000$ - 3.000$</label>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center p-2 rounded hover:bg-gray-100">
                                    <input checked id="checkbox-item-6" type="checkbox" value="" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded">
                                    <label for="checkbox-item-6" class="w-full ms-2 text-sm font-medium text-gray-900 rounded">2.000$ - 3.000$</label>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center p-2 rounded hover:bg-gray-100">
                                    <input id="checkbox-item-7" type="checkbox" value="" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded">
                                    <label for="checkbox-item-7" class="w-full ms-2 text-sm font-medium text-gray-900 rounded">2.000$ - 3.000$</label>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center p-2 rounded hover:bg-gray-100">
                                    <input id="checkbox-item-8" type="checkbox" value="" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded">
                                    <label for="checkbox-item-8" class="w-full ms-2 text-sm font-medium text-gray-900 rounded">2.000$ - 3.000$</label>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center p-2 rounded hover:bg-gray-100">
                                    <input checked id="checkbox-item-9" type="checkbox" value="" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded">
                                    <label for="checkbox-item-9" class="w-full ms-2 text-sm font-medium text-gray-900 rounded">2.000$ - 3.000$</label>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="float-left rtl:float-right bg-blackopacity mr-2 mb-1 lg:mb-0">
                    <button id="dropdownSearchButton" data-dropdown-toggle="dropdownSearch4" class="inline-flex items-center px-4 py-2 text-sm font-medium text-center text-black bg-filteritem rounded-lg hover:bg-filterhover hover:text-white" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="12.235" viewBox="0 0 16 12.235" fill="currentColor">
                            <path id="Path_4482" data-name="Path 4482" d="M16.511,17.294H7.489l-.8.8a.471.471,0,0,1-.666-.666l.138-.138H5.882A1.882,1.882,0,0,1,4,15.412v-.941a1.882,1.882,0,0,1,1.882-1.882H7.075A1.883,1.883,0,0,1,8.706,9.765h1.882A1.878,1.878,0,0,1,12,10.4a1.878,1.878,0,0,1,1.412-.637h1.882a1.883,1.883,0,0,1,1.63,2.824h1.193A1.882,1.882,0,0,1,20,14.471v.941a1.882,1.882,0,0,1-1.882,1.882h-.276l.138.138a.471.471,0,0,1-.666.666Zm1.607-3.765H5.882a.941.941,0,0,0-.941.941v.941a.941.941,0,0,0,.941.941H18.118a.941.941,0,0,0,.941-.941v-.941A.941.941,0,0,0,18.118,13.529Zm-2.824-.941a.941.941,0,1,0,0-1.882H13.412a.941.941,0,1,0,0,1.882Zm-6.588,0h1.882a.941.941,0,1,0,0-1.882H8.706a.941.941,0,1,0,0,1.882ZM5.882,11.176a.471.471,0,0,1-.941,0V8.353A2.2,2.2,0,0,1,6.962,6H17.038a2.2,2.2,0,0,1,2.021,2.353v2.824a.471.471,0,1,1-.941,0V8.353a1.284,1.284,0,0,0-1.08-1.412H6.962a1.284,1.284,0,0,0-1.08,1.412Z" transform="translate(-4 -6)" fill-rule="evenodd"/>
                        </svg>
                        <span class="ml-2">Bids</span>
                        <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                        </svg>
                    </button>

                    <div id="dropdownSearch4" class="z-10 hidden bg-white rounded-lg shadow w-60">
                        <ul class="h-48 px-3 py-3 overflow-y-auto text-sm text-gray-700" aria-labelledby="dropdownSearchButton">
                            <li>
                                <div class="flex items-center p-2 rounded hover:bg-gray-100">
                                    <input id="checkbox-item-1" type="checkbox" value="" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded">
                                    <label for="checkbox-item-1" class="w-full ms-2 text-sm font-medium text-gray-900 rounded">0$ - 1.000$</label>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center p-2 rounded hover:bg-gray-100">
                                    <input id="checkbox-item-2" type="checkbox" value="" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded">
                                    <label for="checkbox-item-2" class="w-full ms-2 text-sm font-medium text-gray-900 rounded">2.000$ - 3.000$</label>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center p-2 rounded hover:bg-gray-100">
                                    <input checked id="checkbox-item-3" type="checkbox" value="" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded">
                                    <label for="checkbox-item-3" class="w-full ms-2 text-sm font-medium text-gray-900 rounded">2.000$ - 3.000$</label>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center p-2 rounded hover:bg-gray-100">
                                    <input id="checkbox-item-4" type="checkbox" value="" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded">
                                    <label for="checkbox-item-4" class="w-full ms-2 text-sm font-medium text-gray-900 rounded">2.000$ - 3.000$</label>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center p-2 rounded hover:bg-gray-100">
                                    <input id="checkbox-item-5" type="checkbox" value="" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded">
                                    <label for="checkbox-item-5" class="w-full ms-2 text-sm font-medium text-gray-900 rounded">2.000$ - 3.000$</label>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center p-2 rounded hover:bg-gray-100">
                                    <input checked id="checkbox-item-6" type="checkbox" value="" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded">
                                    <label for="checkbox-item-6" class="w-full ms-2 text-sm font-medium text-gray-900 rounded">2.000$ - 3.000$</label>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center p-2 rounded hover:bg-gray-100">
                                    <input id="checkbox-item-7" type="checkbox" value="" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded">
                                    <label for="checkbox-item-7" class="w-full ms-2 text-sm font-medium text-gray-900 rounded">2.000$ - 3.000$</label>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center p-2 rounded hover:bg-gray-100">
                                    <input id="checkbox-item-8" type="checkbox" value="" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded">
                                    <label for="checkbox-item-8" class="w-full ms-2 text-sm font-medium text-gray-900 rounded">2.000$ - 3.000$</label>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center p-2 rounded hover:bg-gray-100">
                                    <input checked id="checkbox-item-9" type="checkbox" value="" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded">
                                    <label for="checkbox-item-9" class="w-full ms-2 text-sm font-medium text-gray-900 rounded">2.000$ - 3.000$</label>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="float-right border-0 lg:border-l border-commentborder pl-0 lg:pl-5 my-1.5 w-full lg:w-auto">
                    <button class="float-left rtl:float-right w-6 h-6 rounded-md mr-2 text-center bg-sort">
                        <svg class="w-2.5 h-2.5 inline-block" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="#000" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                        </svg>
                    </button>
                    <button class="float-left rtl:float-right w-6 h-6 rounded-md text-center rotate-180 bg-sortactive">
                        <svg class="w-2.5 h-2.5 inline-block" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="#fff" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                        </svg>
                    </button>
                    <a class="float-left rtl:float-right">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="15" viewBox="0 0 14 15" class="inline-block ml-5 scale-105">
                          <g id="Group_7786" data-name="Group 7786" transform="translate(-344 -557)">
                            <path id="Icon_open-reload" data-name="Icon open-reload" d="M4.408,0a4.408,4.408,0,1,0,3.13,7.538l-.793-.793A3.309,3.309,0,1,1,4.4,1.1a3.206,3.206,0,0,1,2.3,1l-1.2,1.2H8.806V0L7.494,1.311A4.384,4.384,0,0,0,4.4,0Z" transform="translate(344 561)"/>
                          </g>
                        </svg>
                        <span>Refresh</span>
                    </a>
                    <div class="clear-both"></div>
                </div>
                <div class="clear-both"></div>
            </div>
            <button class="float-right rounded-xl h-12 text-center bg-price font-semibold text-base text-white w-full xl:w-48" data-aos="fade-left"><img class="inline-block mr-4" src="assets/img/filter-icon.svg" /> Filter</button>
            <div class="clear-both"></div>
        </div>
    </form>
</section> --}}

<section class="list pt-2 sm:pt-20 pb-2 sm:pb-20">
    <div class="container">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 max-w-full mx-0">
            @foreach($apartments as $apartment)
                @include('apartment.card', ['apartment' => $apartment])
            @endforeach
        </div>
    </div>
</section>

@endsection
@push('js')
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
@endpush