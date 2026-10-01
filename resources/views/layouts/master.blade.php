<!DOCTYPE html>
@php
$locale = app()->getLocale();
@endphp
<html lang="{{ $locale }}"  dir="{{ LaravelLocalization::getCurrentLocaleDirection() }}">
  <head>
    @include('layouts.parts.head')
  </head>
  <body class="lg:pt-20 pt-16">
    @if(config('services.google_tag_manager.id'))
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ urlencode(config('services.google_tag_manager.id')) }}"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    @endif
    @include('common.header')
    <main>
        @yield('content')
    </main>
    @include('common.footer')
    @include('layouts.parts.js')
  </body>
</html>
