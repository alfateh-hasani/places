@if ($entry->refund_status === 'pending')
    <form method="POST" action="{{ url(config('backpack.base.route_prefix')) }}/canceled-bookings/{{ $entry->getKey() }}/process-refund/reject" style="display:inline;">
        @csrf
        <button type="submit" class="btn btn-xs btn-danger" 
                onclick="return confirm('هل أنت متأكد من رفض الاسترداد؟')">
            <i class="la la-times"></i> رفض الاسترداد
        </button>
    </form>
@endif

