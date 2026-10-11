@extends('layouts.master')
@section('content')

@include('pages.partials.breadcrumb', ['title' => $blog->{'name_'.app()->getLocale()}])

<section class="py-12 container">
    <div class="blogslider mb-8">
        <a href="{{getImage($blog,'image')}}" data-fancybox="slider">
            <img class="h-96 object-cover rounded-lg w-full" src="{{getImage($blog,'image')}}" /></a>
    </div>
    <h2 class="font-bold text-3xl text-black mb-8">
        {{$blog->{'name_'.app()->getLocale()} }}
    </h2>
    <p class="font-normal text-base text-black mb-20">
        {!! $blog->{'content_'.app()->getLocale()} !!}
    </p>
    @php
        $shareUrl = urlencode(route('blog', $blog->slug));
        $shareTitle = urlencode($blog->{'name_'.app()->getLocale()});
    @endphp
    <div class="my-5 flex items-center flex-wrap gap-3">
        <p class="text-white">{{ __('site.share_post') }}</p>
        <ul class="social flex items-center gap-2">
            <li class="inline-block">
            <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener noreferrer" class="block w-8 h-8 bg-price rounded-lg relative hover:opacity-80 ease-in-out duration-300">
            <img class="absolute" src="{{ asset('assets/img/facebook.svg') }}" alt="facebook">
            </a>
            </li>
                                    <li class="inline-block">
            <a href="https://twitter.com/share?url={{ $shareUrl }}&text={{ $shareTitle }}" target="_blank" rel="noopener noreferrer" class="block w-8 h-8 bg-price rounded-lg relative hover:opacity-80 ease-in-out duration-300">
            <img class="absolute" src="{{ asset('assets/img/twitter.svg') }}" alt="twitter">
            </a>
            </li>
                                    <li class="inline-block">
            {{-- Instagram has no web share URL, so this opens the company profile from settings. --}}
            <a href="{{ Config::get('settings.instagram') ?: 'https://www.instagram.com/' }}" target="_blank" rel="noopener noreferrer" class="block w-8 h-8 bg-price rounded-lg relative hover:opacity-80 ease-in-out duration-300">
            <img class="absolute" src="{{ asset('assets/img/instagram.svg') }}" alt="instagram">
            </a>
            </li>
                                    <li class="inline-block">
            <a href="https://www.linkedin.com/shareArticle?url={{ $shareUrl }}&title={{ $shareTitle }}" target="_blank" rel="noopener noreferrer" class="block w-8 h-8 bg-price rounded-lg relative hover:opacity-80 ease-in-out duration-300">
            <img class="absolute" src="{{ asset('assets/img/linkedin.svg') }}" alt="linkedin">
            </a>
            </li>
            </ul>
    </div>
    <h2 class="font-bold text-3xl text-black mb-8">
        {{__('site.related_blogs')}}
    </h2>
    <div class="lg:grid lg:grid-cols-3 lg:gap-4 w-full mx-0">

        @foreach ($blogs as $blog)
            @include('pages.partials.blog-card')

        @endforeach
    </div>
</section>

@endsection
