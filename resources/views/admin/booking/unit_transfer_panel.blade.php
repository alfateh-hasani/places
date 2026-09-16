@php
    use App\Enums\UnitTransferStatus;
    use App\Enums\TransferDirection;

    // Backpack's "view" widget passes everything under $widget (not as loose variables).
    $booking = $widget['booking'];
    /** @var \App\Models\BookingUnitTransfer $transfer */
    $transfer = $widget['transfer'];
    $transfers = $widget['transfers'] ?? collect();

    $status = $transfer->status;
    $direction = $transfer->direction;
    $deeplink = $transfer->from_ownerrez_booking_id
        ? rtrim(config('ownerrez.app_url'), '/').'/bookings/'.$transfer->from_ownerrez_booking_id
        : null;
@endphp

<div id="utPanel" class="card border-start mb-3" style="border-inline-start: 4px solid {{ $status->color() }};">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="la la-exchange-alt"></i> {{ __('cms.unit_transfer') }}</span>
        <span class="badge" style="background-color: {{ $status->color() }}; color:#fff;">{{ $status->label() }}</span>
    </div>
    <div class="card-body">
        <table class="table table-sm table-bordered mb-3">
            <tr>
                <th style="width: 35%;">{{ __('cms.transfer_from_to') }}</th>
                <td>
                    {{ optional($transfer->fromApartment)->name_ar ?? '#'.$transfer->from_apartment_id }}
                    <i class="la la-long-arrow-alt-left"></i>
                    <strong>{{ optional($transfer->toApartment)->name_ar ?? '#'.$transfer->to_apartment_id }}</strong>
                </td>
            </tr>
            <tr>
                <th>{{ __('cms.dates') }}</th>
                <td><bdo dir="ltr">{{ $transfer->check_in?->format('Y-m-d') }} → {{ $transfer->check_out?->format('Y-m-d') }}</bdo></td>
            </tr>
            <tr>
                <th>{{ __('cms.price') }}</th>
                <td>
                    {{ __('cms.transfer_original_price') }}: <bdo dir="ltr">{{ number_format((float) $transfer->original_price, 2) }}</bdo> —
                    {{ __('cms.transfer_new_price') }}: <bdo dir="ltr">{{ number_format((float) $transfer->new_price, 2) }}</bdo>
                    @if($direction === TransferDirection::Surcharge)
                        <span class="badge bg-info text-dark">{{ __('cms.transfer_absorbed_note') }}</span>
                    @elseif($direction === TransferDirection::Refund)
                        <span class="badge bg-warning text-dark">{{ __('cms.transfer_refund_due', ['amount' => number_format((float) $transfer->refund_amount, 2)]) }}</span>
                    @endif
                </td>
            </tr>
        </table>

        {{-- Pending customer confirmation --}}
        @if($status === UnitTransferStatus::PendingCustomer)
            <div class="alert alert-warning mb-2">{{ __('cms.transfer_awaiting_customer') }}</div>
            <form method="POST" action="{{ route('admin.unit-transfer.cancel', $transfer->id) }}"
                  data-confirm="{{ __('cms.transfer_cancel_confirm') }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="la la-times"></i> {{ __('cms.transfer_cancel_request') }}
                </button>
            </form>
        @endif

        {{-- Applied: manual OwnerRez cancel of the OLD booking + cheaper-unit refund --}}
        @if($status === UnitTransferStatus::Applied)
            @if(!empty($transfer->from_ownerrez_booking_id))
                @if($transfer->old_ownerrez_cancelled)
                    {{-- Old OwnerRez booking auto-freed (parked to a dead past slot). --}}
                    <div class="alert alert-success py-2 mb-3">
                        <i class="la la-check-circle"></i> {{ __('cms.transfer_old_ownerrez_parked') }}
                    </div>
                @else
                    {{-- Auto-park failed → manual deeplink fallback. --}}
                    <div class="alert alert-warning mb-2">
                        {{ __('cms.transfer_old_ownerrez_park_failed') }}
                    </div>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <form method="POST" action="{{ route('admin.unit-transfer.retry-park', $transfer->id) }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="la la-redo"></i> {{ __('cms.transfer_retry_auto_free') }}
                            </button>
                        </form>
                        <a href="{{ $deeplink }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                            <i class="la la-external-link-alt"></i> {{ __('cms.transfer_open_ownerrez') }}
                        </a>
                        <form method="POST" action="{{ route('admin.unit-transfer.mark-ownerrez-cancelled', $transfer->id) }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-success">
                                <i class="la la-check"></i> {{ __('cms.transfer_mark_old_cancelled') }}
                            </button>
                        </form>
                    </div>
                @endif
            @endif

            @if($transfer->needsRefund())
                <div class="border rounded p-2">
                    <div class="mb-2 fw-bold"><i class="la la-money-bill-wave"></i> {{ __('cms.transfer_refund_difference') }}</div>
                    @if($transfer->refund_status === \App\Models\BookingUnitTransfer::REFUND_PROCESSING)
                        <div class="alert alert-warning py-1 mb-2">{{ __('cms.refund_processing_flash') }}</div>
                    @elseif($transfer->refund_status === \App\Models\BookingUnitTransfer::REFUND_FAILED)
                        <div class="alert alert-danger py-1 mb-2">{{ __('cms.refund_failed') }}: {{ $transfer->error }}</div>
                    @endif
                    <form method="POST" action="{{ route('admin.unit-transfer.refund', $transfer->id) }}" class="row g-2 align-items-end"
                          data-confirm="{{ __('cms.transfer_refund_confirm') }}">
                        @csrf
                        <div class="col-auto">
                            <label class="form-label small mb-0">{{ __('cms.refund_amount') }}</label>
                            <input type="number" step="0.01" min="0.01" max="{{ $transfer->refundableAmount() }}"
                                   name="amount" value="{{ $transfer->refundableAmount() }}" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-sm btn-success">
                                <i class="la la-undo"></i> {{ __('cms.transfer_do_refund') }}
                            </button>
                        </div>
                    </form>
                </div>
            @elseif($direction === TransferDirection::Refund && $transfer->refund_status === \App\Models\BookingUnitTransfer::REFUND_APPROVED)
                <div class="alert alert-success py-1 mb-0">{{ __('cms.transfer_refund_done') }}</div>
            @endif
        @endif

        {{-- Failed apply → recoverable: retry or dismiss --}}
        @if($status === UnitTransferStatus::Failed)
            @if($transfer->error)
                <div class="alert alert-danger mb-2">{{ $transfer->error }}</div>
            @endif
            <div class="d-flex flex-wrap gap-2">
                <form method="POST" action="{{ route('admin.unit-transfer.retry', $transfer->id) }}"
                      data-confirm="{{ __('cms.transfer_retry_confirm') }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="la la-redo"></i> {{ __('cms.transfer_retry') }}
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.unit-transfer.cancel', $transfer->id) }}"
                      data-confirm="{{ __('cms.transfer_cancel_confirm') }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="la la-times"></i> {{ __('cms.transfer_dismiss') }}
                    </button>
                </form>
            </div>
        @endif

        {{-- Full transfer history for this booking --}}
        @if(!empty($transfers) && count($transfers) > 1)
            <hr>
            <div class="fw-bold mb-2"><i class="la la-history"></i> {{ __('cms.unit_transfer_history') }}</div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0 small">
                    <thead>
                        <tr>
                            <th>{{ __('cms.date') }}</th>
                            <th>{{ __('cms.transfer_from_to') }}</th>
                            <th>{{ __('cms.price') }}</th>
                            <th>{{ __('cms.status') }}</th>
                            <th>{{ __('cms.by') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transfers as $row)
                            <tr>
                                <td><bdo dir="ltr">{{ $row->created_at?->format('Y-m-d H:i') }}</bdo></td>
                                <td>
                                    {{ optional($row->fromApartment)->name_ar ?? '#'.$row->from_apartment_id }}
                                    <i class="la la-long-arrow-alt-left"></i>
                                    {{ optional($row->toApartment)->name_ar ?? '#'.$row->to_apartment_id }}
                                </td>
                                <td><bdo dir="ltr">{{ number_format((float) $row->new_price, 2) }}</bdo>
                                    @if((float) $row->price_delta != 0.0)
                                        <span class="text-muted">(<bdo dir="ltr">{{ (float) $row->price_delta > 0 ? '+' : '' }}{{ number_format((float) $row->price_delta, 2) }}</bdo>)</span>
                                    @endif
                                </td>
                                <td><span class="badge" style="background-color: {{ $row->status->color() }}; color:#fff;">{{ $row->status->label() }}</span></td>
                                <td>{{ optional($row->initiatedBy)->name ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<script>
    // Panel action forms (park/refund/cancel/retry): show a nice confirm dialog (SweetAlert when
    // available, else native), then disable the clicked button + show a spinner while the request
    // is in flight (these are full page POSTs) so it can't be double-submitted.
    (function () {
        var panel = document.getElementById('utPanel');
        if (! panel) { return; }

        function lockButton(btn) {
            if (! btn) { return; }
            btn.disabled = true;
            btn.style.opacity = '0.65';
            btn.style.pointerEvents = 'none';
            var icon = btn.querySelector('i');
            if (icon) { icon.className = 'la la-spinner la-spin'; }
        }

        panel.addEventListener('submit', function (e) {
            var form = e.target;
            var btn = e.submitter || form.querySelector('button[type="submit"]');
            var message = form.getAttribute('data-confirm');

            // Already confirmed (programmatic submit) or no confirmation needed → just lock + go.
            if (! message || form.dataset.utConfirmed === '1') {
                lockButton(btn);

                return;
            }

            e.preventDefault();

            var submitNow = function () {
                form.dataset.utConfirmed = '1';
                lockButton(btn);
                form.submit();
            };

            var CONFIRM = @json(__('cms.confirm'));
            var CANCEL = @json(__('cms.cancel'));

            // 1) SweetAlert2 (window.Swal.fire)
            if (window.Swal && typeof window.Swal.fire === 'function') {
                window.Swal.fire({
                    title: message,
                    icon: 'warning',
                    showCancelButton: true,
                    reverseButtons: true,
                    focusCancel: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: CONFIRM,
                    cancelButtonText: CANCEL,
                }).then(function (result) {
                    if (result && result.isConfirmed) { submitNow(); }
                });
            // 2) SweetAlert v2 (window.swal — bundled with Backpack)
            } else if (typeof window.swal === 'function') {
                window.swal({
                    title: message,
                    icon: 'warning',
                    buttons: [CANCEL, CONFIRM],
                    dangerMode: true,
                }).then(function (confirmed) {
                    if (confirmed) { submitNow(); }
                });
            // 3) Native fallback
            } else if (window.confirm(message)) {
                submitNow();
            }
        });
    })();
</script>
