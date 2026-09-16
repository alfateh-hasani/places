{{-- Contextual cancellation action for one booking. Shows the guided "Manage
     cancellation" button while a request is under review, or the refund button once
     the cancel is finalized. Requires $booking. --}}
@php($prefix = config('backpack.base.route_prefix'))

@if ($booking->status === \App\Enums\BookingStatus::CancellationRequested->value && $booking->refund_status === 'pending')
    <button type="button" class="btn btn-xs btn-warning js-manage-cancel-btn"
            data-number="{{ $booking->number_of_booking }}"
            data-mapped="{{ $booking->isLinkedToOwnerRez() ? 1 : 0 }}"
            data-ownerrez-url="{{ $booking->isLinkedToOwnerRez() ? rtrim(config('ownerrez.app_url'), '/').'/bookings/'.$booking->ownerrez_booking_id : '' }}"
            data-cancel-local-url="{{ url($prefix.'/booking/'.$booking->getKey().'/cancel-local') }}"
            data-reject-url="{{ url($prefix.'/booking/'.$booking->getKey().'/reject-cancellation') }}">
        <i class="la la-cog"></i> {{ __('cms.manage_cancellation') }}
    </button>
@elseif ($booking->status === \App\Enums\BookingStatus::Canceled->value && $booking->refund_status === 'pending')
    <button type="button" class="btn btn-xs btn-success js-refund-btn"
            data-action="{{ url($prefix.'/booking/'.$booking->getKey().'/refund') }}"
            data-number="{{ $booking->number_of_booking }}"
            data-max="{{ number_format((float) $booking->final_price, 2, '.', '') }}"
            data-current="{{ number_format((float) $booking->final_price, 2, '.', '') }}">
        <i class="la la-money-bill-wave"></i> {{ __('cms.refund_amount') }}
    </button>
@endif
