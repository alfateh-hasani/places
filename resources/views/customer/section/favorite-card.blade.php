<div class="rounded-xl overflow-hidden border border-border">
    <div class="relative">
        <div class="slider">
            @foreach ($apartment->getMedia('image') as $image)
                <a href="{{$apartment->link}}">
                    <img 
                        class="object-cover w-full" 
                        src="{{ $image->getUrl('grid') }}" 
                        alt="@lang('apartment.apartment_name_default')"
                    />
                </a>
            @endforeach
        </div>

        <button 
            class="absolute w-6 h-5 top-4 left-4 rtl:right-4 rtl:left-auto bg-contain favorite favorite-active ease-in-out duration-300">
         
        </button>
    </div>

    <a href="{{$apartment->link}}" class="pt-3 px-4 pb-4 block">
        <div class="flex items-center">
            <img src="{{ asset('assets/img/star.svg') }}" class="mr-2 rtl:ml-2 rtl:mr-0 h-4" />
            <p class="font-normal text-xs text-reviews">
                {{ $apartment->total_ratings }} ({{ $apartment->reviews->count() }}) @lang('apartment.reviews')
            </p>
        </div>

        <h3 class="font-semibold text-sm text-title my-2">
            {{ $apartment->ml('name')   }}
        </h3>
        <h4 class="font-normal text-xs text-reviews">
            {{ $apartment->building->address }}
        </h4>

        <ul class="my-2.5 flex gap-2 flex-wrap">
            <li 
                class="bg-feature border border-feature-border py-1 px-4 rounded-xl font-normal text-xs text-title hover:bg-feature-border ease-in-out duration-300 flex items-center">
                <img 
                    class="h-[14px] mr-2 rtl:ml-2 rtl:mr-0" 
                    src="{{ asset('assets/img/feature-1.svg') }}" 
                />
                {{ $apartment->num_rooms }}  
            </li>
            <li 
                class="bg-feature border border-feature-border py-1 px-4 rounded-xl font-normal text-xs text-title hover:bg-feature-border ease-in-out duration-300 flex items-center">
                <img 
                    class="h-[14px] mr-2 rtl:ml-2 rtl:mr-0" 
                    src="{{ asset('assets/img/feature-2.svg') }}" 
                />
                {{ $apartment->num_beds }}  
            </li>
            <li 
                class="bg-feature border border-feature-border py-1 px-4 rounded-xl font-normal text-xs text-title hover:bg-feature-border ease-in-out duration-300 flex items-center">
                <img 
                    class="h-[14px] mr-2 rtl:ml-2 rtl:mr-0" 
                    src="{{ asset('assets/img/feature-3.svg') }}" 
                />
                {{ $apartment->area }} @lang('apartment.area')
            </li>
        </ul>

        <div class="flex items-center">
            
            <p class="font-bold text-sm text-price">
                {{ $apartment->price }} <span class="currency"> @lang('apartment.currency')</span> /
            </p>

            <p class="font-normal text-sm text-reviews ml-1 rtl:mr-1 rtl:ml-0">@lang('apartment.night')</p>
        </div>
    </a>
</div>
