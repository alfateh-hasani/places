    <meta charset="utf-8" />
      {!! SEO::generate() !!}
    @foreach(LaravelLocalization::getSupportedLocales() as $localeCode => $properties)
    <link rel="alternate" hreflang="{{ $localeCode }}" href="{{ LaravelLocalization::getLocalizedURL($localeCode, null, [], true) }}" />
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ LaravelLocalization::getLocalizedURL(config('app.locale'), null, [], true) }}" />
    <meta   name="viewport"  content="width=device-width, initial-scale=1, shrink-to-fit=no"   />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="theme-color" content="#f7bb8e" />

    <!-- Resource hints: warm up connections to third-party origins used below -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin />
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net" />
    <link rel="preconnect" href="https://code.jquery.com" crossorigin />
    <link rel="dns-prefetch" href="https://code.jquery.com" />
    <link rel="preconnect" href="https://cdn.tailwindcss.com" crossorigin />
    <link rel="dns-prefetch" href="https://cdn.tailwindcss.com" />
    <link rel="preconnect" href="https://unpkg.com" crossorigin />
    <link rel="dns-prefetch" href="https://unpkg.com" />
    <link rel="preconnect" href="https://code.iconify.design" crossorigin />
    <link rel="dns-prefetch" href="https://code.iconify.design" />

    @stack('TopCss')

    <link href="{{ asset('assets/css/style.css') }}?v={{ @filemtime(public_path('assets/css/style.css')) }}" rel="stylesheet" />
    <link    href="https://cdn.jsdelivr.net/npm/flowbite@2.5.1/dist/flowbite.min.css"  rel="stylesheet"  />
    <link    href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"   rel="stylesheet" />
    {{-- Official Saudi Riyal symbol (Unicode U+20C1, SAMA 2025). Registers the
         "saudi_riyal" / "saudi_riyal_bold" font-families used by the x-riyal component. --}}
    <link    href="https://cdn.jsdelivr.net/npm/@emran-alhaddad/saudi-riyal-font/index.css"   rel="stylesheet" />
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/img/favicon.svg')}}" />
    <link rel="icon" type="image/x-www-form ico" href="{{ asset('favicon.ico') }}" />
    <link rel="apple-touch-icon" href="{{ asset('assets/img/favicon.svg') }}" />
    <script  type="text/javascript"    src="https://code.jquery.com/jquery-3.7.1.js"  ></script>
    <script src="https://code.jquery.com/ui/1.14.0/jquery-ui.js"></script>

    
    <script type="text/javascript" src="https://cdn.tailwindcss.com"></script>
    <script type="text/javascript" src="{{ asset('assets/js/maplace.js') }}?v={{ @filemtime(public_path('assets/js/maplace.js')) }}"></script>


    <script>
      tailwind.config = {
          theme: {
              container: {
                  center: true,
              },
              colors: {
                  'gri': '#f7bb8e',
                  'white': '#fff',
                  'black': '#000',
                  'border': '#f7bb8e',
                  'reviews': '#999999',
                  'title': '#2C2C2C',
                  'feature': '#343233',
                  'feature-border': '#E8E8E8',
                  'price': '#f7bb8e',
                  'automated-1': 'rgba(239, 85, 44, .2)',
                  'automated-2': 'rgba(255, 90, 95, .2)',
                  'automated-3': 'rgba(233, 187, 113, .2)',
                  'gritext': '#444',
                  'commentbg': '#F4F6F8',
                  'commentborder': '#F1F1F1',
                  'blackopacity': 'rgba(0, 0, 0, .1)',
                  'properits': '#F8F7FD',
                  'filterbackground': '#fbfbfb',
                  'filterborder': '#ececec',
                  'filteritem': '#ebebe8',
                  'filterhover': '#f7bb8e',
                  'sort': '#f6f6f6',
                  'sortactive': '#f7bb8e',
                  'blue': '#0068CF',
                  'footer': '#fcfcfc',
                  'titletext': '#848484'
              }
          }
      }
  </script> 
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

      /* Guarantee a consistent side gutter on every device: overrides the
         Tailwind Play CDN's zero-padding .container, which otherwise wins the
         cascade race on some viewports and makes content touch the screen edge.
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


    @if(app()->getLocale() == 'ar')
      <link    href="{{ asset('assets/css/rtl.css') }}?v={{ @filemtime(public_path('assets/css/rtl.css')) }}"   rel="stylesheet" />
    @endif

      <link    href="{{ asset('assets/css/custom.css') }}?v={{ @filemtime(public_path('assets/css/custom.css')) }}"   rel="stylesheet" />
      <link    href="{{ asset('assets/css/dark.css') }}?v={{ @filemtime(public_path('assets/css/dark.css')) }}"   rel="stylesheet" />

    @stack('css')