@extends('layouts.master')
@section('content')

@include('pages.partials.breadcrumb', ['title' => $blog->{'name_'.app()->getLocale()}])

<section class="py-12 container">
    <div class="blogslider mb-8">
        <a href="{{getImage($blog,'image')}}" data-fancybox="slider">
            <img class="h-96 object-cover rounded-lg w-full" src="{{getImage($blog,'image')}}" /></a>
    </div>
    <h1 class="font-bold text-3xl text-black mb-8">
        {{$blog->{'name_'.app()->getLocale()} }}
    </h1>
    <p class="font-normal text-base text-black mb-20">
        {!! $blog->{'content_'.app()->getLocale()} !!}
    </p>
    <div class="my-5">
        <p class="inline-block translate-y-[-12px] me-2">شارك الخبر</p>
       


        <ul class="social inline-block">
            <li class="inline-block">
            <a href=https://www.facebook.com/sharer/sharer.php?u=YOUR_URL" target="_blank" class="block w-8 h-8 bg-blackopacity rounded-lg relative hover:bg-price ease-in-out duration-300">
            <img class="absolute" src="https://places.madar-solutions.click/front/assets/img/facebook.svg" alt="facebook">
            </a>
            </li>
                                    <li class="inline-block">
            <a href="https://twitter.com/share?url=YOUR_URL&text=YOUR_TEXT" target="_blank" class="block w-8 h-8 bg-blackopacity rounded-lg relative hover:bg-price ease-in-out duration-300">
            <img class="absolute" src="https://places.madar-solutions.click/front/assets/img/twitter.svg" alt="twitter">
            </a>
            </li>
                                    <li class="inline-block">
            <a href="https://www.instagram.com/YOUR_INSTAGRAM_PROFILE/" target="_blank" class="block w-8 h-8 bg-blackopacity rounded-lg relative hover:bg-price ease-in-out duration-300">
            <img class="absolute" src="https://places.madar-solutions.click/front/assets/img/instagram.svg" alt="instagram">
            </a>
            </li>
                                    <li class="inline-block">
            <a href="https://www.linkedin.com/shareArticle?url=YOUR_URL&title=YOUR_TITLE" target="_blank" class="block w-8 h-8 bg-blackopacity rounded-lg relative hover:bg-price ease-in-out duration-300">
            <img class="absolute" src="https://places.madar-solutions.click/front/assets/img/linkedin.svg" alt="linkedin">
            </a>
            </li>
            </ul>








    </div>
    <h1 class="font-bold text-3xl text-black mb-8">
        {{__('site.related_blogs')}}
    </h1>
    <div class="lg:grid lg:grid-cols-3 lg:gap-4 w-full mx-0">

        @foreach ($blogs as $blog)
            @include('pages.partials.blog-card')

        @endforeach
    </div>
</section>

@endsection
