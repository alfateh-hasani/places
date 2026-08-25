{{-- نافذة سبب الحظر — تفتحها أزرار .js-block-customer-btn عبر data-action / data-number --}}
<div class="modal fade" id="blockCustomerModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="blockCustomerForm" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('cms.block_customer') }} — <span id="bc-number"></span></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-0">
                        <label for="bc-reason">{{ __('cms.block_reason') }}</label>
                        <textarea class="form-control" name="block_reason" id="bc-reason" rows="3" required></textarea>
                        <small class="text-muted">{{ __('cms.block_reason_hint') }}</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('cms.cancel') }}</button>
                    <button type="submit" class="btn btn-danger"><i class="la la-ban"></i> {{ __('cms.confirm_block') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('after_scripts')
    <script>
        jQuery(function ($) {
            $('#blockCustomerModal').appendTo('body');

            $(document).on('click', '.js-block-customer-btn', function () {
                $('#blockCustomerForm').attr('action', $(this).data('action'));
                $('#bc-number').text($(this).data('number'));
                $('#bc-reason').val('');
                $('#blockCustomerModal').modal('show');
            });
        });
    </script>
@endpush
