@extends('layouts.master')

@push('css')
<style>
    /* The theme paints the category sidebar with a loud red gradient
       (section .aside in the theme CSS). Replace it with a calm dark card that
       fits the rest of the dark page. */
    section .aside {
        background: #17130f !important;
        border: 1px solid rgba(255, 255, 255, 0.08);
    }
    /* Question cards: a subtle dark surface with light, readable text instead of
       the busy all-orange look. Keep the brand orange only for hover/active. */
    .faq .faq-item {
        background: #121110;
        border-color: rgba(255, 255, 255, 0.14);
    }
    .faq .faq-item:hover {
        border-color: #f7bb8e;
    }
    /* Open state: highlight the border and add a divider above the answer so it
       is obvious which question is expanded. */
    .faq .faq-item.faq-open {
        border-color: #f7bb8e;
        background: #17130f;
    }
    .faq .faq-question {
        color: #f5f5f4;
    }
    .faq .faq-item.faq-open .faq-answer {
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }
    .faq .faq-answer {
        color: #a8a29e;
        line-height: 1.9;
    }
    /* The theme's arrow SVG is black, so it is invisible on the dark card.
       Tint it light and rotate it when the question is open. */
    .faq .faq-question img {
        filter: brightness(0) invert(0.7);
        transition: transform 0.3s ease;
    }
    .faq .faq-item.faq-open .faq-question img {
        filter: brightness(0) saturate(100%) invert(72%) sepia(28%) saturate(900%) hue-rotate(330deg);
        transform: rotate(180deg);
    }
    /* Category list: the SELECTED category uses the accent color (orange) so it
       is obvious which category is showing; inactive categories stay muted white.
       This keeps orange meaning "active" consistently (selected category / open
       question), while the two use it differently (text vs. border) so they never
       look like the same control. */
    section .aside .category-tab.opacity-100 {
        color: #f7bb8e;
    }
    section .aside .category-tab.opacity-100 img {
        filter: brightness(0) saturate(100%) invert(72%) sepia(28%) saturate(900%) hue-rotate(330deg);
    }
</style>
@endpush

@section('content')

@include('pages.partials.breadcrumb')
<section class="py-12 container">
    <h2 class="font-bold text-3xl text-black mb-5 text-center">
        {{ $page->{'name_'.app()->getLocale()} }}
    </h2>
    <p class="font-light text-base text-titletext text-center md:px-32 lg:px-56 xl:px-96 mb-10">
        {{ strip_tags(str_replace('&nbsp;', ' ', $page->{'content_'.app()->getLocale()})) }}
    </p>

    <div class="lg:grid lg:grid-cols-4 lg:gap-4 w-full mx-0">
        <!-- Tabs for Categories -->
        <div class="aside rounded-lg py-8 px-5 mb-4 lg:mb-0">
            <ul>
                @foreach ($categories as $key => $category)
                    <li>
                        <a class="@if($key==0) opacity-100 @endif w-full category-tab relative 
                        font-normal text-base text-white opacity-60 block py-5 border-b border-whiteopacity ease-in-out duration-300 hover:opacity-100" data-category="{{ $category->id }}">
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
        $(this).closest(".faq-item").toggleClass("faq-open");
    });
});

</script>

@endpush
