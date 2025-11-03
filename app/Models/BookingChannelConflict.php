<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class BookingChannelConflict extends Model
{
    use CrudTrait, LogsActivity;

    protected $fillable = [
        'apartment_id',
        'channel',
        'external_uid',
        'ext_check_in',
        'ext_check_out',
        'conflicting_booking_id',
        'context',
    ];

    protected $casts = [
        'ext_check_in' => 'date',
        'ext_check_out' => 'date',
        'context' => 'array',
    ];

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function conflictingBooking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'conflicting_booking_id');
    }

    public function getChannelLabelAttribute()
    {
        return match($this->channel) {
            'airbnb' => 'Airbnb',
            'booking' => 'Booking.com',
            'expedia' => 'Expedia',
            default => ucfirst($this->channel)
        };
    }

    public function getConflictStatusAttribute()
    {
        if ($this->conflicting_booking_id) {
            return 'Resolved';
        }
        return 'Active';
    }

    public function getConflictStatusBadgeAttribute()
    {
        return $this->conflict_status === 'Resolved' 
            ? '<span class="badge badge-success">Resolved</span>'
            : '<span class="badge badge-danger">Active</span>';
    }

    public function getDurationAttribute()
    {
        return $this->ext_check_in->diffInDays($this->ext_check_out);
    }

    public function getApartmentNameAttribute()
    {
        return $this->apartment?->ml('name') ?? 'N/A';
    }

    public function getBookingNumberAttribute()
    {
        return $this->conflictingBooking?->number_of_booking ?? 'N/A';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll();
    }
}
