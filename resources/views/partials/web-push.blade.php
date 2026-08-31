{{--
    Web Push bootstrap, shared by the staff dashboard (Backpack) and the customer
    web layout. Renders only for an authenticated staff User or web Customer and
    points the subscribe endpoint at the matching guard's route. No effect on the
    mobile app / FCM.
--}}
@php
    $webPushSubscribeUrl = null;

    if (function_exists('backpack_auth') && backpack_auth()->check()) {
        $webPushSubscribeUrl = route('admin.push.subscribe');
    } elseif (auth('customer')->check()) {
        $webPushSubscribeUrl = route('customer.push.subscribe');
    }
@endphp

@if ($webPushSubscribeUrl && config('webpush.vapid.public_key'))
    <script>
        window.WebPushConfig = {
            vapidPublicKey: @json(config('webpush.vapid.public_key')),
            subscribeUrl: @json($webPushSubscribeUrl),
            csrf: @json(csrf_token()),
        };
    </script>
    {{-- url() (not asset()) so the script loads from the app origin, not ASSET_URL's CDN host. --}}
    <script src="{{ url('js/web-push.js') }}?v=2"></script>
@endif
