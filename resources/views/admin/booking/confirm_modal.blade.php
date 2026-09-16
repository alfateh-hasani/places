{{-- تأكيد الحجز: يتحقّق من دفع Geidea أولاً، وإلا يعتمده كتحويل بنكي (رقم الحوالة + إيصال اختياري).
     نافذة مفردة تُعرض مرة واحدة؛ أزرار صفوف الجدول تحمل data-* وتفتحها. --}}
<div class="modal fade" id="confirmBookingModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="confirm-booking-form" method="POST" action="" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        {{ __('cms.confirm_booking_title') }} <span id="cb-number" class="text-muted"></span>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted" style="font-size:.9rem;">{{ __('cms.confirm_booking_note') }}</p>
                    <div class="form-group">
                        <label for="cb-transfer">
                            {{ __('cms.transfer_number') }} <small class="text-muted">({{ __('cms.optional') }})</small>
                        </label>
                        <input type="text" id="cb-transfer" name="transfer_number" class="form-control" maxlength="255" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="cb-receipt">
                            {{ __('cms.receipt_image') }} <small class="text-muted">({{ __('cms.optional') }})</small>
                        </label>
                        <input type="file" id="cb-receipt" name="receipt" class="form-control-file" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('cms.cancel') }}</button>
                    <button type="submit" class="btn btn-success"><i class="la la-check"></i> {{ __('cms.confirm_and_mark_paid') }}</button>
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
        var $modal = $('#confirmBookingModal');
        if ($modal.length && !$modal.data('moved')) { $modal.appendTo('body').data('moved', true); }

        // «تأكيد» — عبّئ رابط الإجراء ورقم الحجز ثم اعرض النافذة.
        $(document).on('click', '.js-confirm-btn', function () {
            var $btn = $(this);
            var form = document.getElementById('confirm-booking-form');
            form.reset();
            form.setAttribute('action', $btn.data('confirm-url'));
            $('#cb-number').text('#' + ($btn.data('number') || ''));
            $('#confirmBookingModal').modal('show');
        });
    })();
</script>
@endpush
