<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Apartment;
use App\Models\Booking;
use Auth;
use Illuminate\Http\Request;
class CustomerAccountController extends Controller
{
    public function profile()
    {
        $customer = auth()->user();
        return view('customer.account', compact('customer'));
    }

    public function update(Request $request)
    {
        $customer = auth()->user();
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email|unique:customers,email,' . $customer->id,

        ]);
       
        $customer->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'emergency_phone' => $request->emergency_phone,
        ]);
          //josn response
        return response()->json(['success' => true, 'message' => __('customer.updated_successfully')]);
    }

    //getBooking
    public function getBooking()
    {
        $customer = auth()->user();

        $allBookings = Booking::where('customer_id', $customer->id)->get();
        $pastBookings = $allBookings->filter(function ($booking) {
            return $booking->check_out < now();
        });
        $upcomingBookings = $allBookings->filter(function ($booking) {
            return $booking->check_out >= now();
        });
        $data = [
            'past_bookings' => $pastBookings->values(),
            'upcoming_bookings' => $upcomingBookings->values(),
            'customer' => $customer,
            'total_bookings' => $allBookings->count(),
        ];
        return view('customer.booking', $data);
    }

    //favorite

    public function favorite()
    {
        $customer = auth()->user();
        $favoriteApartments = $customer->favoriteApartments;
          $data = [
            'favorites' => $favoriteApartments,
            'customer' => $customer,
            'total_favorites' => $favoriteApartments->count(),
          ];
        return view('customer.favorite', $data);
    }

    public function notifications()
    {
        $customer = auth()->user();
        
        $data = [
            'notifications' => 'notifications',
            'customer' => $customer, 
            'total_notifications' => '56',
        ];
        return view('customer.notifications', $data);
    }


    //BookingDetails
    public function BookingDetails($number_of_booking )
    {
        $data['booking'] = Booking::where([
            'number_of_booking' => $number_of_booking,
            'customer_id' => auth()->id()
        ])->firstOrFail();
        return view('booking.details', $data);
    }


    //toggleFavorite
    public function toggleFavorite(Request $request)
    {
        $customer = auth()->user();
        $apartment = Apartment::find($request->apartment_id);
    
        if ($apartment) {
            $isFavorited = $customer->favoriteApartments()->toggle($apartment->id);
            $action = count($isFavorited['attached']) > 0 ? 'added' : 'removed';
    
            return response()->json([
                'success' => true,
                'action' => $action,
                'message' => __('apartment.favorite_' . $action)
            ]);
        }
    
        return response()->json(['success' => false, 'message' => __('apartment.favorite_failed')], 404);
    }
    
    
 
 
}
