@extends('layouts.master')
@push('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@24.6.0/build/css/intlTelInput.css" />
<link rel="stylesheet" href="{{asset('assets/plugin/HoldOn.min.css')}}" />

<style>
    .bg-white {
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
                        <svg class="inline-block" xmlns="http://www.w3.org/2000/svg" width="17.997" height="19.86" viewBox="0 0 17.997 19.86">
                            <g id="Icon_feather-bell" data-name="Icon feather-bell" transform="translate(-3.9 -2.4)">
                                <path id="Path_4319" data-name="Path 4319" d="M18.5,8.6a5.6,5.6,0,1,0-11.2,0c0,6.532-2.8,8.4-2.8,8.4H21.3s-2.8-1.866-2.8-8.4" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2"/>
                                <path id="Path_4320" data-name="Path 4320" d="M18.634,31.5a1.866,1.866,0,0,1-3.229,0" transform="translate(-4.121 -10.77)" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2"/>
                            </g>
                        </svg>
                        <p class="inline-block ml-4">{{__('apartment.notifications_list')}} <span class="text-price font-semibold">({{$total_notifications}})</span></p>

                    </div>
                    <ul>
                        <li>
                            <a class="border border-border rounded-xl p-6 mb-2 block">
                                <button class="float-right"><img src="assets/img/cancel.svg" /></button>
                                <img class="float-left mr-3" src="assets/img/notification-profile.svg" />
                                <p class="text-sm">Please confirm your email address by clicking on the link we just emailed you</p>
                                <p class="text-xs text-reviews">February 27, 2019</p>
                                <div class="clear-both"></div>
                            </a>
                        </li>
                        <li>
                            <a class="border border-border rounded-xl p-6 mb-2 block">
                                <button class="float-right"><img src="assets/img/cancel.svg" /></button>
                                <img class="float-left mr-3" src="assets/img/notification-profile.svg" />
                                <p class="text-sm">Please confirm your email address by clicking on the link we just emailed you</p>
                                <p class="text-xs text-reviews">February 27, 2019</p>
                                <div class="clear-both"></div>
                            </a>
                        </li>
                        <li>
                            <a class="border border-border rounded-xl p-6 mb-2 block">
                                <button class="float-right"><img src="assets/img/cancel.svg" /></button>
                                <img class="float-left mr-3" src="assets/img/notification-profile.svg" />
                                <p class="text-sm">Please confirm your email address by clicking on the link we just emailed you</p>
                                <p class="text-xs text-reviews">February 27, 2019</p>
                                <div class="clear-both"></div>
                            </a>
                        </li>
                        <li>
                            <a class="border border-border rounded-xl p-6 mb-2 block">
                                <button class="float-right"><img src="assets/img/cancel.svg" /></button>
                                <img class="float-left mr-3" src="assets/img/notification-profile.svg" />
                                <p class="text-sm">Please confirm your email address by clicking on the link we just emailed you</p>
                                <p class="text-xs text-reviews">February 27, 2019</p>
                                <div class="clear-both"></div>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>


@endsection
