<?php

namespace App\Http\Controllers\Front;

use App\Models\Apartment;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;
use App\Http\Controllers\Controller;

class ApartmentsICSController extends Controller
{
    public function generateICS(Apartment $apartment)
    {
        // جلب الحجوزات المؤكدة (booked) لهذه الشقة
        $bookings = Booking::where('apartment_id', $apartment->id)
            ->wherein('status', ['approved','booked'])
            ->where('is_airbnb_booking', 0)
            ->get();

        $calendar = Calendar::create("Apartment {$apartment->id} Bookings");

        foreach ($bookings as $booking) {
            // دمج التاريخ مع الوقت للدخول
            $startDate = Carbon::parse($booking->check_in);
            if ($booking->check_in_time) {
                $startDate = $startDate->setTimeFromTimeString($booking->check_in_time->format('H:i:s'));
            }

            // دمج التاريخ مع الوقت للخروج
            $endDate = Carbon::parse($booking->check_out);
            if ($booking->check_out_time) {
                $endDate = $endDate->setTimeFromTimeString($booking->check_out_time->format('H:i:s'));
            }

            $event = Event::create("booking.{$booking->id}@places.co")
                ->uniqueIdentifier("#".$booking->id)
                ->startsAt($startDate)
                ->endsAt($endDate)
                ->description("Booking for {$booking->customer_full_name}, Email: {$booking->customer_email}")
                ->address("Apartment ".$apartment->name_en."");

            $calendar->event($event);
        }

        $icsContent = $calendar->get();

        return Response::make($icsContent, 200, [
            'Content-Type' => 'text/calendar',
            'Content-Disposition' => 'attachment; filename="apartment-'.$apartment->id.'-bookings.ics"',
        ]);
    }
}
