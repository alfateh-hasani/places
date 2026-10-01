@if (app(\App\Services\Otp\OtpRequestThrottle::class)->isBlocked($entry->phone))
    <button type="button" class="btn btn-xs btn-warning dc-action-btn"
            data-url="{{ url(config('backpack.base.route_prefix')) }}/customer/{{ $entry->getKey() }}/reset-otp"
            data-title="{{ __('cms.reset_otp') }}"
            data-confirm="{{ __('cms.reset_otp_confirm') }}"
            data-confirm-btn="{{ __('cms.reset_otp') }}"
            data-icon="question"
            data-color="#f0ad4e">
        <i class="la la-unlock"></i> {{ __('cms.reset_otp') }}
    </button>
@endif
