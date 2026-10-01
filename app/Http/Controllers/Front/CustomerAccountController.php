<?php

namespace App\Http\Controllers\Front;

use App\Enums\DateChangeStatus;
use App\Enums\UnitTransferStatus;
use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Booking;
use App\Models\BookingUnitTransfer;
use App\Models\Customer;
use App\Models\DateChangeRequest;
use App\Models\Review;
use App\Traits\generateSeoTrait;
use Auth;
use Cache;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
            'first_name' => ['required', 'string', "regex:/^[\\p{L}\\p{M}\\s'.\\-]+$/u", 'max:255'],
            'last_name' => ['required', 'string', "regex:/^[\\p{L}\\p{M}\\s'.\\-]+$/u", 'max:255'],
            'email' => Customer::emailValidationRules($customer->id),
            'id_number' => 'required|string|max:30|unique:customers,id_number,'.$customer->id,
            'emergency_phone' => ['nullable', 'string', 'max:25', 'regex:/^\+?[0-9\s\-()]+$/'],

        ]);

        $customer->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'emergency_phone' => $request->emergency_phone,
            'id_number' => $request->id_number,
        ]);

        // josn response
        return response()->json(['success' => true, 'message' => __('customer.updated_successfully')]);
    }

    // getBooking
    public function getBooking()
    {
        $customer = Auth::guard('customer')->user();

        $allBookings = Booking::where('customer_id', $customer->id)->latest()->get();

        $now = now();
        $nextCheckoutThreshold = $now;

        $pastBookings = $allBookings->filter(function ($booking) use ($nextCheckoutThreshold) {
            return Carbon::parse($booking->check_out)->setTime(12, 0, 0)->lessThanOrEqualTo($nextCheckoutThreshold);
        });

        $upcomingBookings = $allBookings->filter(function ($booking) use ($nextCheckoutThreshold) {
            return Carbon::parse($booking->check_out)->setTime(12, 0, 0)->greaterThanOrEqualTo($nextCheckoutThreshold);
        });

        $data = [
            'past_bookings' => $pastBookings->values(),
            'upcoming_bookings' => $upcomingBookings->values(),
            'customer' => $customer,
            'total_bookings' => $allBookings->count(),
        ];

        $seo_title = __('customer.my_reservations').' | '.__('site.seo_title');
        $seo_description = __('customer.my_reservations');
        $url = route('customer.booking');
        $this->generateSeo($seo_title, $seo_description, $url);

        return view('customer.booking', $data);
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

    // BookingDetails
    public function BookingDetails($number_of_booking)
    {
        $user = Auth::guard('customer')->user();
        $data['booking'] = Booking::where([
            'number_of_booking' => $number_of_booking,
            'customer_id' => $user->id,
        ])->firstOrFail();
        $data['has_review'] = Review::existsForBooking($user->id, $data['booking']->id);
        $data['review'] = Review::where([
            'booking_id' => $data['booking']->id,
            'customer_id' => $user->id,
        ])->first();

        // استخدام method canBeCanceled() من Booking model
        $data['can_cancel'] = $data['booking']->canBeCanceled();

        // الحصول على عدد الساعات المطلوبة للإلغاء لعرضها في الواجهة
        $cancelBeforeHoursSetting = \DB::table('settings')->where('key', 'cancel_before_hours')->first();
        $data['cancel_before_hours'] = $cancelBeforeHoursSetting ? (int) $cancelBeforeHoursSetting->value : 24;

        // طلب تعديل تواريخ ما قبل القبول (بانتظار الدفع/المراجعة) لعرض إكمال الدفع أو الإلغاء.
        // بعد القبول (processing/applied) يُخفى — تسوية الفرق عملية داخلية لا تخص العميل.
        $data['date_change_request'] = DateChangeRequest::where('booking_id', $data['booking']->id)
            ->whereIn('status', DateChangeStatus::customerVisibleValues())
            ->latest()
            ->first();

        // طلب نقل وحدة بانتظار تأكيد العميل (بدأه الموظف) — لعرض رسالة + زر التأكيد/الرفض.
        $data['unit_transfer'] = BookingUnitTransfer::with(['fromApartment', 'toApartment'])
            ->where('booking_id', $data['booking']->id)
            ->whereIn('status', UnitTransferStatus::openValues())
            ->latest()
            ->first();

        // Get active passcode for this booking
        $data['active_passcode'] = $data['booking']->getActivePasscode();

        $seo_title = __('customer.booking_details').' #'.$number_of_booking.' | '.__('site.seo_title');
        $seo_description = __('customer.booking_details');
        $url = route('customer.booking.details', $number_of_booking);
        $this->generateSeo($seo_title, $seo_description, $url);

        return view('booking.details', $data);
    }

    public function printBookingDetails($number_of_booking)
    {

        $user = Auth::guard('customer')->user();
        $data['booking'] = Booking::where([
            'number_of_booking' => $number_of_booking,
            'customer_id' => $user->id,
        ])->firstOrFail();

        return view('booking.print', $data);
    }

    // addReview
    public function addReview(Request $request)
    {
        $request->validate([
            'apartment_id' => 'nullable|integer',
            'rating' => 'required|integer|min:1|max:5',
            'review_text' => 'required|string|max:2000',
            'booking_id' => 'required|integer',
        ]);

        $customer = Auth::guard('customer')->user();

        // Only the guest of a finished stay may review it, and only that stay's apartment.
        $booking = Booking::reviewableBy($customer->id)->find($request->booking_id);
        if (! $booking) {
            return response()->json(['success' => false, 'message' => __('apartment.review_added_failed')], 404);
        }

        $apartment = $booking->apartment;
        if (Review::existsForBooking($customer->id, $booking->id)) {
            return response()->json(['success' => false, 'message' => __('apartment.review_already_exists')], 400);
        }
        if ($apartment) {
            $apartment->reviews()->create([
                'customer_id' => $customer->id,
                'rating' => $request->rating,
                'review_text' => $request->review_text,
                'booking_id' => $booking->id,
            ]);
            Cache::forget("total_ratings_{$apartment->id}");

            return response()->json(['success' => true, 'message' => __('apartment.review_added_successfully')]);
        }

        return response()->json(['success' => false, 'message' => __('apartment.review_added_failed')], 404);
    }
}
