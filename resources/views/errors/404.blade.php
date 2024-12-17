@extends('layouts.master')
 
@section('content')

<section class="breadcrumb py-16 sm:py-32 relative">
    <img class="object-cover" src="/storage/95/v8ebHZijIoHVx5QExMMBbyzVbiH1RB8RHQtD7jol.jpeg" />
    <div class="container z-10 relative text-center text-white">
        <h1 class="font-semibold text-4xl mb-3 sm:mb-6">
            404
        </h1>
        <ul>
            <li class="inline-block"><a href="{{route('home')}}" class="px-5">{{__('site.home')}}</a></li>
            <li class="inline-block"><a class="px-5"> 
                Page Not Found!
            </a></li>
        </ul>
    </div>
</section>

<section class="py-12 bg-footer">
    <div class="container text-center">
        <div class="lg:grid lg:grid-cols-1 lg:gap-4 w-full mx-0">
            <div class="pr-0 xl:pr-24">
                <p class="font-normal text-base text-price mb-5">
                    404
                </p>
                <p class="font-semibold text-3xl sm:text-5xl mb-6">
                   The Page Requested Is Not defined!
                </p>
                <p class="font-light text-base text-gri mb-12"> 
                   
                </p>
               
            </div>
             
        </div>
    </div>
</section> 

 

@endsection

 