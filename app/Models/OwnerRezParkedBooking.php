<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A booking we moved ("parked") to a dead past slot in OwnerRez because the API cannot
 * delete it. Awaits manual deletion in the OwnerRez UI; the daily purge command removes
 * this row once OwnerRez confirms the booking is gone (HTTP 404).
 */
class OwnerRezParkedBooking extends Model
{
    protected $table = 'ownerrez_parked_bookings';

    protected $fillable = [
        'ownerrez_booking_id',
        'ownerrez_property_id',
        'source_type',
        'source_id',
        'reason',
        'parked_arrival',
        'parked_departure',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'source_id' => 'integer',
            'parked_arrival' => 'date',
            'parked_departure' => 'date',
        ];
    }
}
