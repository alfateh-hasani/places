@if ($entry->refund_status === 'pending')
    <form method="POST" action="{{ url(config('backpack.base.route_prefix')) }}/canceled-bookings/{{ $entry->getKey() }}/process-refund/approve" style="display:inline;">
        @csrf
        <button type="submit" class="btn btn-xs btn-success" 
                onclick="return confirm('هل أنت متأكد من تأكيد الاسترداد؟ تأكد من أنك قمت بالاسترداد يدوياً في جيديا أولاً.')">
            <i class="la la-check"></i> تأكيد الاسترداد
        </button>
    </form>
@endif

