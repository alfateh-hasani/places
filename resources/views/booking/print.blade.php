@extends('layouts.master')
@push('css')


<style>
     .cancel-booking-btn[disabled] {
        background-color: #f5d7d1;  
        color: #a8a8a8;  
        cursor: not-allowed; 
        opacity: 0.6;  
    }

    @media print {
   body{
    font-size: 14px;
   }
    .no-print {
        display: none;
    }

    header , footer{
        display: none;
    }

    .container{
        width: 100% !important;  
        max-width: 100% !important;
    }
}

</style>
@endpush
@section('content')
 
<section class="">
    <div class="container">
   
        <div class=" ">
            <div class="  pb-2 mb-2">
                <p class="font-semibold text-lg   text-center py-2.5">
                  Booking ID:   #{{$booking->number_of_booking }} -     {{$booking->apartment->{'name_'.app()->getLocale()} }}
                </p>
               
                <div class="clear-both"></div>
            </div>
            <div class="grid lg:grid-cols-5 gap-6 max-w-full">
                <div class="col-span-3">
                    <img src="{{getImage($booking->apartment,'image')}}" class="w-full h-60 object-cover rounded-lg mb-4" />
                    <ul>
                         
                        <li class="bg-feature border border-feature-border mb-4 rounded-lg p-4">
                            <p class="text-gri float-left rtl:float-right">{{__('booking.status')}} :</p>
                            <p class="float-right rtl:float-left text-[#10C13F]">{{__('booking.status_'.$booking->status )}}</p>
                            <div class="clear-both"></div>
                        </li>
                        
                        <li class="bg-feature border border-feature-border mb-4 rounded-lg p-4">
                            <p class="text-gri float-left rtl:float-right">  {{__('booking.check_in')}} | {{__('booking.check_out')}} :</p>
                            <p class="float-right rtl:float-left">{{$booking->check_in?->format('y-m-d')}} | {{$booking->check_out?->format('y-m-d')}}</p><div class="clear-both"></div></li>
                    
                          
                    </ul>
                </div>
                <div class="bg-footer border border-feature-border rounded-lg py-5 col-span-3">
                    <div class="border-b border-feature-border pb-5 mb-5 px-5">
                        <p class="float-left rtl:float-right font-semibold">   {{__('booking.info')}} </p>
                        <svg class="float-right rtl:float-left text-price" xmlns="http://www.w3.org/2000/svg" width="21" height="21" viewBox="0 0 21 21">
                            <g id="event" transform="translate(-1.5 -1.5)">
                                <rect id="Rectangle_19429" data-name="Rectangle 19429" width="20" height="18" rx="4" transform="translate(2 4)" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="1"/>
                                <path id="Path_4484" data-name="Path 4484" d="M2,8A4,4,0,0,1,6,4H18a4,4,0,0,1,4,4V9H2Z" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="1"/>
                                <path id="Path_4485" data-name="Path 4485" d="M6,2V6M18,2V6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="1"/>
                                <path id="Path_4486" data-name="Path 4486" d="M12,12l1.458,1.994,2.347.77-1.446,2-.007,2.47L12,18.48l-2.351.756-.007-2.47-1.446-2,2.347-.77Z" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="1"/>
                            </g>
                        </svg>
                        <div class="clear-both"></div>
                    </div>
                    <div class="bg-feature border border-feature-border rounded-lg mx-5 my-3 px-4 py-3">
                        <p class="float-left rtl:float-right text-xs">{{__('booking.check_in_time')}} <span class="block font-semibold text-sm">12:00 PM</span></p>
                        <p class="float-right rtl:float-left text-xs w-2/4 border-l border-feature-border text-right">{{__('booking.check_out_time')}}  <span class="block font-semibold text-sm">12:00 PM</span></p>
                        <div class="clear-both"></div>
                    </div>
               
 
                    <p class="font-semibold py-4 mx-5">
                        {{__('booking.summary')}}
                    </p>
                    <p class="text-title mx-5">  {{__('booking.night_price')}} <span class="float-right rtl:float-left font-semibold">{{$booking->total_price/$booking->number_of_nights }} SAR</span></p>
                    @if($booking->coupon_code != null)
                        <p class="text-title mx-5">  {{__('booking.copon').' ( ' .$booking->coupon_code.' ) '}} <span class="float-right rtl:float-left font-semibold">{{$booking->discount}} SAR</span></p>
                    @endif
                    <div class="bg-feature border border-feature-border rounded-lg mx-5 mt-4 p-3">
                        <p>         {{__('booking.summary')}} (    {{$booking->number_of_nights   .' '.__('booking.nights')}})</p>
                        <p class="font-semibold text-lg">
                            {{$booking->final_price}}
                            SAR</p>
                    </div>
                </div>
            </div>
        </div>
    </div>


</section>

<div class="popup modal" id="popup-1" style="display: none;">
    <div class="popup-contain text-center">
        <p class="p-5 border-b border-border text-left">
            {{__('booking.booking_success')}}
        </p>
        <img class="inline-block my-10" src="{{asset('assets/img/success.svg')}}" />
        <p class="font-semibold">
        
            {{__('booking.booking_confirmed_message')}}
            <br>
            {{__('booking.number_of_booking')}}: <span class="text-price" dir="ltr">#{{ $booking->number_of_booking }}</span>
        </p>
        <div class="md:grid md:grid-cols-2 md:gap-5 max-w-full my-10 mx-5">
            <a href="{{ route('customer.booking.details', $booking->number_of_booking) }}"  class="py-4 rounded-lg bg-price text-white">
                {{__('booking.view_booking')}}
            </a>
            <button id="closeMe"  class="py-4 rounded-lg bg-feature">
                {{__('booking.close')}}
            </button>
        </div>
    </div>
</div>

 
@endsection
 

@push('js')
 
<script>
 window.print()

</script>


@endpush


 