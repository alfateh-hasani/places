{{-- تأكيد إلغاء الحجز — نافذة أنيقة بدل confirm() الأصلية. تُعرض مرة واحدة؛
     أزرار صفوف الجدول تحمل data-cancel-url وتفتحها. --}}
<div class="modal fade" id="cancelBookingModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="cancel-booking-form" method="POST" action="">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="la la-ban text-danger"></i>
                        {{ __('cms.cancel_booking_title') }} <span id="cx-number" class="text-muted"></span>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p style="font-size:.95rem;margin-bottom:0;">{{ __('cms.cancel_booking_confirm') }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('cms.back') }}</button>
                    <button type="submit" class="btn btn-danger"><i class="la la-ban"></i> {{ __('cms.confirm_cancel_btn') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('after_scripts')
<script>
    (function () {
        var $ = window.jQuery;
        if (!$) { return; }

        // انقل النافذة إلى body مرة واحدة حتى لا تُقصّها حاويات الجدول.
        var $modal = $('#cancelBookingModal');
        if ($modal.length && !$modal.data('moved')) { $modal.appendTo('body').data('moved', true); }

        // «إلغاء» — عبّئ رابط الإجراء ورقم الحجز ثم اعرض النافذة.
        $(document).on('click', '.js-cancel-btn', function () {
            var $btn = $(this);
            document.getElementById('cancel-booking-form').setAttribute('action', $btn.data('cancel-url'));
            $('#cx-number').text('#' + ($btn.data('number') || ''));
            $('#cancelBookingModal').modal('show');
        });
    })();
</script>
@endpush
