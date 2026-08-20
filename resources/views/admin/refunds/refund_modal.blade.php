{{-- نافذة تحديد مبلغ الاسترداد. الاسترداد الجزئي يظهر فقط إذا كان مفعّلاً في الإعدادات
     (config: payments.gateways.geidea.partial_refund). عند تعطيله يكون الاسترداد كاملاً فقط.
     الأزرار (class="js-refund-btn") تفتحها عبر: data-action / data-number / data-max / data-current --}}
@php($partialEnabled = (bool) config('payments.gateways.geidea.partial_refund'))
<div class="modal fade" id="refundModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="refundForm" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('cms.refund_modal_title') }} — <span id="rf-number"></span></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">{{ __('cms.refund_full_amount') }}: <strong><span id="rf-max"></span> SAR</strong></p>

                    @if ($partialEnabled)
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="rf-mode" id="rf-full" value="full">
                            <label class="form-check-label" for="rf-full">{{ __('cms.refund_full') }}</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="rf-mode" id="rf-partial" value="partial">
                            <label class="form-check-label" for="rf-partial">{{ __('cms.refund_partial') }}</label>
                        </div>
                        <div class="form-group mb-0">
                            <label for="rf-amount">{{ __('cms.refund_amount') }} (SAR)</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" name="amount" id="rf-amount" readonly required>
                            <small class="text-muted">{{ __('cms.refund_amount_hint') }}</small>
                        </div>
                    @else
                        <div class="form-group mb-0">
                            <label for="rf-amount">{{ __('cms.refund_amount') }} (SAR)</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" name="amount" id="rf-amount" readonly required>
                            <small class="text-warning">{{ __('cms.partial_refund_disabled') }}</small>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('cms.cancel') }}</button>
                    <button type="submit" class="btn btn-success"><i class="la la-check"></i> {{ __('cms.confirm_refund') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('after_scripts')
    <script>
        jQuery(function ($) {
            $('#refundModal').appendTo('body');

            $(document).on('click', '.js-refund-btn', function () {
                var max = parseFloat($(this).data('max')) || 0;
                var current = parseFloat($(this).data('current'));
                if (isNaN(current) || current <= 0 || current > max) { current = max; }

                $('#refundForm').attr('action', $(this).data('action'));
                $('#rf-number').text($(this).data('number'));
                $('#rf-max').text(max.toFixed(2));

                if ($('#rf-partial').length) {
                    var isFull = current >= max;
                    $('#rf-full').prop('checked', isFull);
                    $('#rf-partial').prop('checked', !isFull);
                    $('#rf-amount').attr('max', max).val(current.toFixed(2)).prop('readonly', isFull);
                } else {
                    // Partial disabled → full only.
                    $('#rf-amount').attr('max', max).val(max.toFixed(2)).prop('readonly', true);
                }

                $('#refundModal').modal('show');
            });

            $(document).on('change', 'input[name=rf-mode]', function () {
                var partial = $('#rf-partial').is(':checked');
                var max = parseFloat($('#rf-amount').attr('max')) || 0;
                $('#rf-amount').prop('readonly', !partial);
                if (partial) {
                    $('#rf-amount').focus();
                } else {
                    $('#rf-amount').val(max.toFixed(2));
                }
            });
        });
    </script>
@endpush
