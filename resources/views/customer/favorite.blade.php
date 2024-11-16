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
                        <svg class="inline-block" xmlns="http://www.w3.org/2000/svg" width="24.13" height="21.2" viewBox="0 0 24.13 21.2">
                            <path id="Icon_feather-heart" data-name="Icon feather-heart" d="M23.485,6.265a6.033,6.033,0,0,0-8.535,0L13.788,7.428,12.625,6.265A6.035,6.035,0,1,0,4.091,14.8l1.163,1.163L13.788,24.5l8.535-8.535L23.485,14.8a6.033,6.033,0,0,0,0-8.535Z" transform="translate(-1.723 -3.897)" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2"/>
                        </svg>
                        <p class="inline-block ml-4">{{__('apartment.favorite_list')}} <span class="text-price font-semibold">({{$total_favorites}})</span></p>

                    </div>
                  
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-6 max-w-full">
                        @forelse ($favorites as $item)
                            @include('customer.section.favorite-card', ['apartment' => $item])
                        @empty
                            <p class="text-center text-gray-500 col-span-full">
                                {{ __('customer.no_favorites') }}
                            </p>
                        @endforelse
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</section>


@endsection
