@extends('layouts.master')
@section('content')

    @include('home.parts.slider')
    @include('home.parts.search')
    @include('home.parts.apartments')
    @include('home.parts.buildings')
    @include('home.parts.apps_banner')
    @include('home.parts.reviews')
@endsection