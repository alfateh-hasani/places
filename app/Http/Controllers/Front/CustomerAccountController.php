<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Apartment;
use App\Models\Booking;
use App\Models\Review;
use Auth;
use Illuminate\Http\Request;
class CustomerAccountController extends Controller
{
    public function profile()
    {
        $customer = Auth::guard('customer')->user();
        
        return view('customer.account', compact('customer'));
    }

    public function update(Request $request)
    {
        $customer = Auth::guard('customer')->user();
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
        $customer = Auth::guard('customer')->user();

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
        $customer = Auth::guard('customer')->user();
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
        $customer = Auth::guard('customer')->user();
        
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
        $user = Auth::guard('customer')->user();
        $data['booking'] = Booking::where([
            'number_of_booking' => $number_of_booking,
            'customer_id' => $user->id
        ])->firstOrFail();
        $data['has_review'] =  Review::existsForBooking($user->id, $data['booking']->id);
        $data['review'] = Review::where([
            'booking_id' => $data['booking']->id,
            'customer_id' => $user->id
        ])->first();
        return view('booking.details', $data);
    }


    //toggleFavorite
    public function toggleFavorite(Request $request)
    {
        $customer = Auth::guard('customer')->user();
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
    
    

    //addReview
    public function addReview(Request $request)
    {
        $request->validate([
            'apartment_id' => 'required|exists:apartments,id',
            'rating' => 'required|numeric|min:1|max:5',
            'review_text' => 'required',
            'booking_id' => 'required|exists:bookings,id',
        ]);
    
        $customer = Auth::guard('customer')->user();   
        $apartment = Apartment::find($request->apartment_id);
        if (Review::existsForBooking($customer->id, $request->booking_id)) {
            return response()->json(['success' => false, 'message' => __('apartment.review_already_exists')], 400);
        }
        if ($apartment) {
            $apartment->reviews()->create([
                'customer_id' => $customer->id,
                'rating' => $request->rating,
                'review_text' => $request->review_text,
                'booking_id' => $request->booking_id,
            ]);
    
            return response()->json(['success' => true, 'message' => __('apartment.review_added_successfully')]);
        }
    
        return response()->json(['success' => false, 'message' => __('apartment.review_added_failed')], 404);
    }
 
 
}
