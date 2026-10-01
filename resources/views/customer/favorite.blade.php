@extends('layouts.master')
@push('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@24.6.0/build/css/intlTelInput.css" />
<link rel="stylesheet" href="{{asset('assets/plugin/HoldOn.min.css')}}" />

<style>
    /* Scope the dark-panel override to the customer dashboard so it doesn't leak
       to the site header/footer/modals rendered on this page. */
    .profile .bg-white {
        background-color: #0f0c0c;
    }
</style>
@endpush
@section('content')

<section class="profile py-5 lg:py-16 text-white min-h-screen lg:min-h-min">
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
                        <h1 class="inline-block ml-4">{{__('apartment.favorite_list')}} <span class="text-price font-semibold">(<span data-wishlist-count>{{$total_favorites}}</span>)</span></h1>

                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-6 max-w-full" data-wishlist-grid>
                        @foreach ($favorites as $item)
                            @include('customer.section.favorite-card', ['apartment' => $item])
                        @endforeach
                        <div class="col-span-full flex flex-col items-center justify-center text-center py-14 {{ $favorites->isNotEmpty() ? 'hidden' : '' }}" data-wishlist-empty>
                            <svg class="w-14 h-14 mb-4 text-reviews" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 20.5 4.2 12.9a4.5 4.5 0 0 1 6.36-6.37L12 7.97l1.44-1.44a4.5 4.5 0 1 1 6.36 6.37L12 20.5Z" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <h3 class="font-semibold text-lg text-white mb-2">{{ __('customer.no_favorites') }}</h3>
                            <p class="font-normal text-sm text-gray-400 max-w-xs mb-5">{{ __('customer.no_favorites_hint') }}</p>
                            <a href="{{ route('apartments.index') }}" class="inline-flex items-center justify-center bg-price text-white font-semibold text-sm rounded-xl px-6 py-2.5 hover:bg-black ease-in-out duration-300">
                                {{ __('customer.browse_units') }}
                            </a>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</section>


@endsection
