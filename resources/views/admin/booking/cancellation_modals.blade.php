{{-- Shared modals for the booking cancellation → refund flow. Populated per-row by
     the .js-manage-cancel-btn / .js-refund-btn buttons via data-* attributes. --}}

{{-- Refund amount modal (full/partial) — reused as-is for the refund step. --}}
@include('admin.refunds.refund_modal')

{{-- Guided cancellation-management modal (the "request under review" step). --}}
<div class="modal fade" id="cancellationModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header cm-header">
                <button type="button" class="close cm-close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h5 class="modal-title">{{ __('cms.manage_cancellation') }} — <span id="cm-number"></span></h5>
            </div>
            <div class="modal-body">
                {{-- Mapped to OwnerRez: cancel there; the webhook finalizes locally. The
                     local "force cancel" fallback lives on the booking detail page and only
                     appears once OwnerRez itself confirms the reservation is cancelled. --}}
                <div id="cm-mapped" style="display:none;">
                    <p class="mb-2">{{ __('cms.cancel_ownerrez_intro') }}</p>
                    <a href="#" target="_blank" rel="noopener" id="cm-ownerrez-link" class="btn btn-primary btn-block">
                        <i class="la la-external-link"></i> {{ __('cms.cancel_via_ownerrez') }}
                    </a>
                </div>

                {{-- Not mapped: a plain local cancellation frees the unit. --}}
                <div id="cm-unmapped" style="display:none;">
                    <p class="mb-2">{{ __('cms.cancel_local_intro') }}</p>
                    <form method="POST" id="cm-cancel-form">
                        @csrf
                        <button type="submit" class="btn btn-warning btn-block"
                                onclick="return confirm('{{ __('cms.cancel_local_confirm') }}')">
                            <i class="la la-ban"></i> {{ __('cms.confirm_cancel_local') }}
                        </button>
                    </form>
                </div>

                <hr>

                {{-- Reject the request → reinstate the booking (safe: the unit stayed held). --}}
                <form method="POST" id="cm-reject-form">
                    @csrf
                    <button type="submit" class="btn btn-link text-danger px-0"
                            onclick="return confirm('{{ __('cms.reject_cancel_confirm') }}')">
                        <i class="la la-times"></i> {{ __('cms.reject_cancellation') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('after_styles')
    <style>
        #cancellationModal .cm-header { position: relative; }
        #cancellationModal .cm-close {
            position: absolute;
            left: 14px;
            top: 12px;
            margin: 0;
            float: none;
        }
    </style>
@endpush

@push('after_scripts')
    <script>
        jQuery(function ($) {
            $('#cancellationModal').appendTo('body');

            $(document).on('click', '.js-manage-cancel-btn', function () {
                var mapped = String($(this).data('mapped')) === '1';

                $('#cm-number').text($(this).data('number'));
                $('#cm-cancel-form').attr('action', $(this).data('cancel-local-url'));
                $('#cm-reject-form').attr('action', $(this).data('reject-url'));
                $('#cm-ownerrez-link').attr('href', $(this).data('ownerrez-url') || '#');

                $('#cm-mapped').toggle(mapped);
                $('#cm-unmapped').toggle(!mapped);

                $('#cancellationModal').modal('show');
            });
        });
    </script>
@endpush
