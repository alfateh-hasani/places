{{-- موافقة واسترداد تلقائي — للوحدات غير المربوطة بـ OwnerRez فقط.
     الوحدات المربوطة تُقبل عبر إلغائها في OwnerRez (زر "إلغاء عبر OwnerRez"). --}}
@if ($entry->status === 'customer_canceled' && $entry->refund_status === 'pending' && empty($entry->ownerrez_booking_id))
    <form method="POST" action="{{ url(config('backpack.base.route_prefix')) }}/canceled-bookings/{{ $entry->getKey() }}/process-refund/approve" style="display:inline;">
        @csrf
        <button type="submit" class="btn btn-xs btn-success"
                onclick="return confirm('قبول الإلغاء واسترداد المبلغ للعميل تلقائياً عبر جيديا؟')">
            <i class="la la-check"></i> موافقة واسترداد
        </button>
    </form>
@endif
