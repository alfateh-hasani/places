{{-- الموافقة على طلب تعديل التواريخ (النطاق الأرخص): تطبيق التواريخ الجديدة + استرداد الفرق. --}}
@if ($entry->status === 'pending_review')
    <button type="button" class="btn btn-xs btn-success dc-action-btn"
            data-url="{{ url(config('backpack.base.route_prefix')) }}/date-change-requests/{{ $entry->getKey() }}/approve"
            data-title="{{ __('cms.date_change_approve_btn') }}"
            data-confirm="{{ __('cms.date_change_approve_confirm') }}"
            data-confirm-btn="{{ __('cms.date_change_approve_btn') }}"
            data-icon="warning"
            data-color="#28a745">
        <i class="la la-check"></i> {{ __('cms.date_change_approve_btn') }}
    </button>
@endif
