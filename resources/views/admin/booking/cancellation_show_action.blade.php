{{-- Detail-page cancellation action bar. Only renders when the booking needs an
     action, so it stays invisible for normal bookings. --}}
@php($booking = $widget['booking'])
@php($ownerrezCanceled = $widget['ownerrezCanceled'] ?? false)
@php($isRequest = $booking->status === \App\Enums\BookingStatus::CancellationRequested->value)
@if (
    ($isRequest || $booking->status === \App\Enums\BookingStatus::Canceled->value)
    && $booking->refund_status === 'pending'
)
    <div class="card mb-2">
        <div class="card-body py-2 d-flex align-items-center justify-content-between flex-wrap" style="gap:.5rem;">
            <span class="font-weight-bold">
                <i class="la la-exclamation-circle text-warning"></i> {{ __('cms.cancellation_needs_action_hint') }}
            </span>
            <span class="d-flex align-items-center" style="gap:.5rem;">
                @include('admin.booking.partials.manage_cancellation_button', ['booking' => $booking])

                {{-- Force cancel locally — shown only once OwnerRez itself confirms the
                     reservation is cancelled/deleted (the webhook was missed). --}}
                @if ($ownerrezCanceled && $isRequest)
                    <form method="POST"
                          action="{{ url(config('backpack.base.route_prefix').'/booking/'.$booking->getKey().'/cancel-local') }}"
                          id="force-cancel-form-{{ $booking->getKey() }}" style="display:inline;">
                        @csrf
                        <button type="button" class="btn btn-sm btn-outline-danger js-force-cancel-btn"
                                data-form="force-cancel-form-{{ $booking->getKey() }}"
                                data-title="{{ __('cms.force_cancel_confirm') }}"
                                data-text="{{ __('cms.force_cancel_warning') }}"
                                data-confirm="{{ __('cms.force_cancel_local') }}"
                                data-dismiss-text="{{ __('cms.dismiss') }}">
                            <i class="la la-unlink"></i> {{ __('cms.force_cancel_local') }}
                        </button>
                    </form>
                @endif
            </span>
        </div>
        @if ($ownerrezCanceled && $isRequest)
            <div class="card-footer py-1 small text-muted">
                <i class="la la-check-circle text-success"></i> {{ __('cms.ownerrez_confirmed_canceled_hint') }}
            </div>
        @endif
    </div>
@endif

@push('after_scripts')
    <script>
        jQuery(function ($) {
            $(document).on('click', '.js-force-cancel-btn', function () {
                var $btn = $(this);
                var form = document.getElementById($btn.data('form'));
                if (! form) { return; }

                // Graceful fallback if SweetAlert isn't available for any reason.
                if (typeof Swal === 'undefined') {
                    if (window.confirm($btn.data('title'))) { form.submit(); }
                    return;
                }

                Swal.fire({
                    title: $btn.data('title'),
                    text: $btn.data('text'),
                    icon: 'warning',
                    showCancelButton: true,
                    reverseButtons: true,
                    focusCancel: true,
                    confirmButtonText: $btn.data('confirm'),
                    cancelButtonText: $btn.data('dismiss-text'),
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                }).then(function (result) {
                    if (result.isConfirmed) { form.submit(); }
                });
            });
        });
    </script>
@endpush
