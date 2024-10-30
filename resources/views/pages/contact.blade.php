@extends('layouts.master')

@section('content')
@include('pages.partials.breadcrumb')

<section class="py-12 bg-footer">
    <div class="container">
        <div class="lg:grid lg:grid-cols-2 lg:gap-4 w-full mx-0">
            <div class="pr-0 xl:pr-24">
                <p class="font-normal text-base text-price mb-5">
                    {{$page->{'title_'.app()->getLocale()} ?? ''}}
                </p>
                <p class="font-semibold text-3xl sm:text-5xl mb-6">
                    {!!$page->{'content_'.app()->getLocale()} ?? ''!!}
                </p>
                <p class="font-light text-base text-gri mb-12"> 
                   
                </p>
                <ul>
                    <li class="mb-9"><a><div class="w-12 h-12 rounded-full mr-6 bg-[#fae3dd] text-center pt-3 float-left translate-y-1"><img class="inline-block" src="{{asset('assets/img/mail.svg')}}" /></div> <p class="font-semibold text-lg text-black">Send Us Eamil <br> info@maham.com</p></a></li>
                    <li class="mb-9"><a><div class="w-12 h-12 rounded-full mr-6 bg-[#fae3dd] text-center pt-3 float-left translate-y-1"><img class="inline-block" src="{{asset('assets/img/tel.svg')}}" /></div> <p class="font-semibold text-lg text-black">Send Us Eamil <br> info@maham.com</p></a></li>
                    <li class="mb-9"><a><div class="w-12 h-12 rounded-full mr-6 bg-[#fae3dd] text-center pt-3 float-left translate-y-1"><img class="inline-block" src="{{asset('assets/img/address.svg')}}" /></div> <p class="font-semibold text-lg text-black">Send Us Eamil <br> info@maham.com</p></a></li>
                </ul>
            </div>
            <div class="bg-white border border-border rounded-2xl p-7 sm:p-12">
                <p class="font-semibold text-3xl text-black mb-8">
                    {{__('site.contact_us')}}
                </p>
                <form id="contact-us">
                    @csrf
                    <div class="lg:grid lg:grid-cols-2 lg:gap-4 w-full mx-0">
                        <input class="w-full mb-4 border border-border bg-footer rounded-lg h-12 px-4" type="name" placeholder="{{__('site.name')}}" />
                        <input class="w-full mb-4 border border-border bg-footer rounded-lg h-12 px-4" type="phone" placeholder="{{__('site.phone')}}" />
                    </div>
                    <input class="w-full mb-4 border border-border bg-footer rounded-lg h-12 px-4" type="email" placeholder="{{__('site.email')}}" />
                    <textarea class="w-full mb-4 border border-border bg-footer rounded-lg h-12 h-52 px-4 pt-4 resize-none" placeholder="{{__('site.message')}}"></textarea>
                    <button class="bg-price py-4 px-16 font-normal text-sm text-white rounded-full">
                        {{__('site.send')}}
                        <img class="w-3 inline-block ml-3" src="{{asset('assets/img/slider-right.svg')}}" /></button>
                </form>
            </div>
        </div>
    </div>
</section> 

<section class="py-12 container">
    <p class="font-semibold text-2xl mb-10">Where To Find Us</p>
    <script type="text/javascript">
        $(function() {
            var haritaIconu = "assets/img/map.png",
                Yerler = [{
                    lat: "36.791499",
                    lon: "34.623243",
                    zoom: 14,
                    icon: haritaIconu,
                    animation: google.maps.Animation.DROP
                }, ];
            new Maplace({
                locations: Yerler,
                map_div: '#map',
                generate_controls: false,
                styles: {
                    'PLACES': haritaRengi
                },
            }).Load();
        });
    </script>
    <div class="border border-border rounded-xl p-4">
        <div class="h-52 lg:h-96 rounded-xl overflow-hidden" id="map"></div>
    </div>
</section>

@endsection

@push('js')
    
@endpush