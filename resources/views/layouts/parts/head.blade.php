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
    @php($mediaOrigin = parse_url(\Illuminate\Support\Facades\Storage::disk(config('media-library.disk_name'))->url('media'), PHP_URL_HOST))
    @if($mediaOrigin)
    <link rel="preconnect" href="https://{{ $mediaOrigin }}" />
    @endif

    @stack('TopCss')

    {{-- Fonts + one bundled stylesheet (App\Support\CssBundle) in the original cascade
         order: slick, fancybox, aos, style.css, flowbite, select2, Saudi Riyal font
         (the "saudi_riyal" font-families used by the x-riyal component). --}}
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@100..800&display=swap" rel="stylesheet" />
    @if(app()->getLocale() == 'ar')
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@200;300;400;500;700;800;900&display=swap" rel="stylesheet" />
    @endif
    {{ \App\Support\CssBundle::tags('base', [
        'vendor/slick-1.8.1/slick.css',
        'vendor/fancybox-3.5.7/jquery.fancybox.min.css',
        'vendor/aos-2.3.1/aos.css',
        'css/style.css',
        'vendor/flowbite-2.5.1/flowbite.min.css',
        'vendor/select2-4.1.0-rc.0/select2.min.css',
        'vendor/saudi-riyal-font-1.1.0/index.css',
    ]) }}
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/img/favicon.svg')}}" />
    <link rel="icon" type="image/x-www-form ico" href="{{ asset('favicon.ico') }}" />
    <link rel="apple-touch-icon" href="{{ asset('assets/img/favicon.svg') }}" />

    


    <style>
      .datepicker-cell {
        color: #787e8b !important;
      }
      .focused {
        color: black !important;
      }
      .select2-selection {
        border: none !important;
      }
      .select2-container--default
        .select2-selection--single
        .select2-selection__rendered {
        color: inherit !important;
      }
      .view-switch,
      .prev-btn > svg,
      .next-btn > svg {
        color: #000 !important;
      }
      .range-start {
        color: white !important;
      }

      /* Guarantee a consistent side gutter on every device: overrides
         Tailwind's zero-padding .container (tailwind.css is loaded last in the
         head), which otherwise makes content touch the screen edge.
         Uses inner padding (border-box) instead of a calc() max-width so the
         gutter is identical on every device and never collapses to zero. */
      .container {
        width: 100% !important;
        max-width: 100% !important;
        margin-left: auto !important;
        margin-right: auto !important;
        padding-left: 16px !important;
        padding-right: 16px !important;
      }
      @media (min-width: 768px) {
        .container {
          padding-left: 24px !important;
          padding-right: 24px !important;
        }
      }
      @media (min-width: 1280px) {
        .container {
          padding-left: 32px !important;
          padding-right: 32px !important;
        }
      }

      /* Scroll position indicator (thumb) stays visible while the scrollbar
         track/gutter is fully transparent. The page scrollbar runs over both
         the dark header/sections and the light content, so the thumb uses the
         brand accent (#f7bb8e) — it reads clearly on light AND dark and looks
         intentional. A transparent border padded via background-clip keeps the
         pill slim with breathing room from the edge. */
      * {
        scrollbar-width: thin;                        /* Firefox */
        scrollbar-color: #f7bb8e transparent;         /* thumb, track */
      }
      *::-webkit-scrollbar {
        width: 10px;
        height: 10px;
        background: transparent;
      }
      *::-webkit-scrollbar-track {
        background: transparent;
      }
      *::-webkit-scrollbar-thumb {
        background-color: #f7bb8e;
        border: 2px solid transparent;
        background-clip: padding-box;
        border-radius: 999px;
      }
      *::-webkit-scrollbar-thumb:hover {
        background-color: #f0a86e;
      }

      section.app {
 
    background: #171515 !important;
}

@media (max-width: 768px) {
  section.app.relative img {
    margin: auto;
    text-align: center;
}
section.app.relative a {
    margin-right: 10px !important;
    margin-left: 10px !important;
    display: inline-block;
}

section.app.relative > div {
    text-align: center;
}
}

      /* Saudi Riyal symbol (U+20C1). Rendered by the x-riyal component and
         window.formatSAR(). Uses the "saudi_riyal" webfont loaded in the page
         head; inherits color/size from the surrounding text and is nudged to sit
         on the baseline. When OS fonts ship the glyph natively, append a system
         font to this stack and the CDN font can be dropped. */
      .sar-symbol {
        font-family: 'saudi_riyal', sans-serif;
        font-style: normal;
        font-weight: inherit;
        line-height: 1;
        display: inline-block;
        vertical-align: -0.075em;
      }
      .sar-symbol--bold {
        font-family: 'saudi_riyal_bold', sans-serif;
      }
    </style>


    {{ \App\Support\CssBundle::tags('theme-'.app()->getLocale(), array_merge(
        app()->getLocale() == 'ar' ? ['css/rtl.css'] : [],
        ['css/custom.css', 'css/dark.css'],
    )) }}

    @stack('css')

    {{-- Compiled Tailwind (tailwind.config.js). Kept last in <head> so it wins
         the cascade exactly as the former Play CDN's injected <style> did. --}}
    <link href="{{ asset('assets/css/tailwind.css') }}?v={{ @filemtime(public_path('front/assets/css/tailwind.css')) }}" rel="stylesheet" />
