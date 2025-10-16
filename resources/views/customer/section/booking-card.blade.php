<div class="bg-feature border border-feature-border rounded-xl p-4 relative mb-4 reservations">
    <a href="{{route('customer.booking.details',$item->number_of_booking)}}" >
        @if($item->apartment)
            <img class="mb-4 xl:mb-0 ltr:float-left rtl:float-right w-full xl:w-56 h-44 rounded-xl me-4 object-cover" 
                src="{{getImage($item->apartment,'image')}}" />
        @endif
    </a>
    <div style="    min-width: 30%;" class="ltr:float-right rtl:float-left border-s border-feature-border px-3 py-1">
        <a  href="{{route('customer.booking.details',$item->number_of_booking)}}" >
            <p class="text-sm sm:text-base font-semibold text-lg mb-1">{{__('apartment.booking_summary')}}</p>
        </a>
        <a><p class="text-sm sm:text-sm text-reviews "> {{__('apartment.night_price')}} 
            <span class="leading-none block font-semibold text-black">{{$item->price_per_night}} SAR</span></p></a>
        <a><p class="text-sm sm:text-sm text-reviews "> {{__('apartment.discount')}} 
            <span class="leading-none block font-semibold text-black">{{$item->discount}} SAR</span></p></a>
        <a><p class="text-sm sm:text-sm text-reviews ">{{__('apartment.total_price')}} ({{$item->number_of_nights .' '.__('apartment.nights')}})
            <span class="leading-none block font-semibold text-black mb-2">{{$item->final_price}} SAR</span></p></a>
    </div>
    <div>
        <a>
            <p class="text-sm sm:text-base font-semibold text-lg pt-2">
                {{
                    $item->apartment?->{'name_' . app()->getLocale()}
                    .' - '. 
                    $item->apartment?->building?->{'name_' . app()->getLocale()}
                    .' - ( '.
                    $item->apartment?->unit_number.' )'
                }}
            </p>
        </a>
        
        <a><p class="text-sm sm:text-base text-reviews py-2">  {{__('apartment.reservations_number')}} : 
            <span class="text-black">#{{$item->number_of_booking}}</span></p></a>
        <a><p class="text-sm sm:text-base text-reviews py-2"> {{__('apartment.reservations_status')}} : 
            @php
                $statusColor = match($item->status) {
                    'pending' => '#FFC107', 
                    'approved' => '#10C13F',  
                    'rejected' => '#FF0000',  
                    'booked' => '#1E90FF',   
                    'finished' => '#6C757D' ,
                    'canceled' => '#DC3545',  
                    default => '#000000',     
                };
            @endphp
        
        <span class="text-[{{ $statusColor }}]">{{ __('api.booking_status_' . $item->status) }}</span>
                <a><p class="text-sm sm:text-base text-reviews py-2">  {{__('apartment.reservations_date')}} :
            <span class="text-black">{{$item->check_in?->format('Y-m-d')}}</span></p></a>
    </div>
    {{-- <a class="options-button cursor-pointer z-50 absolute ltr:right-4 rtl:left-4 top-4">
        <svg width="20" height="20" version="1.1" id="fi_512142" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 426.667 426.667" style="enable-background:new 0 0 426.667 426.667;" xml:space="preserve">
            <circle cx="42.667" cy="213.333" r="42.667"></circle>
            <circle cx="213.333" cy="213.333" r="42.667"></circle>
            <circle cx="384" cy="213.333" r="42.667"></circle>
        </svg>
    </a>
    <ul class="absolute ltr:right-1 rtl:left-1 top-9 bg-white p-3 rounded-lg">
        <li><a class="block p-1 border-b border-border text-sm">Option 1</a></li>
        <li><a class="block p-1 border-b border-border text-sm">Option 2</a></li>
        <li><a class="block p-1 border-b border-border text-sm">Option 3</a></li>
    </ul> --}}
    <div class="clear-both"></div>
    
</div>