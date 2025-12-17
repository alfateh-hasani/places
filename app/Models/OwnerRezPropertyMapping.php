<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class OwnerRezPropertyMapping extends Model
{
    use CrudTrait;
    use LogsActivity;


 

    protected $table = 'ownerrez_property_mappings';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'sync_enabled' => 'boolean',
            'check_availability_enabled' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function ownerrezBookings(): HasMany
    {
        return $this->hasMany(OwnerRezBooking::class, 'apartment_id', 'apartment_id');
    }

    public function isSyncEnabled(): bool
    {
        return $this->sync_enabled;
    }

    public function isAvailabilityCheckEnabled(): bool
    {
        return $this->check_availability_enabled;
    }

    public function markAsSynced(): void
    {
        $this->update(['last_synced_at' => now()]);
    }

    public function getActivitylogOptions(): LogOptions
    {
        // log all changes
        return LogOptions::defaults()
            ->logAll();

    }
}
