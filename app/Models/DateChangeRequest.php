<?php

namespace App\Models;

use App\Enums\DateChangeStatus;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DateChangeRequest extends Model
{
    use CrudTrait;

    protected $table = 'booking_date_change_requests';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'original_check_in' => 'date:Y-m-d',
            'original_check_out' => 'date:Y-m-d',
            'new_check_in' => 'date:Y-m-d',
            'new_check_out' => 'date:Y-m-d',
            'original_price' => 'decimal:2',
            'new_price' => 'decimal:2',
            'price_delta' => 'decimal:2',
            'response_payload' => 'array',
            'last_attempt_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'applied_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isSurcharge(): bool
    {
        return (float) $this->price_delta > 0;
    }

    public function isRefund(): bool
    {
        return (float) $this->price_delta < 0;
    }

    /** Absolute amount of the difference to refund (only meaningful when isRefund()). */
    public function refundableAmount(): float
    {
        return abs((float) $this->price_delta);
    }
}
