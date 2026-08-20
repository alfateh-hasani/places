<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    use CrudTrait;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUS_FAILED = 'failed';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'request_payload' => 'array',
            'response_payload' => 'array',
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

    /**
     * Whether a refund request was already successfully issued to the gateway.
     * Used to guarantee we never call the refund endpoint twice for one transaction.
     */
    public function wasIssued(): bool
    {
        return (bool) data_get($this->response_payload, 'success') === true
            || in_array($this->status, [self::STATUS_PROCESSING, self::STATUS_REFUNDED], true);
    }
}
