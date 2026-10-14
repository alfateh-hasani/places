{{-- Customer product tour: loads driver.js and our controller, and hands PHP data to JS.
     Included once near the end of layouts/parts/js.blade.php (after jQuery/main.js). --}}

@php
    // Decide which tour belongs to the current page from the route name.
    // NOTE: all page tours are currently disabled except the account-area tour.
    // To re-enable one, uncomment its line below (keep 'customer.booking.details'
    // above the account arm so it isn't swallowed by the 'customer.booking' match).
    $tourKey = match (true) {
        // request()->routeIs('home')                          => 'home',
        // request()->routeIs('apartments.show')               => 'details',
        // request()->routeIs('web-booking.confirm-booking')   => 'booking',
        // request()->routeIs('customer.booking.details')      => 'booking-details',
        request()->routeIs('customer.account'),
        request()->routeIs('customer.booking'),
        request()->routeIs('customer.favorite'),
        request()->routeIs('customer.notifications')        => 'account',
        default                                             => null,
    };
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
