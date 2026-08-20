@if ($entry->status === 'customer_canceled' && $entry->refund_status === 'pending')
    <form method="POST" action="{{ url(config('backpack.base.route_prefix')) }}/canceled-bookings/{{ $entry->getKey() }}/process-refund/reject" style="display:inline;">
        @csrf
        <button type="submit" class="btn btn-xs btn-danger"
                onclick="return confirm('هل أنت متأكد من رفض طلب الإلغاء وإعادة الحجز نشطاً؟')">
            <i class="la la-times"></i> رفض الطلب
        </button>
    </form>
@endif
