<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OwnerRezBooking extends Model
{
    use CrudTrait;

    protected $table = 'ownerrez_bookings';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'raw_data' => 'array',
            'synced_at' => 'datetime',
        ];
    }

    public function localBooking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'local_booking_id');
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function markAsSynced(): void
    {
        $this->update([
            'sync_status' => 'synced',
            'synced_at' => now(),
            'error_message' => null,
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'sync_status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    public function scopeInbound($query)
    {
        return $query->where('sync_direction', 'inbound');
    }

    public function scopeOutbound($query)
    {
        return $query->where('sync_direction', 'outbound');
    }

    public function scopePending($query)
    {
        return $query->where('sync_status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('sync_status', 'failed');
    }
}
