<?php

namespace App\Http\Resources;

use App\Models\Review;
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
            'apartment_id' => $this->apartment_id,
            'apartment_name' => $this->apartment?->{'name_' . app()->getLocale()},
            'building_name' => $this->apartment?->building?->{'name_' . app()->getLocale()},
            'check_in_time' => $this->check_in_time->format('h:i A'),
            'check_out_time' => $this->check_out_time->format('h:i A'),
            'city_name' => $this->apartment?->building?->city?->{'name_' . app()->getLocale()},
            'reviews' => $this->apartment?->reviews->count(),
            'ratings' => number_format($this->apartment?->reviews?->avg('rating') ?? 0, 1),
            'image' => getImage($this->apartment, 'image'),
            'invoice' => url('pdf-test.pdf'),
            'has_review' =>  Review::existsForBooking( $this->customer_id , $this->id),
            'review_avaliable' => now()->gt($this->check_out),
            'show_login_btn'  => (bool) $this->checkLoginInfoShow(),
            'can_cancel' => (bool) $this->canBeCanceled(),
            // معلومات الاسترداد
            'refund_status' => $this->refund_status,
            'refund_status_title' => $this->refund_status ? __('cms.refund_status_' . $this->refund_status) : null,
            'refund_amount' => $this->refund_amount ? number_format($this->refund_amount, 2, '.', '') : null,
            'refund_date' => $this->refund_date ? $this->refund_date->format('Y-m-d H:i') : null,

        ];
    }


    private function checkLoginInfoShow(): bool
    {
        // Ensure status is confirmed and both check-in and check-out times exist
        if ($this->status === 'approved' && $this->check_in && $this->check_out && $this->check_in_time && $this->check_out_time) {
            // Combine check-in date and time
            $checkInDateTime = $this->check_in->setTimeFromTimeString($this->check_in_time->format('H:i:s'));

            // Combine check-out date and time
            $checkOutDateTime = $this->check_out->setTimeFromTimeString($this->check_out_time->format('H:i:s'));

            // Check if the current time falls between the check-in and check-out times
            return now()->between($checkInDateTime, $checkOutDateTime);
        }

        return false;
    }


}
