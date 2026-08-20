{{-- إعادة محاولة استرداد فاشل — تفتح نافذة تحديد المبلغ (معبّأة بالمبلغ السابق، يمكن تغييره أو اختيار كامل). --}}
@if ($entry->status === 'failed')
    @php($max = (float) ($entry->booking?->final_price ?: $entry->amount))
    <button type="button" class="btn btn-xs btn-warning js-refund-btn"
            data-action="{{ url(config('backpack.base.route_prefix')) }}/refund/{{ $entry->getKey() }}/retry"
            data-number="{{ $entry->booking?->number_of_booking }}"
            data-max="{{ number_format($max, 2, '.', '') }}"
            data-current="{{ number_format((float) $entry->amount, 2, '.', '') }}">
        <i class="la la-redo"></i> إعادة محاولة
    </button>
@endif
