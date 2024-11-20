@extends('layouts.master')
@push('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <style>
        iframe{
            width: 100%;
            height: 100% !important;
        }
        .flatpickr-day {
            width: 30px;  
            height: 30px; 
            line-height: 30px;  
            margin:3px  ;
        }
        span.flatpickr-day.selected{
            background: #EF552C !important;
            color: #fff !important;
            border-color: #EF552C !important;
        }
    </style>

        
@endpush
@section('content')

<section class="detail"> 
    <div class="py-0 lg:py-10 container relative">
        <h1 class="rtl:float-right float-left font-semibold text-2xl hidden lg:block text-title">
            {{ $apartment->ml('name') }}
        </h1>
        <div class="rtl:float-left float-right absolute lg:relative z-10 right-3 lg:right-0 top-4 lg:top-0">
            <button data-src="#popup-share" data-fancybox dont-close-click-outside class="bg-blackopacity inline-block py-1 ml-1 lg:py-2 px-0 w-8 h-8 lg:w-auto lg:h-auto lg:px-4 bg-sort rounded-full text-center lg:rounded-md hover:bg-filteritem ease-in-out duration-300">
                <img src="{{ asset('assets/img/share.svg') }}" class="inline-block rtl:ml-0 rtl:lg:ml-2 mr-0 lg:mr-2 h-4" />
                <span class="hidden lg:inline">
                    {{ __('apartment.share') }}
                </span>
            </button>
            
            <a href="javascript:void(0);" onclick="toggleFavorite({{ $apartment->id }})"
                class="bg-blackopacity inline-block py-1 ml-1 lg:py-2 px-0 w-8 h-8 lg:w-auto 
                lg:h-auto lg:px-4 bg-sort rounded-full text-center lg:rounded-md hover:bg-filteritem ease-in-out duration-300">
                @if (!$apartment->is_favorite)
                    <img id="favorite-icon-{{ $apartment->id }}" src="{{ asset('assets/img/favoritee.svg') }}" class="inline-block rtl:ml-0 rtl:lg:ml-2 mr-0 lg:mr-2 h-4" />
                @else
                    <img id="favorite-icon-{{ $apartment->id }}" src="{{ asset('assets/img/favorite-active.svg') }}" class="inline-block rtl:ml-0 rtl:lg:ml-2 mr-0 lg:mr-2 h-4" />
                @endif
                <span class="hidden lg:inline">
                    {{ __('apartment.favorite') }}
                </span>
            </a>
            
            
        </div>
        <div class="lg:hidden absolute z-10 left-3 top-4">
            <a class="inline-block py-1 ml-1 px-0 w-8 h-8 bg-sort rounded-full text-center hover:bg-filteritem ease-in-out duration-300">
                <img src="{{asset('assets/img/back-arrow.svg') }}" class="inline-block rtl:ml-0 rtl:lg:ml-2 mr-0 lg:mr-2 h-3" />
            </a>
        </div>
        <div class="clear-both"></div>
    </div>
    <div class="container relative">
        <div class="hidden sm:grid photos grid-cols-4 gap-4 max-w-full rounded-xl overflow-hidden h-[256px] xl:h-[456px] banner-side ease-in-out duration-300">
            
            @if ($apartment->getMedia('image'))
                @foreach ($apartment->getMedia('image') as $key=> $photo)
                    @if($key==0)
                        <div class="col-span-2"><a data-fancybox="banner" href="{{ $photo->getUrl() }}" class="relative block">
                            <img class="w-full h-[256px] xl:h-[456px] object-cover" src="{{ $photo->getUrl() }}" /></a>
                        </div>
                    @endif
                @endforeach
            <div>              
            @foreach ($apartment->getMedia('image') as $key=> $photo)
                        
                @if(!in_array($key , [1,2]) )
                @continue
                @endif
 
                    
                        <a data-fancybox="banner" href="{{ $photo->getUrl() }}" class="relative block mb-4">
                            <img class="w-full h-[120px] xl:h-[220px] object-cover" src="{{ $photo->getUrl() }}" />
                        </a>
 
            
                @endforeach
            </div>

            <div>              
                @foreach ($apartment->getMedia('image') as $key=> $photo)
                            
                    @if(!in_array($key , [3,4]) )
                    @continue
                    @endif
                    <a data-fancybox="banner" href="{{ $photo->getUrl() }}" class="relative block mb-4">
                        <img class="w-full h-[120px] xl:h-[220px] object-cover" src="{{ $photo->getUrl() }}" />
                    </a>
 
                    @endforeach
                </div>


                    
            @foreach ($apartment->getMedia('image') as $key=> $photo)
                        
                @if($key < 5) 
                     @continue
                @endif

                <div> 
    
                    <a data-fancybox="banner" href="{{ $photo->getUrl() }}" class="relative block">
                        <img class="{{$key==0?'w-full h-[256px] xl:h-[456px] object-cover'  :'w-full h-[120px] xl:h-[220px] object-cover'}} " src="{{ $photo->getUrl() }}" />
                    </a>
                </div>
 
            
                        
                
            @endforeach
            
            @endif 
        </div>
        <div class="buttons absolute z-10 right-4 bottom-4 hidden lg:block">
            {{-- <button class="bg-white rounded-md py-2 px-3 shadow-lg ml-2 cursor-pointer video-button"><img class="inline-block mr-2" src="assets/img/video.svg" /> Show All Videos</button> --}}
            <button class="bg-white rounded-md py-2 px-3 shadow-lg ml-2 cursor-pointer photo-button"><img class="inline-block mr-2" src="{{asset('assets/img/photo.svg')}}" /> 
                {{__('apartment.show_all_photos')}}
            </button>
        </div>
    </div>
    <div class="relative">
        <div class="block sm:hidden photos h-[256px] banner-side ease-in-out duration-300">
            <div>
                <a data-fancybox="banner" href="https://www.youtube.com/watch?v=LXb3EKWsInQ" class="relative block video">
                    <button class="absolute"><img class="w-10 ease-in-out duration-300" src="{{asset('assets/img/video-white.svg')}}" /></button>
                    <img class="w-full h-[256px] object-cover" src="{{asset('assets/img/slider.png')}}" />
                </a>
            </div>
            <div><a data-fancybox="banner" href="{{asset('assets/img/slider.png')}}" class="relative block">
                <img class="w-full h-[256px] object-cover" src="{{asset('assets/img/slider.png')}}" /></a></div>
            <div><a data-fancybox="banner" href="{{asset('assets/img/slider.png')}}" class="relative block"><img class="w-full h-[256px] object-cover" src="{{asset('assets/img/slider.png')}}" /></a></div>
            <div><a data-fancybox="banner" href="{{asset('assets/img/slider.png')}}" class="relative block"><img class="w-full h-[256px] object-cover" src="{{asset('assets/img/slider.png')}}" /></a></div>
            <div><a data-fancybox="banner" href="{{asset('assets/img/slider.png')}}" class="relative block"><img class="w-full h-[256px] object-cover" src="{{asset('assets/img/slider.png')}}" /></a></div>
            <div><a data-fancybox="banner" href="{{asset('assets/img/slider.png')}}" class="relative block"><img class="w-full h-[256px] object-cover" src="{{asset('assets/img/slider.png')}}" /></a></div>
            <div><a data-fancybox="banner" href="{{asset('assets/img/slider.png')}}" class="relative block"><img class="w-full h-[256px] object-cover" src="{{asset('assets/img/slider.png')}}" /></a></div>
            <div><a data-fancybox="banner" href="{{asset('assets/img/slider.png')}}" class="relative block"><img class="w-full h-[256px] object-cover" src="{{asset('assets/img/slider.png')}}" /></a></div>
        </div>
    </div>
</section>

<section class="location mt-5 lg:mt-8 mb-0 lg:mb-12">
    <div class="container">
        <div class="float-left rtl:float-right rtl:ml-5 mr-5 lg:hidden mb-3 lg:mb-0">
            <h1 class="float-left rtl:float-right font-semibold text-xl text-title">
                {{ $apartment->ml('name') }}
            </h1>
        </div>
        <div class="float-left rtl:float-right rtl:ml-5 mr-5 w-full lg:w-auto mb-3 lg:mb-0">
            <img class="inline-block mr-2 rtl:ml-2 -translate-y-1" src="{{asset('assets/img/location.svg')}}" />
            <p class="inline-block font-normal text-xl text-title">
                {{ $apartment->building->city->ml('name') }}
            </p>
        </div>
        <div class="float-left rtl:float-right mr-5 w-full lg:w-auto mb-3 lg:mb-0">
            <img class="inline-block mr-2 rtl:ml-2 -translate-y-0.5" src="{{asset('assets/img/star.svg')}}" />
            <p class="inline-block font-normal text-base text-reviews">
                {{ $apartment->rating }}
            </p>
        </div>
        <div class="float-left rtl:float-right mr-5 rtl:ml-5 w-full lg:w-auto mb-3 lg:mb-0">
            <img class="inline-block mr-2 rtl:ml-2 -translate-y-0.5" src="{{asset('assets/img/feature-3.svg')}}" />
            <p class="inline-block font-normal text-base text-reviews">  

                {{__('apartment.area'). $apartment->area }} <sup></sup>
            </p>
        </div>
        <p class="float-right rtl:float-left font-normal text-xl text-title w-full lg:w-auto">
            {{__('apartment.adults_count'). ' ( '.$apartment->adults_count}} )  /
            {{__('apartment.children_count'). ' ( '.$apartment->children_count}}) 
        </p>
        <div class="clear-both"></div>
        
    </div>
</section>




<section class="descriptions pb-24">
    <div class="container">
        <div class="xl:flex xl:flex-row">
            <div class="xl:basis-8/12">
                <div class="hidden xl:block py-5 px-6 bg-filterbackground border border-filterborder rounded-xl">
                    <img class="rtl:float-right  rtl:ml-8  float-left mr-8 my-2" src="{{asset('assets/img/logo.svg')}}" />
                    <ul>
                        <li class="inline-block w-4/12 font-semibold text-base text-title mb-2">
                            <img class="inline-block rtl:ml-2 mr-2" src="{{asset('assets/img/feature-ok.svg')}}" /> 
                            {{__('apartment.num_beds'). ' ( '.$apartment->num_beds}} ) </li>
                        <li class="inline-block w-4/12 font-semibold text-base text-title mb-2">
                            <img class="inline-block rtl:ml-2 mr-2" src="{{asset('assets/img/feature-ok.svg')}}" /> 
                            {{__('apartment.floor_number'). ' ( '.$apartment->floor_number .' ) ' . __('apartment.unit_number'). ' ( '.$apartment->unit_number }} )
                        </li>
                        <li class="inline-block w-4/12 font-semibold text-base text-title mb-2">
                            <img class="inline-block rtl:ml-2 mr-2" src="{{asset('assets/img/feature-ok.svg')}}" />
                            {{__('apartment.bathrooms_count'). ' ( '.$apartment->bathrooms_count}} ) 
                        </li>
                        <li class="inline-block w-4/12 font-semibold text-base text-title mb-2">
                            <img class="inline-block rtl:ml-2 mr-2" src="{{asset('assets/img/feature-ok.svg')}}" /> 
                            {{__('apartment.num_rooms'). ' ( '.$apartment->num_rooms }} ) 
                        </li>
                         
                    </ul>
                    <div class="clear-both"></div>
                </div>
                <div class="py-2 xl:py-7 detail-description border-b border-blackopacity mb-8">
                    <h4 class="font-semibold text-xl text-title">   
                        {{__('apartment.description')}}
                    </h4>
                    <p class="font-light text-base text-gri mt-3 mb-2 ease-in-out duration-900 max-h-[92px] overflow-hidden desctext">
                        {{ strip_tags($apartment->ml('description')) }}

                    </p>
                    <button class="showmoreApartment font-normal text-sm text-blue underline">
                        {{__('apartment.show_more')}}    
                    </button> 
                </div>
                <div class="tabs" id="tabs">
                    <ul class="buttons w-[210vw] xl:w-auto">
                        <li class="inline-block">
                            <a class="xl:px-5 xl:py-3 rounded-lg rtl:ml-2 mr-2 block bg-price" href="#tabs-1">
                                <svg class="hidden -translate-y-0.5 xl:inline-block" id="building" xmlns="http://www.w3.org/2000/svg" width="17.371" height="18.707" viewBox="0 0 17.371 18.707">
                                    <path id="Path_1364" data-name="Path 1364" d="M15.354,4H8A2,2,0,0,0,6,6V20.7a2,2,0,0,0,2,2H21.367a2,2,0,0,0,2-2V13.354a2,2,0,0,0-2-2H17.358V6A2,2,0,0,0,15.354,4ZM6.668,20.7V6A1.336,1.336,0,0,1,8,4.668h7.349A1.336,1.336,0,0,1,16.69,6V22.039H14.017V17.7a.334.334,0,0,0-.334-.334H9.675a.334.334,0,0,0-.334.334v4.343H8A1.336,1.336,0,0,1,6.668,20.7Zm3.341,1.336V18.031h3.341v4.009ZM21.367,12.017A1.336,1.336,0,0,1,22.7,13.354V20.7a1.336,1.336,0,0,1-1.336,1.336H17.358V12.017Z" transform="translate(-6 -4)" fill="currentColor"/>
                                    <path id="Path_1365" data-name="Path 1365" d="M12.334,12H13.67A.334.334,0,0,0,14,11.67V10.334A.334.334,0,0,0,13.67,10H12.334a.334.334,0,0,0-.334.334V11.67A.334.334,0,0,0,12.334,12Zm.334-1.336h.668v.668h-.668Zm-.334,4.677H13.67A.334.334,0,0,0,14,15.011V13.675a.334.334,0,0,0-.334-.334H12.334a.334.334,0,0,0-.334.334v1.336A.334.334,0,0,0,12.334,15.345Zm.334-1.336h.668v.668h-.668Zm1,4.677A.334.334,0,0,0,14,18.352V17.015a.334.334,0,0,0-.334-.334H12.334a.334.334,0,0,0-.334.334v1.336a.334.334,0,0,0,.334.334Zm-1-1.336h.668v.668h-.668ZM17.679,12h1.336a.334.334,0,0,0,.334-.334V10.334A.334.334,0,0,0,19.015,10H17.679a.334.334,0,0,0-.334.334V11.67A.334.334,0,0,0,17.679,12Zm.334-1.336h.668v.668h-.668Zm-.334,4.677h1.336a.334.334,0,0,0,.334-.334V13.675a.334.334,0,0,0-.334-.334H17.679a.334.334,0,0,0-.334.334v1.336A.334.334,0,0,0,17.679,15.345Zm.334-1.336h.668v.668h-.668Zm-.334,4.677h1.336a.334.334,0,0,0,.334-.334V17.015a.334.334,0,0,0-.334-.334H17.679a.334.334,0,0,0-.334.334v1.336A.334.334,0,0,0,17.679,18.686Zm.334-1.336h.668v.668h-.668Zm5.679,2h1.336a.334.334,0,0,0,.334-.334V17.683a.334.334,0,0,0-.334-.334H23.692a.334.334,0,0,0-.334.334V19.02A.334.334,0,0,0,23.692,19.354Zm.334-1.336h.668v.668h-.668Zm-.334,4.677h1.336a.334.334,0,0,0,.334-.334V21.024a.334.334,0,0,0-.334-.334H23.692a.334.334,0,0,0-.334.334V22.36A.334.334,0,0,0,23.692,22.694Zm.334-1.336h.668v.668h-.668Z" transform="translate(-9.996 -7.996)" fill="currentColor"/>
                                </svg>
                                <span class="font-semibold">
                                    {{__('apartment.specifications')}}
                                </span>
                            </a>
                        </li>
                        <li class="inline-block">
                            <a class="xl:px-5 xl:py-3 rounded-lg rtl:ml-2 mr-2 block" href="#tabs-2">
                                <svg class="hidden -translate-y-0.5 xl:inline-block" xmlns="http://www.w3.org/2000/svg" width="20.671" height="19.707" viewBox="0 0 20.671 19.707">
                                    <path id="Icon_feather-star" data-name="Icon feather-star" d="M12.836,3l3.039,6.157,6.8.993-4.918,4.79,1.161,6.767-6.078-3.2-6.078,3.2L7.918,14.94,3,10.151l6.8-.993Z" transform="translate(-2.5 -2.5)" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1"/>
                                </svg>
                                <span class="font-semibold">
                                    {{__('apartment.total_reviews').' ( '.$apartment->reviews->count()}} )
                                </span>
                            </a>
                        </li>
                        <li class="inline-block">
                            <a class="xl:px-5 xl:py-3 rounded-lg rtl:ml-2 mr-2 block" href="#tabs-3">
                                <svg class="hidden -translate-y-0.5 xl:inline-block" xmlns="http://www.w3.org/2000/svg" width="16.306" height="19.707" viewBox="0 0 16.306 19.707">
                                    <g id="Icon_feather-map-pin" data-name="Icon feather-map-pin" transform="translate(0.5 0.5)">
                                        <path id="Path_1362" data-name="Path 1362" d="M19.806,9.153c0,5.952-7.653,11.054-7.653,11.054S4.5,15.105,4.5,9.153a7.653,7.653,0,1,1,15.306,0Z" transform="translate(-4.5 -1.5)" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1"/>
                                        <path id="Path_1363" data-name="Path 1363" d="M18.6,13.051A2.551,2.551,0,1,1,16.051,10.5,2.551,2.551,0,0,1,18.6,13.051Z" transform="translate(-8.398 -5.398)" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1"/>
                                    </g>
                                </svg>
                                <span class="font-semibold">
                                    {{__('apartment.location')}}
                                </span>
                            </a>
                        </li>
                        <li class="inline-block">
                            <a class="xl:px-5 xl:py-3 rounded-lg rtl:ml-2 mr-2 block" href="#tabs-4">
                                <svg class="hidden -translate-y-0.5 xl:inline-block" xmlns="http://www.w3.org/2000/svg" width="16.734" height="18" viewBox="0 0 16.734 18">
                                    <path fill="currentColor" d="M40.372,2.813h6.715a.264.264,0,0,0,0-.527H40.372a.264.264,0,0,0,0,.527Zm0,3.164h6.715a.264.264,0,1,0,0-.527H40.372a.264.264,0,0,0,0,.527Zm0-1.582h6.715a.264.264,0,1,0,0-.527H40.372a.264.264,0,0,0,0,.527ZM52.5,3.4l-.373-.373a.791.791,0,0,0-1.119,0l-1.23,1.23V1.318A1.32,1.32,0,0,0,48.458,0H39a1.32,1.32,0,0,0-1.318,1.318V15.012H36.259a.264.264,0,0,0-.264.264v1.406A1.32,1.32,0,0,0,37.313,18h11.18l.029,0a1.32,1.32,0,0,0,1.255-1.317V7.237L52.5,4.515a.792.792,0,0,0,0-1.119ZM37.313,17.473a.792.792,0,0,1-.791-.791V15.539H47.034v1.09a1.364,1.364,0,0,0,.292.844Zm11.936-2.2s0,0,0,0v1.354a.844.844,0,1,1-1.687,0V15.275a.264.264,0,0,0-.264-.264H38.21V1.318A.792.792,0,0,1,39,.527h9.457a.792.792,0,0,1,.791.791V4.781L44.362,9.668h-3.99a.264.264,0,0,0,0,.527h3.463L42.8,11.227l-.019.022H40.372a.264.264,0,0,0,0,.527H42.59l-.352,1.055H40.372a.264.264,0,0,0,0,.527h1.969a.264.264,0,0,0,.057-.006.255.255,0,0,0,.116-.011l1.678-.559.005,0a.263.263,0,0,0,.045-.021l.007,0,.018-.012.006,0,.022-.019.941-.941h1.851a.264.264,0,0,0,0-.527H45.763l3.486-3.486ZM43.1,11.9l.515.515-.773.258Zm1,.258-.746-.746L49.7,5.077h0l.689-.689.746.746Zm8.017-8.017-.617.617-.746-.746.617-.617a.264.264,0,0,1,.373,0l.373.373a.264.264,0,0,1,0,.373Z" transform="translate(-35.995)"/>
                                </svg>
                                <span class="font-semibold">
                                    {{__('apartment.terms_policies')}}
                                </span>
                            </a>
                        </li>
                    </ul>
                    <div class="sections">
                        <div class="pt-8" id="tabs-1">
                            <h5 class="font-semibold text-xl text-filterhover mb-6">
                                {{__('apartment.specifications_title')}}
                            </h5>
                            <ul>
                                @foreach ($apartment->features as $item)
                                    <li class="inline-block mb-6 w-full xl:w-4/12 hover:text-price ease-in-out duration-300 cursor-pointer">
                                        <img class="inline-block rtl:ml-2 mr-2" width="20" height="20" 
                                        src="{{getImage($item,'icon')}}" />
                                        <p class="inline-block ml-4">
                                            {{  $item->{'name_'.app()->getLocale()}  }}
                                        </p>
                                    </li>
                                @endforeach
                                
                                
                            </ul>
                            {{-- <button class="show-specifications font-semibold text-base border border-black rounded-full py-2 px-6">
                                Show All 30 Amenities
                            </button> --}}
                        </div>
                        <div class="pt-8" id="tabs-2">
                            <h5 class="font-semibold text-xl text-filterhover mb-6">
                                {{__('apartment.reviews_title')}}
                            </h5>
                            <ul>
                                @forelse ($apartment->reviews as $item)
                                    <li class="bg-sort border border-filteritem rounded-lg p-5 mb-4">
                                        <div>
                                            <div class="w-10 h-10 rounded-full rtl:ml-4 mr-4 float-left rtl:float-right inline-block" 
                                                 style="background-image: url({{asset('assets/img/slider.png')}}"></div>
                                            <h5 class="font-normal text-base">  
                                                {{$item->customer->first_name.' '.$item->customer->last_name}}
                                            </h5>
                                            <p class="font-normal text-xs text-filterhover"></p>
                                        </div>
                                        <div class="my-3">
                                            @for ($i = 0; $i < $item->rating; $i++) 
                                                <img class="inline-block" src="{{asset('assets/img/comment-star.svg')}}" />
                                            @endfor
                                            <p class="inline-block ml-3 translate-y-0.5 font-normal text-base">
                                                {{$item->created_at?->diffForHumans()}}
                                            </p>
                                        </div>
                                        <p class="font-light text-base text-black"> 
                                            {{$item->review_text}}
                                        </p>
                                    </li>
                                @empty
                                    <li class="text-center text-gray-500">
                                        {{ __('apartment.no_reviews') }}
                                    </li>
                                @endforelse
                            </ul>
                            
                        </div>
                        <div class="pt-8" id="tabs-3">
                            <h5 class="font-semibold text-xl text-filterhover mb-6">    
                                {{__('apartment.where_us')}}
                            </h5>
                             
                            <div class="h-52 lg:h-96 rounded-xl overflow-hidden" id="map">
                                {!! $apartment->building?->map !!}
                            </div>
                        </div>
                        <div class="pt-8" id="tabs-4">
                            <h5 class="font-semibold text-xl text-filterhover mb-6">
                                {{__('apartment.terms_policies_title')}}
                            </h5>
                           
                            <h6 class="mt-8">
                                {{$apartment->policy?->{'name_'.app()->getLocale()} }}
                            </h6>
                            <p class="font-light text-base text-gri mt-3 mb-2 ease-in-out duration-900 max-h-[72px] overflow-hidden">
                                {!! $apartment->policy?->{'description_'.app()->getLocale()} !!}              
                            </p>
                         </div>
                    </div>
                </div>
            </div>
            <div class="hidden xl:block xl:basis-4/12 rtl:xl:pr-5 xl:pl-5">
                <div class="border border border-filterborder rounded-xl px-5 py-6">
                    <!--<div class="checkout-slider-detail">
                        <a><img class="w-8" src="https://places.madar-solutions.click/storage/236/ckisxb3gminw93pa-b8c6h0ykuz9sfsz-fOGz.webp" /></a>
                        <a><img class="w-8" src="https://places.madar-solutions.click/storage/236/ckisxb3gminw93pa-b8c6h0ykuz9sfsz-fOGz.webp" /></a>
                        <a><img class="w-8" src="https://places.madar-solutions.click/storage/236/ckisxb3gminw93pa-b8c6h0ykuz9sfsz-fOGz.webp" /></a>
                        <a><img class="w-8" src="https://places.madar-solutions.click/storage/236/ckisxb3gminw93pa-b8c6h0ykuz9sfsz-fOGz.webp" /></a>
                    </div>-->
                    <p class="font-normal text-base text-reviews">
                        <span class="font-bold text-2xl text-black translate-y-0.5 inline-block">
                            {{$apartment->price}} 
                        </span> 
                        {{__('apartment.sar')}}
                    </p>
                    <form action="{{ route('web-booking.determine',$apartment->id) }}" class="mb-9 space-y-4" method="GET">
             
                        <div class="flex flex-wrap -mx-2">
                            <div class="flex flex-col w-1/2 px-2">
                                <label for="checkin" class="mb-1 font-semibold">@lang('apartment.checkin_date')</label>
                                <input type="date" id="checkin" name="checkin" class="bg-blackopacity border border-gray-300 rounded-lg h-12 px-3" required
                                    value="{{$started_day}}">
                            </div>
                        
                            <div class="flex flex-col w-1/2 px-2">
                                <label for="checkout" class="mb-1 font-semibold">@lang('apartment.checkout_date')</label>
                                <input type="date" id="checkout" name="checkout" class="bg-blackopacity border border-gray-300 rounded-lg h-12 px-3" required
                                    value="{{ $next_started_day }}">
                            </div>
                        </div>
                        
                            <script>
                            function validateInput(input, minMessage, maxMessage) {
                                if (input.validity.rangeUnderflow) {
                                    input.setCustomValidity(minMessage);  
                                } else if (input.validity.rangeOverflow) {
                                    input.setCustomValidity(maxMessage);   
                                } else {
                                    input.setCustomValidity('');  
                                }
                            }
                            </script>
                        
                        <ul>
                            <li class="mb-4 font-semibold text-sm text-title">
                                <span>
                                    {{__('apartment.one_night')}}
                                </span>
                                <span class="float-right rtl:float-left">
                                    {{$apartment->price . ' ' . __('apartment.price')}} 
                                </span>
                                <div class="clear-both"></div>
                            </li>
                            <li class="mb-4 font-semibold text-sm text-title">
                                <span>{{ __('apartment.total_nights') }}</span>
                                <span class="float-right rtl:float-left" id="totalNights">1 {{ __('apartment.nights') }}</span>
                                <div class="clear-both"></div>
                            </li>
                            <li class="mb-4 font-semibold text-sm text-title">
                                <span>{{ __('apartment.total_cost')}}</span>
                                <span class="font-normal text-base text-reviews">
                                    ({{__('apartment.price_tax') }})
                                </span>
                                <span class="float-right rtl:float-left" id="totalCost"> 
                                    {{calculateTotalWithTax($apartment->price) .' '.__('apartment.price') }} 
                                </span>
                                <div class="clear-both"></div>
                            </li>
                            
                            
                            
                        </ul>
                        @if ($errors->any())
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                            <strong class="font-bold">حدث خطأ!</strong>
                            <span class="block sm:inline">
                                @foreach ($errors->all() as $error)
                                    {{ $error }}
                                @endforeach
                            </span>
                             
                        </div>
                        @endif
                        
                        @auth('customer')
                            <button type="submit" class="bg-price rounded-lg h-12 w-full font-semibold text-white">@lang('apartment.book_now')</button>
                        @else
                            <button data-src="#popup-5" data-fancybox dont-close-click-outside class="bg-price rounded-lg h-12 w-full font-semibold text-white">@lang('apartment.book_now')</button>
                        @endauth
                    </form>
                    
                    
                    
                </div>
            </div>
        </div>
    </div>
</section>



<!-- Share Popup Modal -->
 
<div class="popup modal" id="popup-share">
    <div class="popup-contain text-center">
        <p class="p-5 border-b border-border text-left">
            {{ __('apartment.share_this_apartment') }}
        </p>
        <div class="p-5">
            <div class="text-left mb-5">
                <img class="float-left w-20 h-12 rounded-lg mr-4" src="{{getImage($apartment,'image')}}" />
                <p class="p-3">
                    {{ $apartment->ml('name') }}
                </p>
                <div class="clear-both"></div>
            </div>
            <ul class="md:grid md:grid-cols-2 md:gap-3 max-w-full">
                <li>
                    <a class="block border border-border rounded-lg text-left p-4" onclick="copyLink()">
                        <img class="inline-block mr-4 hover:bg-border" src="{{ asset('assets/img/link.svg') }}" />
                        <p class="inline-block">
                            {{ __('apartment.copy_link') }}
                        </p>
                    </a>
                    <!-- Notification message that appears briefly when link is copied -->
                    <div id="copyNotification" style="display: none; position: fixed; top: 20px; right: 20px; padding: 10px; background-color: #28a745; color: white; border-radius: 5px; z-index: 1000;">
                        {{ __('apartment.link_copied') }}
                    </div>

                </li>
                <li>
                    <a href="https://api.whatsapp.com/send?text={{ urlencode(Request::fullUrl()) }}" target="_blank"
                     class="block border border-border rounded-lg text-left p-4">
                        <img width="20px"  height="20px" class="inline-block mr-4 hover:bg-border" src="{{ asset('assets/img/whatsapp.png') }}" />
                        <p class="inline-block">
                            {{ __('apartment.whatsapp') }}
                        </p>
                    </a>
                </li>
                <li>
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(Request::fullUrl()) }}" target="_blank"
                     class="block border border-border rounded-lg text-left p-4">
                        <img class="inline-block mr-4 hover:bg-border" src="{{ asset('assets/img/fb.svg')}}" />
                        <p class="inline-block">
                            {{ __('apartment.facebook') }}
                        </p>
                    </a>
                </li>
                <li>
                    <a  href="https://twitter.com/intent/tweet?url={{ urlencode(Request::fullUrl()) }}" target="_blank"
                     class="block border border-border rounded-lg text-left p-4">
                        <img class="inline-block mr-4 hover:bg-border" src="{{ asset('assets/img/x.svg') }}" />
                        <p class="inline-block">
                            {{ __('apartment.X') }}
                        </p>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
{{-- @dd($apartment->booked_days($apartment->bookings)); --}}
@endsection
@push('js')
@include('customer.section.script-form')
@include('apartment.js')

<script>
    function copyLink() {
        const link = '{{ url()->current() }}';
        const tempInput = document.createElement('input');
        tempInput.value = link;
        document.body.appendChild(tempInput);
        tempInput.select();
        tempInput.setSelectionRange(0, 99999); 
        document.execCommand('copy');
        document.body.removeChild(tempInput);
        const notification = document.getElementById('copyNotification');
        notification.style.display = 'block';
        setTimeout(() => {
            notification.style.display = 'none';
        }, 2000);
    }

    var readMoreText = "{{ __('apartment.show_more') }}";
    var readLessText = "{{ __('apartment.show_less') }}";

   

    

    alert($('.desctext').text().trim().split(/\s+/).length);


    

        

    $(".showmoreApartment").click(function () {
        if ($(this).hasClass("active")) {
            $(this).removeClass("active");
            $(this).text(readMoreText); 
            $(this).prev("p").css({"maxHeight":"72px"});
        } else {
            $(this).addClass("active");
            $(this).text(readLessText);   
            $(this).prev("p").css({"maxHeight":"10000px"});
        }
    });
</script>

@endpush
