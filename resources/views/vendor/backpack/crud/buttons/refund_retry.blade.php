{{-- إعادة محاولة استرداد فاشل (آمن؛ لا يُكرّر الاسترداد إن تم فعلاً). --}}
@if ($entry->status === 'failed')
    <form method="POST" action="{{ url(config('backpack.base.route_prefix')) }}/refund/{{ $entry->getKey() }}/retry" style="display:inline;">
        @csrf
        <button type="submit" class="btn btn-xs btn-warning"
                onclick="return confirm('إعادة محاولة استرداد المبلغ عبر جيديا؟')">
            <i class="la la-redo"></i> إعادة محاولة
        </button>
    </form>
@endif
