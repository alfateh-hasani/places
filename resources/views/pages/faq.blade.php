@extends('layouts.master')

@section('content')

@include('pages.partials.breadcrumb')
<section class="py-12 container">
    <h1 class="font-bold text-3xl text-black mb-5 text-center">Frequently Asked Questions</h1>
    <p class="font-light text-base text-titletext text-center md:px-32 lg:px-56 xl:px-96 mb-10">
        {{ strip_tags(str_replace('&nbsp;', ' ', $page->{'content_'.app()->getLocale()})) }}
    </p>

    <div class="lg:grid lg:grid-cols-4 lg:gap-4 w-full mx-0">
        <!-- Tabs for Categories -->
        <div class="aside rounded-lg py-8 px-5 mb-4 lg:mb-0">
            <ul>
                @foreach ($categories as $key => $category)
                    <li>
                        <a class="@if($key==0) opacity-100 @endif w-full category-tab relative font-normal text-base text-white opacity-60 block py-5 border-b border-whiteopacity ease-in-out duration-300 hover:opacity-100" data-category="{{ $category->id }}">
                            {{ $category->{'name_'.app()->getLocale()} }}
                            <img class="inline-block absolute ltr:right-0 rtl:left-0 rtl:rotate-180 translate-y-1.5" src="{{asset('assets/img/aside-arrow.svg')}}" />
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        <!-- FAQ Questions -->
        <div class="faq col-span-3">
            @foreach ($categories as $category)
                <ul class="faq-category hidden" id="category-{{ $category->id }}">
                    @foreach ($category->questions as $question)
                        <li class="faq-item border border-border rounded-lg shadow-md mb-4 hover:border-price ease-in-out duration-300 cursor-pointer">
                            <a class="faq-question relative block mx-5 py-5 pr-5 font-normal text-base text-black">
                                {{ $question->{'title_'.app()->getLocale()} }}
                                <img class="inline-block absolute ltr:right-0 rtl:left-0 top-7" src="{{ asset('assets/img/faq.svg') }}" />
                            </a>
                            <p class="faq-answer p-5 font-normal text-base text-black hidden">{{ $question->{'description_'.app()->getLocale()} }}</p>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </div>
    </div>
</section>

@push('js')
<script>
    $(document).ready(function () {
    
    $(".faq-category").first().show();

    $(".category-tab").on("click", function () {
        
        $(".category-tab").removeClass("opacity-100");
        $(this).addClass("opacity-100");
        $(".faq-category").hide();

        const categoryId = $(this).data("category");
        
        $("#category-" + categoryId).fadeIn();
    });

    $(".faq-question").on("click", function () {
        $(this).next(".faq-answer").slideToggle();
    });
});

</script>

@endpush
