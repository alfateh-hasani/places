<div class="bg-feature border border-feature-border rounded-xl p-4 relative mb-4">
    <a href="{{route('customer.booking.details',$item->number_of_booking)}}" >
        <img class="mb-4 xl:mb-0 float-left rtl:float-right rtl:ml-4 w-full xl:w-56 h-44 rounded-xl mr-4 object-cover" 
        src="{{getImage($item->apartment,'image')}}" />
    </a>
    <div class="ltr:float-right rtl:float-left border-l border-feature-border ltr:pl-3 rtl:pm-3 py-1">
        <a  href="{{route('customer.booking.details',$item->number_of_booking)}}" >
            <p class="text-sm sm:text-base font-semibold text-lg mb-4">{{__('apartment.booking_summary')}}</p>
        </a>
        <a><p class="text-sm sm:text-base text-reviews "> {{__('apartment.night_price')}} 
            <span class="block font-semibold text-black mb-2">{{$item->price_per_night}} SAR</span></p></a>
        <a><p class="text-sm sm:text-base text-reviews "> {{__('apartment.discount')}} 
            <span class="block font-semibold text-black mb-2">{{$item->discount}} SAR</span></p></a>
        <a><p class="text-sm sm:text-base text-reviews ">{{__('apartment.total_price')}} ({{$item->number_of_nights .' '.__('apartment.nights')}})<span class="block font-semibold text-black mb-2">{{$item->final_price}} SAR</span></p></a>
    </div>
    <div>
        <a><p class="text-sm sm:text-base font-semibold text-lg pt-2">{{$item->apartment?->{'name_' . app()->getLocale()} }}</p></a>
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
            <span class="text-black">{{$item->check_in}}</span></p></a>
    </div>
    <svg class="absolute ltr:right-4 rtl:left-4 top-4" width="20" height="20" version="1.1" id="fi_512142" 
    xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" 
    viewBox="0 0 426.667 426.667" style="enable-background:new 0 0 426.667 426.667;" xml:space="preserve">
        <circle cx="42.667" cy="213.333" r="42.667"></circle>
        <circle cx="213.333" cy="213.333" r="42.667"></circle>
        <circle cx="384" cy="213.333" r="42.667"></circle>
    </svg>
    <div class="clear-both"></div>
</div>