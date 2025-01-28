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
            'check_in_time' => 'datetime',
            'check_out_time' => 'datetime',
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



    public function getChangeStatusButton()
    {
        $statuses = [
            'pending' => __('cms.status_pending'),
            'approved' => __('cms.status_approved'),
            'canceled' => __('cms.status_canceled'),
            'rejected' => __('cms.status_rejected'),
            'finished' => __('cms.status_finished'),
            'booked' => __('cms.status_booked'),
        ];
    
        $button = '<div class="btn-group">
                        <button type="button" class="btn btn-sm btn-info dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            ' . __('cms.change_status') . '
                        </button>
                        <div class="dropdown-menu">';
    
        foreach ($statuses as $status => $label) {
            $url = url("admin/booking/{$this->id}/change-status/{$status}");
            $button .= '<form method="POST" action="'.$url.'" style="display:inline;">
                            ' . csrf_field() . '
                            <button class="dropdown-item" type="submit">'.$label.'</button>
                        </form>';
        }
    
        $button .= '</div></div>';
    
        return $button;
    }
    
    
    public function getChangePaymentStatusButton()
    {
        $paymentStatuses = [
            'pending' => __('cms.payment_status_pending'),
            'paid' => __('cms.payment_status_paid'),
            'failed' => __('cms.payment_status_failed'),
        ];
    
        $button = '<div class="btn-group">
                        <button type="button" class="btn btn-sm btn-warning dropdown-toggle" 
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            ' . __('cms.change_payment_status') . '
                        </button>
                        <div class="dropdown-menu">';
    
        foreach ($paymentStatuses as $status => $label) {
            $url = url("admin/booking/{$this->id}/change-payment-status/{$status}");
            $button .= '<form method="POST" action="'.$url.'" style="display:inline;">
                            ' . csrf_field() . '
                            <button class="dropdown-item" type="submit">'.$label.'</button>
                        </form>';
        }
    
        $button .= '</div></div>';
    
        return $button;
    }
    
    

}
