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
        @yield('content')
    @include('common.footer')
    @include('layouts.parts.js')
  </body>
</html>
