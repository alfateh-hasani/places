{{-- قبول الإلغاء لوحدة مربوطة بـ OwnerRez: يفتح الحجز في OwnerRez ليُلغيه الموظف،
     فيصل الإشعار (webhook) ويُنهي الإلغاء ويسترد المبلغ تلقائياً. --}}
@if (! empty($entry->ownerrez_booking_id) && $entry->status === 'customer_canceled' && $entry->refund_status === 'pending')
    <a href="{{ rtrim(config('ownerrez.app_url'), '/') }}/bookings/{{ $entry->ownerrez_booking_id }}"
       target="_blank" rel="noopener"
       class="btn btn-xs btn-primary"
       title="افتح الحجز في OwnerRez لإلغائه — سيصل الإشعار ويُنهي الإلغاء ويسترد المبلغ تلقائياً">
        <i class="la la-external-link"></i> إلغاء عبر OwnerRez
    </a>
@endif
