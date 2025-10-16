<section class="properitirs pt-10 pb-10 md:pt-20 md:pb-20   bg-properits">
    <div class="container">
  
        <h3 class="font-semibold text-base md:text-3xl text-black mt-1 md:mt-3 mb-5 md:mb-0 text-center md:text-left rtl:md:text-right">
            @lang('site.explore_more_properties')
        </h3>


       
            <div class="category relative pt-16 md:pt-24">
                 @foreach ($buildings as $city)
                <div class="slider px-5 -mx-5" data-title="{{ $city->ml('name') }}">
                    @foreach ($city->buildings as $building)
                        <a href="{{route('buliding.show',$building->slug)}}" class="relative block px-1 overflow-hidden">
                            <img
                                src="{{ $building->image_grid }}"
                                class="h-[250px] md:h-[440px] w-full object-cover rounded-xl overflow-hidden"
                                alt="{{ $building->ml('name') }}"
                            />
                            <div class="gradient absolute left-1 top-0 right-1 bottom-0 z-10 rounded-xl overflow-hidden"></div>
                            <h3 class="absolute left-4 sm:left-6 right-4 sm:right-6 z-20 font-normal text-lg text-white mb-5">
                                {{ $building->ml('name') }}
                            </h3>
                            <!-- <ul class="  max-w-full grid grid-cols-3 bg-white bg-opacity-20 py-4 px-1 rounded-md text-center absolute z-20 mx-0">
                                <li class="font-normal text-xs sm:text-sm text-white">
                                    <img class="h-[15px] sm:h-[19px] -translate-y-0.5" src="{{ asset('assets/img/pin.svg') }}" />
                                    {{ $city->ml('name') }}
                                </li>
                                <li class="font-normal text-xs sm:text-sm text-white">
                                    <img class="h-[15px] sm:h-[19px] -translate-y-0.5" src="{{ asset('assets/img/apartment.svg') }}" />
                                    {{ $building->apartments->count() }} @lang('site.properties')
                                </li>
                                <li class="font-normal text-xs sm:text-sm text-white">
                                    <img class="h-[15px] sm:h-[19px] -translate-y-0.5" src="{{ asset('assets/img/fire.svg') }}" />
                                    @lang('site.top_rated')
                                </li>
                            </ul> -->
                        </a>
                    @endforeach
                </div>
                 @endforeach
            </div>
       
    </div>
</section>
