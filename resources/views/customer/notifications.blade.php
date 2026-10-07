@extends('layouts.master')
@push('css')
<style>
    /* Scope the dark override to this page's own section so it does not repaint
       global white surfaces (header menus, login popup, SweetAlert) black. */
    .profile .bg-white {
        background-color: #0f0c0c;
    }
</style>
@endpush
@section('content')

<section class="profile py-5 lg:py-16 text-white min-h-screen lg:min-h-min">
    <div class="container">
        <div class="lg:grid lg:grid-cols-4 lg:gap-6 w-full mx-0">
            @include('customer.section.sidebar')
            <div class="col-span-3">
                @include('customer.section.header')
                <div class="bg-white py-8 px-6 rounded-2xl mt-5">
                    <div class="flex items-center justify-between gap-3 mb-6">
                        <div class="flex items-center">
                            <svg class="inline-block" xmlns="http://www.w3.org/2000/svg" width="20" height="22" viewBox="0 0 17.997 19.86">
                                <g transform="translate(-3.9 -2.4)">
                                    <path d="M18.5,8.6a5.6,5.6,0,1,0-11.2,0c0,6.532-2.8,8.4-2.8,8.4H21.3s-2.8-1.866-2.8-8.4" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2"/>
                                    <path d="M18.634,31.5a1.866,1.866,0,0,1-3.229,0" transform="translate(-4.121 -10.77)" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2"/>
                                </g>
                            </svg>
                            <h1 class="inline-block ms-3 font-semibold text-lg">
                                {{ __('customer.notifications_title') }}
                                @if ($unread_count)
                                    <span class="text-price">({{ $unread_count }})</span>
                                @endif
                            </h1>
                        </div>

                        @if ($unread_count)
                            <form method="POST" action="{{ route('customer.notifications.read-all') }}">
                                @csrf
                                <button type="submit" class="text-sm text-price hover:underline ease-in-out duration-300">
                                    {{ __('customer.mark_all_read') }}
                                </button>
                            </form>
                        @endif
                    </div>

                    @forelse ($notifications as $notification)
                        @php $isUnread = is_null($notification->read_at); @endphp
                        <div class="flex items-stretch rounded-xl border mb-3 overflow-hidden ease-in-out duration-300 {{ $isUnread ? 'border-price bg-[#17130f]' : 'border-border' }}">
                            <a href="{{ route('customer.notifications.open', $notification->id) }}"
                               class="flex items-start gap-3 p-5 flex-1 min-w-0 hover:bg-white/5 ease-in-out duration-300">
                                <span class="shrink-0 w-10 h-10 rounded-full bg-[#fdede9] text-[#ef552c] flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="18" viewBox="0 0 17.997 19.86">
                                        <g transform="translate(-3.9 -2.4)">
                                            <path d="M18.5,8.6a5.6,5.6,0,1,0-11.2,0c0,6.532-2.8,8.4-2.8,8.4H21.3s-2.8-1.866-2.8-8.4" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2"/>
                                            <path d="M18.634,31.5a1.866,1.866,0,0,1-3.229,0" transform="translate(-4.121 -10.77)" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2"/>
                                        </g>
                                    </svg>
                                </span>
                                <span class="flex-1 min-w-0">
                                    <span class="flex items-center gap-2">
                                        @if ($isUnread)
                                            <span class="w-2 h-2 rounded-full bg-price shrink-0"></span>
                                        @endif
                                        <span class="font-semibold text-sm {{ $isUnread ? 'text-white' : 'text-reviews' }}">
                                            {{ $notification->title }}
                                        </span>
                                    </span>
                                    @if ($notification->body)
                                        <span class="block text-xs text-reviews mt-1 leading-relaxed">{{ $notification->body }}</span>
                                    @endif
                                    <span class="block text-[11px] text-reviews mt-2">{{ $notification->created_at?->diffForHumans() }}</span>
                                </span>
                            </a>
                            <form method="POST" action="{{ route('customer.notifications.destroy', $notification->id) }}"
                                  class="flex items-start p-3">
                                @csrf
                                @method('DELETE')
                                <button type="submit" aria-label="{{ __('customer.dismiss') }}"
                                        class="w-7 h-7 flex items-center justify-center rounded-full text-reviews hover:text-white hover:bg-white/10 ease-in-out duration-300">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                        <path d="M6 6l12 12M6 18L18 6"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center text-center py-16">
                            <svg class="mb-4 text-reviews" xmlns="http://www.w3.org/2000/svg" width="48" height="52" viewBox="0 0 17.997 19.86">
                                <g transform="translate(-3.9 -2.4)">
                                    <path d="M18.5,8.6a5.6,5.6,0,1,0-11.2,0c0,6.532-2.8,8.4-2.8,8.4H21.3s-2.8-1.866-2.8-8.4" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2"/>
                                    <path d="M18.634,31.5a1.866,1.866,0,0,1-3.229,0" transform="translate(-4.121 -10.77)" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2"/>
                                </g>
                            </svg>
                            <h3 class="font-semibold text-lg mb-2">{{ __('customer.no_notifications') }}</h3>
                            <p class="font-normal text-sm text-reviews max-w-md">{{ __('customer.no_notifications_hint') }}</p>
                        </div>
                    @endforelse

                    @if ($notifications->hasPages())
                        <div class="mt-6">
                            {!! $notifications->links() !!}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
