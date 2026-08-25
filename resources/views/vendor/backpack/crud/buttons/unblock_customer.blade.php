@if (! empty($entry->blocked_at))
    <button type="button" class="btn btn-xs btn-success dc-action-btn"
            data-url="{{ url(config('backpack.base.route_prefix')) }}/customer/{{ $entry->getKey() }}/unblock"
            data-title="{{ __('cms.unblock_customer') }}"
            data-confirm="{{ __('cms.unblock_confirm') }}"
            data-confirm-btn="{{ __('cms.unblock_customer') }}"
            data-icon="question"
            data-color="#28a745">
        <i class="la la-unlock"></i> {{ __('cms.unblock_customer') }}
    </button>
@endif
