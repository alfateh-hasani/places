<section class="filter">
    <form action="{{ route('apartments.search') }}" method="GET">
        <input type="hidden" name="check_in" value="{{ request('check_in') }}">
        <input type="hidden" name="check_out" value="{{ request('check_out') }}">
        <input type="hidden" name="city_id" value="{{ request('city_id') }}">
    
        <div class="container">
            <div class="rtl:float-right float-left rounded-xl bg-filterbackground border border-filterborder px-5 py-1.5 buttons mb-2 xl:mb-0" data-aos="fade-right rtl:fade-left">
                <p class="rtl:float-right float-left rtl:ml-2 mr-2 font-semibold text-base text-black pt-1.5 mb-1 lg:mb-0">
                    {{ __('site.filters') }}   
                </p>
                
                <!-- Price Range Filter -->
                <div class="rtl:float-right float-left rtl:ml-2 mr-2 mb-1 lg:mb-0">
                    <button id="dropdownPriceButton" data-dropdown-toggle="dropdownPrice" class="inline-flex items-center px-4 py-2 text-sm font-medium text-center text-black bg-filteritem rounded-lg hover:bg-filterhover hover:text-white" type="button">
                        <span class="rtl:mr-2 ml-2">{{ __('filters.price') }}</span>
                        <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4" />
                        </svg>
                    </button>
                    
                    <div id="dropdownPrice" class="z-10 hidden bg-white rounded-lg shadow w-60 px-4 py-4">
                        <label for="priceRange" class="block text-sm font-medium text-gray-700">{{ __('filters.price_range') }}</label>
                        <input id="priceRange" name="price_range" type="range" min="{{ $filter_keys['min_price'] }}" max="{{ $filter_keys['max_price'] }}" value="{{ $filter_keys['min_price'] }}" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                        <div class="flex justify-between text-sm text-gray-700 mt-2">
                            <span id="minPriceValue">{{ $filter_keys['min_price'] }}$</span>
                            <span id="maxPriceValue">{{ $filter_keys['max_price'] }}$</span>
                        </div>
                    </div>
                </div>
    
                <!-- Rate Filter -->
                <div class="rtl:float-right float-left rtl:ml-2 mr-2 mb-1 lg:mb-0">
                    <button id="dropdownRateButton" data-dropdown-toggle="dropdownRate" class="inline-flex items-center px-4 py-2 text-sm font-medium text-center text-black bg-filteritem rounded-lg hover:bg-filterhover hover:text-white" type="button">
                        <span class="rtl:mr-2 ml-2">{{ __('filters.rate') }}</span>
                        <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4" />
                        </svg>
                    </button>
                    
                    <div id="dropdownRate" class="z-10 hidden bg-white rounded-lg shadow w-60">
                        <ul class="h-48 px-3 py-3 overflow-y-auto text-sm text-gray-700" aria-labelledby="dropdownRateButton">
                            @for ($i = 1; $i <= 5; $i++)
                                <li>
                                    <div class="flex items-center p-2 rounded hover:bg-gray-100">
                                        <input id="rate-{{ $i }}" type="checkbox" value="{{ $i }}" 
                                        name="rate" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded" 
                                        {{ in_array($i, request('rate', [])) ? 'checked' : '' }}>
                                        <label for="rate-{{ $i }}" class="w-full ms-2 text-sm font-medium text-gray-900 rounded">
                                            @for ($j = 1; $j <= $i; $j++)
                                                <img class="inline-block -translate-y-0.5" src="{{ asset('assets/img/star.svg') }}" alt="Star">
                                            @endfor
                                        </label>
                                    </div>
                                </li>
                            @endfor
                        </ul>
                    </div>
                </div>
    
                <!-- Area Range Filter -->
                <div class="rtl:float-right float-left rtl:ml-2 mr-2 mb-1 lg:mb-0">
                    <button id="dropdownAreaButton" data-dropdown-toggle="dropdownArea" class="inline-flex items-center px-4 py-2 text-sm font-medium text-center text-black bg-filteritem rounded-lg hover:bg-filterhover hover:text-white" type="button">
                        <span class="rtl:mr-2 ml-2">{{ __('filters.area') }}</span>
                        <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4" />
                        </svg>
                    </button>
    
                    <div id="dropdownArea" class="z-10 hidden bg-white rounded-lg shadow w-60 px-4 py-4">
                        <label for="areaRange" class="block text-sm font-medium text-gray-700">{{ __('filters.area_range') }}</label>
                        <input id="areaRange" name="area_range" type="range" min="{{ $filter_keys['min_area'] }}" max="{{ $filter_keys['max_area'] }}" value="{{ request('area_range') }}" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer" oninput="updateAreaRange(this.value)">
                        <div class="flex justify-between text-sm text-gray-700 mt-2">
                            <span id="minAreaValue">{{ $filter_keys['min_area'] }} m²</span>
                            <span id="maxAreaValue">{{ $filter_keys['max_area'] }} m²</span>
                        </div>
                    </div>
                </div>
    
                <!-- Rooms Filter -->
                <div class="rtl:float-right float-left rtl:ml-2 mr-2 mb-1 lg:mb-0">
                    <button id="dropdownSearchButton" data-dropdown-toggle="dropdownSearch3" class="inline-flex items-center px-4 py-2 text-sm font-medium text-center text-black bg-filteritem rounded-lg hover:bg-filterhover hover:text-white" type="button">
                        <span class="rtl:mr-2 ml-2">{{ __('filters.rooms') }}</span>
                        <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4" />
                        </svg>
                    </button>
                    
                    <div id="dropdownSearch3" class="z-10 hidden bg-white rounded-lg shadow w-60">
                        <ul class="h-48 px-3 py-3 overflow-y-auto text-sm text-gray-700" aria-labelledby="dropdownSearchButton">
                            @foreach($filter_keys['rooms_options'] as $roomId => $roomLabel)
                                <li>
                                    <div class="flex items-center p-2 rounded hover:bg-gray-100">
                                        <input name="rooms[]" id="checkbox-room-{{ $roomId }}" type="checkbox" value="{{ $roomId }}" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded" {{ in_array($roomId, request('rooms', [])) ? 'checked' : '' }}>
                                        <label for="checkbox-room-{{ $roomId }}" class="w-full ms-2 text-sm font-medium text-gray-900 rounded">
                                            {{ $roomLabel }}
                                        </label>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
    
                <!-- Beds Filter -->
                <div class="rtl:float-right float-left rtl:ml-2 mr-2 mb-1 lg:mb-0">
                    <button id="dropdownSearchButton" data-dropdown-toggle="dropdownSearch4" class="inline-flex items-center px-4 py-2 text-sm font-medium text-center text-black bg-filteritem rounded-lg hover:bg-filterhover hover:text-white" type="button">
                        <span class="rtl:mr-2 ml-2">{{ __('filters.beds') }}</span>
                        <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4" />
                        </svg>
                    </button>
                    
                    <div id="dropdownSearch4" class="z-10 hidden bg-white rounded-lg shadow w-60">
                        <ul class="h-48 px-3 py-3 overflow-y-auto text-sm text-gray-700" aria-labelledby="dropdownSearchButton">
                            @foreach($filter_keys['beds_options'] as $id => $bedsCount)
                                <li>
                                    <div class="flex items-center p-2 rounded hover:bg-gray-100">
                                        <input id="checkbox-bed-{{ $bedsCount }}" type="checkbox" name="beds[]" value="{{ $id }}" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded" {{ in_array($id, request('beds', [])) ? 'checked' : '' }}>
                                        <label for="checkbox-bed-{{ $bedsCount }}" class="w-full ms-2 text-sm font-medium text-gray-900 rounded">
                                            {{ $bedsCount }}
                                        </label>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
    
                <div class="clear-both"></div>
            </div>
            
            <button class="float-right rtl:float-left rounded-xl h-12 text-center bg-price font-semibold text-base text-white w-full xl:w-48" data-aos="fade-left rtl:fade-right">
                <img class="inline-block mr-4" src="{{asset('assets/img/filter-icon.svg')}}" />
                {{ __('filters.apply_filters') }}
            </button>
    
            <div class="clear-both"></div>
        </div>
    </form>
    
    
</section>
 
@push('js')
    <script>
        // Price Range
        const priceRange = document.getElementById('priceRange');
        const minPriceValue = document.getElementById('minPriceValue');
        const maxPriceValue = document.getElementById('maxPriceValue');
        minPriceValue.textContent = priceRange.min + '$';
        maxPriceValue.textContent = priceRange.max + '$';
        priceRange.oninput = function() {
            minPriceValue.textContent = this.value + '$';
        }
        priceRange.oninput = function() {
            maxPriceValue.textContent = this.value + '$';
        }

        // Area Range
        const areaRange = document.getElementById('areaRange');
        const minAreaValue = document.getElementById('minAreaValue');
        const maxAreaValue = document.getElementById('maxAreaValue');
        minAreaValue.textContent = areaRange.min + 'm²';
        maxAreaValue.textContent = areaRange.max + 'm²';
        areaRange.oninput = function() {
            minAreaValue.textContent = this.value + 'm²';
        }
        areaRange.oninput = function() {
            maxAreaValue.textContent = this.value + 'm²';
        }

        // Update Area Range
        function updateAreaRange(value) {
            minAreaValue.textContent = value + 'm²';
        }

        // Dropdowns
        
    </script>
   
    <script>
        document.querySelector('form').addEventListener('submit', function(event) {
             const uncheckedCheckboxes = this.querySelectorAll('input[type="checkbox"]:not(:checked)');
            uncheckedCheckboxes.forEach(checkbox => checkbox.disabled = true);

            const inputs = this.querySelectorAll('input');
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    input.disabled = true;
                }
            });
        });
    </script>

@endpush