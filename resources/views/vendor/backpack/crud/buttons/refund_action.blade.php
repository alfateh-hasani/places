{{-- الخطوة 2: يظهر بعد الإلغاء (status=canceled) — يفتح نافذة تحديد مبلغ الاسترداد (كامل/جزئي). --}}
@if ($entry->status === 'canceled' && $entry->refund_status === 'pending')
    <button type="button" class="btn btn-xs btn-success js-refund-btn"
            data-action="{{ url(config('backpack.base.route_prefix')) }}/canceled-bookings/{{ $entry->getKey() }}/refund"
            data-number="{{ $entry->number_of_booking }}"
            data-max="{{ number_format((float) $entry->final_price, 2, '.', '') }}"
            data-current="{{ number_format((float) $entry->final_price, 2, '.', '') }}">
        <i class="la la-money-bill-wave"></i> استرداد المبلغ
    </button>
@endif
