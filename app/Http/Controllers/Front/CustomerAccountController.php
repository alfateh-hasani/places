<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Apartment;
use App\Models\Booking;
use App\Models\Review;
use App\Traits\generateSeoTrait;
use Auth;
use Cache;
use Carbon\Carbon;
use Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class CustomerAccountController extends Controller
{
    use generateSeoTrait;
    public function profile()
    {
        $customer = Auth::guard('customer')->user();
        $seo_title = $customer->first_name.' '.$customer->last_name.' | '.__('site.seo_title');
        $seo_description = __('customer.account');
        $url = route('customer.account');
        $this->generateSeo($seo_title, $seo_description, $url);
        return view('customer.account', compact('customer'));
    }

    public function update(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email|unique:customers,email,' . $customer->id,
            'id_number' => 'required|unique:customers,id_number,' . $customer->id,

        ]);
       
        $customer->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'emergency_phone' => $request->emergency_phone,
            'id_number' => $request->id_number,
        ]);
          //josn response
        return response()->json(['success' => true, 'message' => __('customer.updated_successfully')]);
    }

    //getBooking
    public function getBooking()
    {
        $customer = Auth::guard('customer')->user();

        $allBookings = Booking::where('customer_id', $customer->id)->latest()->get();

        $now = now();
        $nextCheckoutThreshold = $now;

        $pastBookings = $allBookings->filter(function ($booking) use ($nextCheckoutThreshold) {
            return Carbon::parse($booking->check_out)->setTime( 12, 0, 0)->lessThanOrEqualTo($nextCheckoutThreshold);
        });

        $upcomingBookings = $allBookings->filter(function ($booking) use ($nextCheckoutThreshold) {
            return Carbon::parse($booking->check_out)->setTime( 12, 0, 0)->greaterThanOrEqualTo($nextCheckoutThreshold);
        });

        $data = [
            'past_bookings' => $pastBookings->values(),
            'upcoming_bookings' => $upcomingBookings->values(),
            'customer' => $customer,
            'total_bookings' => $allBookings->count(),
        ];

        $seo_title = __('customer.my_reservations') . ' | ' . __('site.seo_title');
        $seo_description = __('customer.my_reservations');
        $url = route('customer.booking');
        $this->generateSeo($seo_title, $seo_description, $url);

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

        $seo_title = __('customer.favorite') . ' | ' . __('site.seo_title');
        $seo_description = __('customer.favorite');
        $url = route('customer.favorite');
        $this->generateSeo($seo_title, $seo_description, $url);
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
        
        // استخدام method canBeCanceled() من Booking model
        $data['can_cancel'] = $data['booking']->canBeCanceled();
        
        // الحصول على عدد الساعات المطلوبة للإلغاء لعرضها في الواجهة
        $cancelBeforeHoursSetting = \DB::table('settings')->where('key', 'cancel_before_hours')->first();
        $data['cancel_before_hours'] = $cancelBeforeHoursSetting ? (int)$cancelBeforeHoursSetting->value : 24;

        // طلب تعديل تواريخ ما قبل القبول (بانتظار الدفع/المراجعة) لعرض إكمال الدفع أو الإلغاء.
        // بعد القبول (processing/applied) يُخفى — تسوية الفرق عملية داخلية لا تخص العميل.
        $data['date_change_request'] = \App\Models\DateChangeRequest::where('booking_id', $data['booking']->id)
            ->whereIn('status', \App\Enums\DateChangeStatus::customerVisibleValues())
            ->latest()
            ->first();

        // Get active passcode for this booking
        $data['active_passcode'] = $data['booking']->getActivePasscode();

        $seo_title = __('customer.booking_details'). ' #'  .$number_of_booking.  ' | ' . __('site.seo_title');
        $seo_description = __('customer.booking_details');
        $url = route('customer.booking.details', $number_of_booking);
        $this->generateSeo($seo_title, $seo_description, $url);
        return view('booking.details', $data);
    }

    public function printBookingDetails($number_of_booking)  {
        
        $user = Auth::guard('customer')->user();
        $data['booking'] = Booking::where([
            'number_of_booking' => $number_of_booking,
            'customer_id' => $user->id
        ])->firstOrFail();
        return view('booking.print',  $data);
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
            Cache::forget("total_ratings_{$apartment->id}");

            return response()->json(['success' => true, 'message' => __('apartment.review_added_successfully')]);
        }
    
        return response()->json(['success' => false, 'message' => __('apartment.review_added_failed')], 404);
    }
 
 
}
