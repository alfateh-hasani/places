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
