{{-- Customer product tour: loads driver.js and our controller, and hands PHP data to JS.
     Included once near the end of layouts/parts/js.blade.php (after jQuery/main.js). --}}

@php
    // Which tour belongs to the current page (null = none). The route→key map and
    // the enable/disable toggles live in App\Support\ProductTour so the header "?"
    // trigger and this loader always agree.
    $tourKey = \App\Support\ProductTour::currentKey();
@endphp

@if ($tourKey)
    <link rel="stylesheet" href="{{ asset('assets/vendor/driverjs/driver.css') }}">
    <script src="{{ asset('assets/vendor/driverjs/driver.js.iife.js') }}" defer></script>
    <script>
        window.DyafaTour = {
            key: @json($tourKey),
            isRtl: @json(app()->getLocale() === 'ar'),
            isLoggedIn: @json(auth('customer')->check()),
            // Bump this when tour copy/steps change so returning users see the new tour once.
            version: 1,
            i18n: {
                next: @json(__('tour.next')),
                prev: @json(__('tour.prev')),
                done: @json(__('tour.done')),
                skip: @json(__('tour.skip')),
                progress: @json(__('tour.progress')),
            },
            text: @json(__('tour.' . $tourKey)),
        };
    </script>
    <script src="{{ asset('assets/js/tour.js') }}?v={{ @filemtime(public_path('front/assets/js/tour.js')) }}" defer></script>
@endif
