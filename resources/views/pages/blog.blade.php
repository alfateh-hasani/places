@extends('layouts.master')
@section('content')

@include('pages.partials.breadcrumb')
<section class="py-12 container">
    <div class="lg:grid lg:grid-cols-3 lg:gap-4 w-full mx-0">
        @foreach ($blogs as $blog)
            @include('pages.partials.blog-card')
           
        @endforeach


    </div>
    {{$blogs->links()}}
</section>

@endsection
