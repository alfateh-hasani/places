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
            ->get();

        $calendar = Calendar::create("Apartment {$apartment->id} Bookings");

        foreach ($bookings as $booking) {
            $startDate = Carbon::parse($booking->check_in);
            $endDate = Carbon::parse($booking->check_out);

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
