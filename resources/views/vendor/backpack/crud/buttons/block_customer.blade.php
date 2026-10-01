@if (empty($entry->blocked_at))
    <button type="button" class="btn btn-xs btn-danger js-block-customer-btn"
            data-action="{{ url(config('backpack.base.route_prefix')) }}/customer/{{ $entry->getKey() }}/block"
            data-number="{{ $entry->full_name }}">
        <i class="la la-ban"></i> {{ __('cms.block_customer') }}
    </button>
@endif
