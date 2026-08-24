{{-- إعادة محاولة استرداد الفرق الفاشل (التواريخ الجديدة مطبّقة أصلاً). --}}
@if ($entry->status === 'failed')
    <button type="button" class="btn btn-xs btn-warning dc-action-btn"
            data-url="{{ url(config('backpack.base.route_prefix')) }}/date-change-requests/{{ $entry->getKey() }}/retry"
            data-title="{{ __('cms.date_change_retry_btn') }}"
            data-confirm="{{ __('cms.date_change_retry_confirm') }}"
            data-confirm-btn="{{ __('cms.date_change_retry_btn') }}"
            data-icon="warning"
            data-color="#f0ad4e">
        <i class="la la-redo"></i> {{ __('cms.date_change_retry_btn') }}
    </button>
@endif
