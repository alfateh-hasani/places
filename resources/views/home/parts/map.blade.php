<section class="map-section py-10 md:py-20 bg-gray-50">
    <div class="container">
        <div class="text-center mb-8 md:mb-12">
            <h2 class="font-bold text-primary text-2xl md:text-4xl   mb-4">
                @lang('site.our_locations')
            </h2>
            <p class=" text-primary text-lg md:text-xl max-w-3xl mx-auto">
                @lang('site.explore_our_buildings_map')
            </p>
        </div>

        <div class="relative">
            <!-- شريط البحث -->
            <div class="mb-6">
                <div class="max-w-md mx-auto">
                    <div class="relative">
                        <input type="text" 
                               id="mapSearch" 
                               placeholder="@lang('site.search_buildings')" 
                               class="w-full px-4 py-3 pl-10 pr-4 text-gray-700 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <button id="clearSearch" 
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 hidden">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- خريطة Google Maps -->
            <div id="buildingsMap" class="w-full h-96 md:h-[500px] rounded-xl shadow-lg overflow-hidden"></div>
            
           
        </div>
    </div>
</section>

@push('css')
<style>
    .map-section {
        background: #0f0f0f;
    }
    
    .bg-primary {
        background-color: #f7bb8e;
    }
    
    .text-primary {
        color: #f7bb8e;
    }
    
    .hover\:text-primary-dark:hover {
        color: #e6a67a;
    }
    
    #buildingsMap {
        border: 2px solid #f7bb8e;
    }
    
    .gm-style-iw {
        border-radius: 8px !important;
    }
    
    .gm-style-iw-d {
        overflow: hidden !important;
    }
</style>
@endpush

@push('js')
<script>
    // تمرير بيانات المباني للـ JavaScript
    window.buildingsData = @json($mapBuildings);
    window.langProperties = '@lang("site.properties")';
    window.langViewDetails = '@lang("site.view_details")';
</script>
<script src="{{ asset('assets/js/map.js') }}"></script>
@endpush
