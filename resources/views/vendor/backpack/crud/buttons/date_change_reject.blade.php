{{-- رفض/إلغاء طلب تعديل التواريخ — يبقى الحجز على تواريخه الأصلية (آمن: النافذة بقيت محجوزة).
     يشمل تجاوز الطلبات العالقة بانتظار الدفع التي لم يكملها العميل. --}}
@if (in_array($entry->status, ['pending_review', 'awaiting_payment', 'pending']))
    <button type="button" class="btn btn-xs btn-danger dc-action-btn"
            data-url="{{ url(config('backpack.base.route_prefix')) }}/date-change-requests/{{ $entry->getKey() }}/reject"
            data-title="{{ __('cms.date_change_reject_btn') }}"
            data-confirm="{{ __('cms.date_change_reject_confirm') }}"
            data-confirm-btn="{{ __('cms.date_change_reject_btn') }}"
            data-icon="warning"
            data-color="#d33"
            data-danger="1">
        <i class="la la-times"></i> {{ __('cms.date_change_reject_btn') }}
    </button>
@endif
