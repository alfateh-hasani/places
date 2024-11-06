<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number_of_booking' =>  $this->number_of_booking,
            'check_in' => $this->check_in?->format('Y-m-d'),
            'check_out' => $this->check_out?->format('Y-m-d'),
            'adults_count' => $this->adults_count,
            'children_count' => $this->children_count,
            'coupon_code' => $this->coupon_code,
            'discount' => number_format($this->discount, 2, '.', ''),
            'total_price' => $this->total_price,
            'final_price' => $this->final_price,
            'status_title' => __('api.booking_status_'.$this->status),
            'status' =>$this->status,
            'price_per_night' => $this->price_per_night,
            'number_of_nights' => $this->number_of_nights,
            'apartment_name' => $this->apartment?->{'name_' . app()->getLocale()},
            'building_name' => $this->apartment?->building?->{'name_' . app()->getLocale()},
            'city_name' => $this->apartment?->building?->city?->{'name_' . app()->getLocale()},
            'reviews' => $this->apartment?->reviews->count(),
            'ratings' => $this->apartment?->reviews?->avg('rating'),
            'image' => getImage($this->apartment, 'image'),
            'invoice' => url('pdf-test.pdf'),
        ];
    }
}
