@extends('layouts.master')

@section('content')
    @include('pages.partials.breadcrumb')
<section class="py-12 container">
        {!! $page->{'content_'.app()->getLocale()} !!}   
</section>
@endsection