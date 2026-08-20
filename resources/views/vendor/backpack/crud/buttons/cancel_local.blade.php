{{-- الخطوة 1 لوحدة غير مربوطة بـ OwnerRez: إلغاء محلي فقط (بدون استرداد — الاسترداد خطوة تالية). --}}
@if ($entry->status === 'customer_canceled' && $entry->refund_status === 'pending' && empty($entry->ownerrez_booking_id))
    <form method="POST" action="{{ url(config('backpack.base.route_prefix')) }}/canceled-bookings/{{ $entry->getKey() }}/cancel-local" style="display:inline;">
        @csrf
        <button type="submit" class="btn btn-xs btn-warning"
                onclick="return confirm('إلغاء الحجز محلياً؟ (بدون استرداد — يظهر بعدها زر الاسترداد)')">
            <i class="la la-ban"></i> إلغاء محلي
        </button>
    </form>
@endif
