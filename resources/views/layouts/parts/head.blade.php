    <meta charset="utf-8" />
      {!! SEO::generate() !!}
    @foreach(LaravelLocalization::getSupportedLocales() as $localeCode => $properties)
    <link rel="alternate" hreflang="{{ $localeCode }}" href="{{ LaravelLocalization::getLocalizedURL($localeCode) }}" />
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ LaravelLocalization::getLocalizedURL(LaravelLocalization::getDefaultLocale()) }}" />
    <meta   name="viewport"  content="width=device-width, initial-scale=1, shrink-to-fit=no"   />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="theme-color" content="#f7bb8e" />

    {{-- Resource hints: media (slider = LCP image) is served from S3. --}}
    @php
        $mediaOrigin = parse_url(\Illuminate\Support\Facades\Storage::disk(config('media-library.disk_name'))->url('media'), PHP_URL_HOST);
    @endphp
    @if($mediaOrigin)
    <link rel="preconnect" href="https://{{ $mediaOrigin }}" />
    @endif

    @stack('TopCss')

    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/img/favicon.svg')}}" />
    <link rel="icon" type="image/x-www-form ico" href="{{ asset('favicon.ico') }}" />
    <link rel="apple-touch-icon" href="{{ asset('assets/img/favicon.svg') }}" />

    {{-- Stylesheets (App\Support\CssBundle), in the original cascade order:
         self-hosted fonts, slick, fancybox, aos, style.css, flowbite, select2,
         Saudi Riyal font ("saudi_riyal" families for x-riyal), css/head.css,
         rtl (ar), custom, dark, page @push('css'), then Tailwind last (where
         the former Play CDN injected it). Pages without their own CSS get it
         all as a single file. --}}
    @php
        $isArabic = app()->getLocale() == 'ar';
        $pageCss = trim($__env->yieldPushContent('css'));
        $baseCss = array_merge(
            ['vendor/google-fonts/sora.css'],
            $isArabic ? ['vendor/google-fonts/tajawal.css'] : [],
            [
                'vendor/slick-1.8.1/slick.css',
                'vendor/fancybox-3.5.7/jquery.fancybox.min.css',
                'vendor/aos-2.3.1/aos.css',
                'css/style.css',
                'vendor/flowbite-2.5.1/flowbite.min.css',
                'vendor/select2-4.1.0-rc.0/select2.min.css',
                'vendor/saudi-riyal-font-1.1.0/index.css',
                'css/head.css',
            ],
        );
        $themeCss = array_merge($isArabic ? ['css/rtl.css'] : [], ['css/custom.css', 'css/dark.css']);
    @endphp
    @if($pageCss === '')
    {{ \App\Support\CssBundle::tags('site-'.app()->getLocale(), array_merge($baseCss, $themeCss, ['css/tailwind.css'])) }}
    @else
    {{ \App\Support\CssBundle::tags('base-'.app()->getLocale(), $baseCss) }}
    {{ \App\Support\CssBundle::tags('theme-'.app()->getLocale(), $themeCss) }}
    {!! $pageCss !!}
    {{ \App\Support\CssBundle::tags('tailwind', ['css/tailwind.css']) }}
    @endif
