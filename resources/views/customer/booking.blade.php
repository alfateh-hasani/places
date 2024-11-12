@extends('layouts.master')
@push('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@24.6.0/build/css/intlTelInput.css" />
<link rel="stylesheet" href="{{asset('assets/plugin/HoldOn.min.css')}}" />

@endpush
@section('content')

<section class="profile py-5 lg:py-16 bg-[#eff3f6] min-h-screen lg:min-h-min">
    <div class="container">
        <div class="lg:grid lg:grid-cols-4 lg:gap-6 w-full mx-0">
             @include('customer.section.sidebar')
            <div class="col-span-3">
                 @include('customer.section.header')
                <div class="bg-white py-8 px-6 rounded-2xl mt-5">
                    <div class="mb-6">
                    
                            <svg class="inline-block" xmlns="http://www.w3.org/2000/svg" width="21" height="21" viewBox="0 0 21 21">
                                <g id="event" transform="translate(-1.5 -1.5)">
                                    <rect id="Rectangle_19429" data-name="Rectangle 19429" width="20" height="18" rx="4" transform="translate(2 4)" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="1"/>
                                    <path id="Path_4484" data-name="Path 4484" d="M2,8A4,4,0,0,1,6,4H18a4,4,0,0,1,4,4V9H2Z" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="1"/>
                                    <path id="Path_4485" data-name="Path 4485" d="M6,2V6M18,2V6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="1"/>
                                    <path id="Path_4486" data-name="Path 4486" d="M12,12l1.458,1.994,2.347.77-1.446,2-.007,2.47L12,18.48l-2.351.756-.007-2.47-1.446-2,2.347-.77Z" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="1"/>
                                </g>
                            </svg>
                        
                        <p class="inline-block ml-4">{{__('site.total_reservations')}} <span class="text-price font-semibold">({{$total_bookings}})</span></p>
                    </div>
                    <p class="mb-4">
                        {{__('customer.upcoming_bookings')}}
                    </p>
                    @if ($upcoming_bookings->isNotEmpty())
                        @foreach ($upcoming_bookings as $item) 
                            @include('customer.section.booking-card',['item'=>$item])
                        @endforeach
                    @else
                        <p class="text-center">{{__('customer.no_upcoming_bookings')}}</p>
                    @endif
                  
                 
                    <p class="mb-4">{{__('customer.past_bookings')}}</p>
                    @if ($past_bookings->isNotEmpty())
                        @foreach ($past_bookings as $item) 
                            @include('customer.section.booking-card',['item'=>$item])
                        @endforeach
                        @else
                        <p class="text-center">{{__('customer.no_past_bookings')}}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>


@endsection
