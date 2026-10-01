<!DOCTYPE html>
@php
$locale = app()->getLocale();
@endphp
<html lang="{{ $locale }}"  dir="{{ LaravelLocalization::getCurrentLocaleDirection() }}">
  <head>
    @include('layouts.parts.head')
  </head>
  <body class="lg:pt-20 pt-16">
    @include('common.header')
    <main>
        @yield('content')
    </main>
    @include('common.footer')
    @include('layouts.parts.js')
  </body>
</html>
