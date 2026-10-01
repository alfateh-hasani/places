@php
    $sliderLocale = app()->getLocale();
    $firstSlider = $sliders->first();
    $firstMobileUrl = $firstSlider ? $firstSlider->displayImageUrl('image_mobile_'.$sliderLocale, 'hero_mobile') : '';
@endphp

{{-- The first slide is the LCP element: start fetching it from <head>. The media
     queries mirror the <picture> below so exactly one image is downloaded. --}}
@if($firstSlider)
    @push('TopCss')
        @if($firstMobileUrl)
            <link rel="preload" as="image" fetchpriority="high" href="{{ $firstMobileUrl }}" media="(max-width: 1023px)" />
        @endif
        <link rel="preload" as="image" fetchpriority="high"
            href="{{ $firstSlider->displayImageUrl('image_'.$sliderLocale, 'hero') }}"
            imagesrcset="{{ $firstSlider->heroSrcset('image_'.$sliderLocale) }}"
            imagesizes="100vw"
            @if($firstMobileUrl) media="(min-width: 1024px)" @endif />
    @endpush
@endif

<section class="slider w-full  ">
    @foreach($sliders as $slider)
        <a href="{{ $slider->ml('link') }}">
            <picture>
                <source media="(max-width: 1023px)" srcset="{{ $slider->displayImageUrl('image_mobile_'.$sliderLocale, 'hero_mobile') }}" width="900" height="1208">
                <img src="{{ $slider->displayImageUrl('image_'.$sliderLocale, 'hero') }}"
                    srcset="{{ $slider->heroSrcset('image_'.$sliderLocale) }}"
                    sizes="100vw"
                    width="1600" height="537"
                    @if($loop->first) fetchpriority="high" loading="eager" @else loading="lazy" @endif
                    alt="{{ $slider->ml('name') }}" />
            </picture>
        </a>
    @endforeach
</section>
