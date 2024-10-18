@extends('layouts.app')

@section('content')
    <section class="container py-10">
        <h1 class="text-2xl font-semibold mb-6">Apartments</h1>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($apartments as $apartment)
                @include('apartment.card', ['apartment' => $apartment])
            @endforeach
        </div>

        <div class="mt-6">
            {{ $apartments->links() }}  <!-- Pagination Links -->
        </div>
    </section>
@endsection
