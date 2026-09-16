<?php

namespace App\Models;

use App\Enums\TransferDirection;
use App\Enums\UnitTransferStatus;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit + workflow record for moving a booking from one apartment to another.
 *
 * Created when staff initiate the move (status = PendingCustomer, destination held),
 * finalized when the customer confirms (status = Applied). It is the ONLY place the
 * old-apartment ↔ booking link survives, since the booking row itself only ever shows
 * its current apartment.
 *
 * The `refund_status` / gateway columns are used only for the "cheaper destination"
 * path, where staff partially refund the difference after the move — kept on this
 * record (not the booking's refund_* columns) so a transfer refund never collides with
 * a cancellation refund on the same transaction (mirrors DateChangeRequest).
 */
class BookingUnitTransfer extends Model
{
    use CrudTrait;

    protected $guarded = [];

    /** Refund states for the cheaper-unit difference (staff-driven, gateway-backed). */
    public const REFUND_PENDING = 'pending';

    public const REFUND_PROCESSING = 'processing';

    public const REFUND_APPROVED = 'approved';

    public const REFUND_FAILED = 'failed';

    protected function casts(): array
    {
        return [
            'check_in' => 'date:Y-m-d',
            'check_out' => 'date:Y-m-d',
            'original_price' => 'decimal:2',
            'new_price' => 'decimal:2',
            'price_delta' => 'decimal:2',
            'refund_amount' => 'decimal:2',
            'direction' => TransferDirection::class,
            'status' => UnitTransferStatus::class,
            'response_payload' => 'array',
            'last_attempt_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'applied_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function fromApartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class, 'from_apartment_id');
    }

    public function toApartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class, 'to_apartment_id');
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function isOpen(): bool
    {
        return $this->status instanceof UnitTransferStatus && $this->status->isOpen();
    }

    /** A cheaper-unit move whose difference refund is still owed / retryable. */
    public function needsRefund(): bool
    {
        return $this->status === UnitTransferStatus::Applied
            && $this->direction === TransferDirection::Refund
            && in_array($this->refund_status, [self::REFUND_PENDING, self::REFUND_FAILED, self::REFUND_PROCESSING], true);
    }

    /** Absolute difference to refund the customer for a cheaper destination. */
    public function refundableAmount(): float
    {
        return round((float) $this->refund_amount, 2);
    }

    /**
     * An applied move whose OLD OwnerRez booking still has to be cancelled by hand in the
     * OwnerRez UI (there is no cancel API). Drives the dashboard deeplink button.
     */
    public function needsManualOwnerRezCancel(): bool
    {
        return $this->status === UnitTransferStatus::Applied
            && ! empty($this->from_ownerrez_booking_id)
            && ! $this->old_ownerrez_cancelled;
    }
}
