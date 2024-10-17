<?php

namespace App\Services;
use App\Models\Coupon;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{

    public function validateCoupon($apartment, $couponCode)
    {
        if ($couponCode) {
            $coupon = Coupon::where('code', $couponCode)->first();
            if (!$coupon) {
                throw ValidationException::withMessages(['coupon_code' =>  __('api.coupon_invalid')]);
            }
            if (!$coupon->apartments->contains($apartment->id) &&
                !$coupon->buildings->contains($apartment->building_id)) {
                throw ValidationException::withMessages(['coupon_code' =>  __('api.coupon_invalid_apartment')]);
            }
            return $coupon;
        }
        return null;
    }

    //checkAvailability
    public function checkAvailability($apartment, $checkIn, $checkOut): void
    {
        $existingBooking = Reservation::where('apartment_id', $apartment->id)
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->whereBetween('check_in', [$checkIn, $checkOut])
                    ->orWhereBetween('check_out', [$checkIn, $checkOut])
                    ->orWhere(function ($query) use ($checkIn, $checkOut) {
                        $query->where('check_in', '<', $checkIn)
                            ->where('check_out', '>', $checkOut);
                    });
            })->exists();

        if ($existingBooking) {
            throw ValidationException::withMessages(['apartment_id' =>  __('api.already_booked')]);
        }
    }

    public function calculatePrices($apartmentPrice, $numberOfNights, $coupon = null): array
    {
        $totalPrice = $apartmentPrice * $numberOfNights;
        $discount = 0;
        if ($coupon) {
            if ($coupon->type === 'percentage') {
                $discount = ($coupon->value / 100) * $totalPrice;
            } elseif ($coupon->type === 'fixed') {
                $discount = $coupon->value;
            }
        }
        $finalPrice = $totalPrice - $discount;
        if ($finalPrice < 0) {
            $finalPrice = 0;
        }

        return [
            'total_price' => $totalPrice,
            'discount' => $discount,
            'final_price' => $finalPrice,
        ];
    }

    public function calculateNumberOfNights($checkIn, $checkOut)
    {
        return Carbon::parse($checkIn)->diffInNights(Carbon::parse($checkOut));
    }


    public function createBooking($validatedData, $customer, $apartment, $bookingSource)
    {
        DB::beginTransaction();
        try {
            $numberOfNights = $this->calculateNumberOfNights($validatedData['check_in'], $validatedData['check_out']);
            $coupon = $this->validateCoupon($apartment, $validatedData['coupon_id'] ?? null);
            $prices = $this->calculatePrices($apartment->price, $numberOfNights, $coupon);
            $booking = $apartment->bookings()->create(array_merge($validatedData, [
                'customer_id' => $customer->id,
                'number_of_nights' => $numberOfNights,
                'total_price' => $prices['total_price'],
                'discount' => $prices['discount'],
                'final_price' => $prices['final_price'],
                'booking_source' => $bookingSource,
            ]));
            DB::commit();
            return $booking;
        } catch (\Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }
}
