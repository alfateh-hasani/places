<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    use CrudTrait;
    protected $guarded = [];
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    protected function casts(): array
    {
        return [
            'check_in' => 'date:Y-m-d',
            'check_out' => 'date:Y-m-d',
            'discount' => 'float',
        ];
    }
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($booking) {
            do {
                $randomNumber = mt_rand(0, 999999);
                $bookingNumber = '00' . str_pad($randomNumber, 6, '0', STR_PAD_LEFT);
            } while (Booking::where('number_of_booking', $bookingNumber)->exists());
            $booking->number_of_booking = $bookingNumber;
        });
    }

    //coupon

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    //price_per_night
    public function getPricePerNightAttribute()
    {
        return $this->total_price / $this->number_of_nights;
    }


}
