<section class="container py-8 lg:hidden cursor-pointer search-button -translate-y-[50%]">
    <div class="px-6 py-3 bg-white shadow-xl rounded-full border border-border">
        <img src="{{ asset('assets/img/search-black.svg') }}" class="ltr:float-left rtl:float-right w-4 me-5 py-2" />
        <div class="ltr:float-left rtl:float-right">
            <p class="font-semibold text-xs">Where To ?</p>
            <p class="text-sm">Check In . Check Out . Add Guest</p>
        </div>
        <div class="clear-both"></div>
    </div>
</section>

<section class="search lg:container z-40 xl:px-40 lg:py-16 h-[100vh] lg:h-auto fixed lg:relative left-0 bottom-0 right-0 bg-blackopacity lg:bg-[transparent] lg:-translate-y-[50%]">
    <form
        action="{{ route('apartments.search') }}"
        method="GET"
        class="absolute lg:relative bottom-0 lg:bottom-auto left-0 margin-0 w-full lg:w-auto lg:grid grid-cols-2 lg:grid-cols-5 gap-1 max-w-full py-5 lg:pl-10 pl-5 pr-5 bg-white shadow-xl rounded-xl lg:rounded-full border border-border"
        id="date-range-picker" date-rangepicker>
        <div class="mb-5 lg:hidden">
            <p class="float-left font-semibold">Stays</p>
            <button type="button" class="float-right close-button"><img src="{{ asset('assets/img/close.svg') }}" /></button>
            <div class="clear-both"></div>
        </div>
        <div class="shadow-xl lg:shadow-none p-4 lg:p-0 rounded-lg mb-3 lg:mb-0 lg:rounded-none ">
            <p class="font-normal text-xs text-black">{{ __('site.filters_city_id') }}</p>
            <select name="city_id" class="select2 w-full border-0 font-semibold text-sm">
              @foreach ($cities as $item)
                <option value="{{ $item->id }}">{{ $item->ml('name') }}</option>
              @endforeach
            </select>
        </div>
        <div
            class="shadow-xl lg:shadow-none p-4 lg:p-0 rounded-lg mb-3 lg:mb-0 lg:rounded-none lg:px-4 lg:border-s border-blackopacity cursor-pointer ">
            <p class="font-normal text-xs text-black">{{ __('site.filters_check_in') }}</p>
            <input id="datepicker-range-start" name="check_in" type="text"
                class="cursor-pointer p-0 pt-1 text-black font-semibold text-sm block w-full border-0"
                placeholder="27/09/2024" autocomplete="off" />
        </div>
        <div
            class="shadow-xl lg:shadow-none p-4 lg:p-0 rounded-lg mb-3 lg:mb-0 lg:rounded-none lg:px-4 lg:border-s border-blackopacity cursor-pointer ">
            <p class="font-normal text-xs text-black">{{ __('site.filters_check_out') }}</p>
            <input id="datepicker-range-end" name="check_out" type="text"
                class="cursor-pointer p-0 pt-1 text-black font-semibold text-sm block w-full border-0"
                placeholder="29/09/2024"  autocomplete="off"/>
        </div>
        <div
            class="shadow-xl lg:shadow-none p-4 lg:p-0 rounded-lg mb-3 lg:mb-0 lg:rounded-none lg:px-4 lg:border-s border-blackopacity cursor-pointer persons relative ">
            <p class="font-normal text-xs text-black">{{ __('site.filters_guests') }}</p>
            <p class="font-semibold text-sm text-black py-1 content">{{ __('site.add_guests') }}</p>
            <ul class="hidden lg:absolute w-72 bg-white p-4 border border-border rounded-lg">
                <li class="border-b border-blackopacity pb-4 mb-4">
                    <p class="inline-block w-36 text-lg">{{ __('site.filters_adults') }}<span class="block text-xs opacity-50">Ages 13 or above</span></p>
                    <div class="inline-block">
                        <div class="relative flex items-center">
                            <button type="button" id="decrement-button" data-input-counter-decrement="counter-input"
                                class="flex-shrink-0 inline-flex items-center justify-center border border-gray-300 rounded-full h-8 w-8 hover:border-title">
                                <svg class="w-2.5 h-2.5 text-gray-900 dark:text-white" aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 2">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="M1 1h16" />
                                </svg>
                            </button>
                            <input type="text" id="counter-input" data-input-counter
                                class="flex-shrink-0 text-black border-0 bg-transparent text-sm font-normal max-w-[2.5rem] text-center p-1"
                                placeholder="" value="1" required />
                            <button type="button" id="increment-button" data-input-counter-increment="counter-input"
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
                    <p class="inline-block w-36">{{ __('site.filters_children') }}<span class="block text-xs opacity-50">Ages 13 or above</span></p>
                    <div class="inline-block">
                        <div class="relative flex items-center">
                            <button type="button" id="decrement-button" data-input-counter-decrement="counter-input1"
                            class="flex-shrink-0 inline-flex items-center justify-center border border-gray-300 rounded-full h-8 w-8 hover:border-title">
                                <svg class="w-2.5 h-2.5 text-gray-900 dark:text-white" aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 2">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="M1 1h16" />
                                </svg>
                            </button>
                            <input type="text" id="counter-input1" data-input-counter
                                class="flex-shrink-0 text-black border-0 bg-transparent text-sm font-normal max-w-[2.5rem] text-center p-1"
                                placeholder="" value="1" required />
                            <button type="button" id="increment-button" data-input-counter-increment="counter-input1"
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
                class="bg-price text-white w-full h-11 text-center rounded-lg lg:rounded-full hover:bg-black ease-in-out duration-200">
                <img class="inline-block -translate-y-0.5 me-2" src="{{ asset('assets/img/search.svg') }}" />{{ __('site.search') }}
            </button>
        </div>
    </form>
</section>