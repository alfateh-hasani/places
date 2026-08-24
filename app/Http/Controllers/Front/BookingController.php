<?php

namespace App\Http\Controllers\Front;

use App\Enums\DateChangeStatus;
use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Booking;
use App\Models\Building;
use App\Models\DateChangeRequest;
use App\Models\Policy;
use App\Services\BookingService;
use App\Services\DateChangeService;
use App\Services\Pricing\PricingService;
use App\Services\ProcessPaymentService;
use App\Traits\generateSeoTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Mail;

class BookingController extends Controller
{
    protected $booking;

    protected $bookingService;

    protected $pricing;

    use generateSeoTrait;

    public function __construct(BookingService $bookingService, Booking $booking, PricingService $pricing)
    {
        $this->booking = $booking;
        $this->bookingService = $bookingService;
        $this->pricing = $pricing;
    }

    public function startPayment(Request $request, $uuid)
    {

        $booking = Booking::where('uuid', $uuid)->first();
        if (! $booking) {
            abort(404);
        }

        // dd($request->all());

        $validatedData = $request->validate([
            // 'coupon_code' => 'nullable|exists:coupons,code',
            'payment_method_code' => 'required|in:geidea',
        ]);

        $booking->update(['payment_method_code' => $validatedData['payment_method_code']]);

        try {

            $paymentResponse = $this->bookingService->createPaymentV2($booking, $validatedData);

            if (is_array($paymentResponse) && isset($paymentResponse['transaction']['url'])) {
                return redirect($paymentResponse['transaction']['url']);
            } else {
                return redirect()->back()->with('error', $paymentResponse);
            }
        } catch (\Exception $exception) {
            //  dd($exception->getMessage());
            return redirect()->back()->with('error', $exception->getMessage());
        }
    }

    public function paymentMethodCallBack(Request $request, $paymentMethodCode, $transaction_id)
    {
        if (! in_array($paymentMethodCode, array_keys(config('payments.gateways')))) {
            return redirect()->back()->with('error', __('api.payment_method_not_supported'));
        }

        $booking = $this->booking->where('transaction_id', $transaction_id)->first();

        if (! $booking) {
            return redirect()->back()->with('error', __('api.booking_not_found'));
        }

        // الـ webhook من Geidea هو المسؤول عن تأكيد الحجز
        // هنا فقط نتحقق من الحالة ونعرض النتيجة للعميل
        if ($booking->status === 'approved' && $booking->payment_status === 'paid') {
            return $this->bookingComplated($booking);
        }

        // إذا الـ webhook لم يصل بعد، نتحقق يدوياً من Geidea API
        $processPaymentService = new ProcessPaymentService;
        $data = $request->all();
        $data['transaction_id'] = $transaction_id;
        $handlePayment = $processPaymentService->handleCallBack($paymentMethodCode, $data);

        if ($handlePayment['status'] == true) {
            $this->bookingService->completeBookingAfterPayment($transaction_id);
            $booking->refresh();

            return $this->bookingComplated($booking);
        }

        // لا نلغي الحجز هنا - الـ webhook قد يصل لاحقاً ويأكده
        return redirect()->route('customer.booking.details', [$booking->number_of_booking])
            ->with('error', __('api.payment_failed'));
    }

    private function bookingComplated($booking)
    {

        try {

            if ($booking->customer_email) {
                Mail::to($booking->customer_email)->send(new \App\Mail\ReservationDetails($booking));
            }
        } catch (\Exception $e) {
            // dd($e->getMessage());
        }

        $building = Building::where('id', $booking?->apartment?->building_id)->first();
        if ($building) {
            $superVisor = \App\Models\User::where('id', $building->supervisor_id)->first();
            $superVisorEmail = $superVisor->email;
            if ($superVisorEmail) {
                Mail::to($superVisorEmail)->send(new \App\Mail\ReservationDetails($booking));
            }

        }

        return redirect()->route('customer.booking.details', [$booking->number_of_booking, 'showPopup' => '1']);
    }

    // paymentMethodSuccess
    public function paymentMethodSuccess($booking_id)
    {
        $data['booking'] = $this->booking->find($booking_id);

        return view('booking.details', $data);
    }

    public function couponsVerify(Request $request, $uuid)
    {
        $request->validate([
            'code' => 'required|string|exists:coupons,code',
        ]);

        // جلب الحجز مع معلومات الشقة
        $booking = Booking::where('uuid', $uuid)->with('apartment')->firstOrFail();
        $apartment = $booking->apartment;

        // التحقق من صحة الكوبون
        $coupon = $this->bookingService->validateCoupon($apartment, $request->code);

        // حساب الأسعار الجديدة مع الضريبة باستخدام نظام التسعير الجديد
        $prices = $this->bookingService->calculatePricesWithDates($apartment, $booking->check_in, $booking->check_out, $coupon);

        // حساب سعر الليلة الواحدة
        $oneNightPrice = $booking->number_of_nights > 0 ? $prices['total_price'] / $booking->number_of_nights : 0;

        // تحديث بيانات الحجز في قاعدة البيانات
        $booking->update([
            'coupon_id' => $coupon->id ?? null,
            'coupon_code' => $coupon->code ?? null,
            'discount' => $prices['discount'],
            'tax' => $prices['vat'],
            'total_price' => $prices['total_price'], // السعر الكلي قبل الخصم (شامل الضريبة)
            'final_price' => $prices['final_price'], // السعر النهائي بعد الخصم (شامل الضريبة)
            'one_night_price' => $oneNightPrice, // سعر الليلة الواحدة
        ]);

        return response()->json($prices);
    }

    public function removeCoupon(Request $request, $uuid)
    {

        $customer = auth()->user();
        $booking = Booking::where('uuid', $uuid)->where('customer_id', $customer->id)->firstOrFail();

        $apartment = $booking->apartment;

        // حساب الأسعار باستخدام نظام التسعير الجديد بدون كوبون
        $prices = $this->bookingService->calculatePricesWithDates($apartment, $booking->check_in, $booking->check_out);

        // حساب سعر الليلة الواحدة
        $oneNightPrice = $booking->number_of_nights > 0 ? $prices['total_price'] / $booking->number_of_nights : 0;

        $booking->update([
            'coupon_id' => null,
            'coupon_code' => null,
            'discount' => 0,
            'tax' => $prices['vat'],
            'total_price' => $prices['total_price'], // السعر الكلي قبل الخصم (شامل الضريبة)
            'final_price' => $prices['final_price'], // السعر النهائي بعد الخصم (شامل الضريبة)
            'one_night_price' => $oneNightPrice, // سعر الليلة الواحدة
        ]);

        return response()->json([
            'success' => true,
            'message' => __('apartment.coupon_removed'),
            'final_price' => number_format($booking->final_price, 2, '.', ''),
            'vat' => number_format($booking->tax, 2, '.', ''),
        ]);
    }

    public function determineBookingStatus(Request $request, $apartment_id)
    {
        $validatedData = $request->validate([
            'checkin' => ['required', 'date', 'after_or_equal:today'],
            'checkout' => ['required', 'date', 'after:checkin'],
            'number_of_adults' => ['required', 'integer', 'min:1', 'max:10'],
            'number_of_children' => ['required', 'integer', 'min:0', 'max:10'],
            'coupon_code' => ['nullable', 'string'],
        ], __('validation.custom'));

        \DB::beginTransaction();

        try {

            // dd($validatedData);
            // جلب بيانات الشقة المطلوبة
            $apartment = Apartment::findOrFail($apartment_id);

            // التحقق من توفر الشقة
            $this->bookingService->checkAvailability($apartment, $validatedData['checkin'], $validatedData['checkout']);

            // التحقق من عدد الضيوف
            $this->bookingService->validateGuestsCount($apartment, $validatedData['number_of_adults'], $validatedData['number_of_children']);

            // تحويل التواريخ إلى Carbon
            $checkInDate = Carbon::parse($validatedData['checkin']);
            $checkOutDate = Carbon::parse($validatedData['checkout']);

            // حساب عدد الليالي
            $number_of_nights = $checkInDate->diffInDays($checkOutDate);

            // استخدام PricingService للحصول على السعر الصحيح (شامل الضريبة)
            $priceInfo = $this->pricing->calculate($apartment, $checkInDate, $checkOutDate);

            $building = Building::where('id', $apartment->building_id)->first();

            // التحقق من الكوبون إذا كان موجودًا
            $coupon = null;
            $discount = 0;

            // السعر من PricingService شامل الضريبة
            $totalPriceWithVat = $priceInfo['total'];

            // استخراج الضريبة من السعر
            $vatRate = 15;
            $tax_amount = round($totalPriceWithVat * $vatRate / (100 + $vatRate), 2);
            $total_price = round($totalPriceWithVat - $tax_amount, 2);

            // حساب السعر النهائي بعد الخصم (إذا كان هناك كوبون)
            $finalPriceWithVat = $totalPriceWithVat; // بدون خصم في البداية
            if ($coupon) {
                // تطبيق خصم الكوبون
                $couponDiscount = $coupon->type === 'percentage'
                    ? round(($coupon->discount / 100) * $totalPriceWithVat, 2)
                    : min($coupon->discount, $totalPriceWithVat);
                $finalPriceWithVat = max($totalPriceWithVat - $couponDiscount, 0);
                $discount = $couponDiscount;
            }

            // حساب سعر الليلة الواحدة
            $oneNightPrice = $number_of_nights > 0 ? $totalPriceWithVat / $number_of_nights : 0;

            // إنشاء حجز مؤقت بحالة `pending`
            $booking = Booking::create([
                'apartment_id' => $apartment->id,
                'customer_id' => auth()->id(),
                'customer_full_name' => auth()->user()->full_name,
                'customer_email' => auth()->user()->email,
                'check_in' => $checkInDate,
                'check_out' => $checkOutDate,
                'number_of_nights' => $number_of_nights,
                'adults_count' => $validatedData['number_of_adults'],
                'children_count' => $validatedData['number_of_children'],
                'status' => 'pending',
                'total_price' => $totalPriceWithVat,  // السعر الكلي قبل الخصم (شامل الضريبة)
                'discount' => $discount,
                'final_price' => $finalPriceWithVat,   // السعر النهائي بعد الخصم (شامل الضريبة)
                'one_night_price' => $oneNightPrice,       // سعر الليلة الواحدة
                'tax' => $tax_amount,
                'payment_status' => 'pending',
                'coupon_id' => $coupon ? $coupon->id : null,
                'coupon_code' => $coupon ? $coupon->code : null,
                'booking_source' => $request->header('User-Agent') ? 'web' : 'android',
                'payment_method_code' => $request->payment_method ?? null,
                'check_in_time' => $building->check_in_time,
                'check_out_time' => $building->check_out_time,
            ]);

            \DB::commit();

            // إعادة توجيه العميل إلى صفحة تأكيد الحجز
            return redirect()->route('web-booking.confirm-booking', ['uuid' => $booking->uuid]);
        } catch (ValidationException $e) {
            // dd($e->getMessage());
            \DB::rollBack();

            return redirect()->route('apartments.show', $apartment->slug)
                ->withErrors($e->errors())
                ->withInput();
        } catch (Exception $e) {

            // dd($e->getMessage());
            \DB::rollBack();

            return redirect()->route('apartments.show', $apartment->slug)
                ->withErrors(['error' => __('api.general_error')])
                ->withInput();
        }
    }

    public function confirmBooking($uuid)
    {
        $booking = Booking::with('apartment')->where('uuid', $uuid)->first();

        if (! $booking) {
            abort(404);
        }
        $payment_methods = Policy::where('type', 'booking')->first();
        $data['policy_title'] = $payment_methods?->{'name_'.app()->getLocale()};
        $data['policy_description'] = $payment_methods?->{'description_'.app()->getLocale()};
        $priceInfo = $this->pricing->calculate($booking->apartment, $booking->check_in, $booking->check_out);

        return view('booking.confirm-booking', [
            'booking' => $booking,
            'apartment' => $booking->apartment,
            'payment_methods' => Policy::where('type', 'booking')->first(),
            'payment_details' => $this->getPaymentDetails(),
            'policy_title' => $data['policy_title'],
            'policy_description' => $data['policy_description'],
            'priceInfo' => $priceInfo,
        ]);
    }

    private function getPaymentDetails()
    {
        $paymentMethods = [];
        foreach (config('payments.gateways') as $gateway => $gatewayData) {
            $paymentMethods[] = [
                'name' => $gateway,
                'icon' => asset('assets/payments/'.$gatewayData['icon']),
                'value' => $gatewayData['value'],
                'explanation' => $gatewayData['explanation'],
            ];
        }

        return $paymentMethods;
    }

    public function cancelBooking(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
        ]);

        $customer = auth()->user();
        $booking = $this->booking->where([
            ['id', $request->booking_id],
            ['customer_id', $customer->id],
        ])->first();

        if (! $booking) {
            return redirect()->back()->with('error', __('api.booking_not_found'));
        }

        // التحقق من إمكانية إلغاء الحجز
        if (! $booking->canBeCanceled()) {
            return redirect()->back()->with('error', __('api.booking_cannot_be_canceled'));
        }

        // تحديث حالة الحجز إلى customer_canceled وrefund_status إلى pending
        $booking->status = 'customer_canceled';
        $booking->refund_status = 'pending';
        $booking->refund_amount = $booking->final_price;
        $booking->save();

        // إرسال إيميل للمشرف عند الإلغاء
        try {
            $building = Building::where('id', $booking?->apartment?->building_id)->first();
            if ($building) {
                $superVisor = \App\Models\User::where('id', $building->supervisor_id)->first();
                $superVisorEmail = $superVisor?->email;
                if ($superVisorEmail) {
                    Mail::to($superVisorEmail)->send(new \App\Mail\BookingCanceled($booking));
                }
            }
        } catch (\Exception $e) {
            // تجاهل أخطاء الإيميل لتجنب فشل عملية الإلغاء
            \Log::error('فشل في إرسال إيميل الإلغاء للمشرف: '.$e->getMessage());
        }

        return redirect()->back()->with('success', __('api.booking_canceled_successfully'));
    }

    /**
     * Quote a date change for the customer's booking: availability of the new range + price delta.
     */
    public function calculateDateChange(Request $request, DateChangeService $dateChangeService)
    {
        $validated = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'new_check_in' => 'required|date',
            'new_check_out' => 'required|date|after:new_check_in',
        ]);

        $booking = $this->customerBooking($validated['booking_id']);
        if (! $booking) {
            return response()->json(['success' => false, 'message' => __('api.booking_not_found')], 404);
        }

        try {
            $quote = $dateChangeService->quote($booking, $validated['new_check_in'], $validated['new_check_out']);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => collect($e->errors())->flatten()->first()], 422);
        }

        return response()->json(['success' => true, 'quote' => $quote]);
    }

    /**
     * Submit a date-change request. Routes by price delta:
     *   even   → applied instantly
     *   refund → pending staff review
     *   surcharge → returns a payment redirect (pay-first)
     */
    public function requestDateChange(Request $request, DateChangeService $dateChangeService)
    {
        $validated = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'new_check_in' => 'required|date',
            'new_check_out' => 'required|date|after:new_check_in',
        ]);

        $booking = $this->customerBooking($validated['booking_id']);
        if (! $booking) {
            return response()->json(['success' => false, 'message' => __('api.booking_not_found')], 404);
        }

        try {
            $result = $dateChangeService->request($booking, $validated['new_check_in'], $validated['new_check_out']);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => collect($e->errors())->flatten()->first()], 422);
        } catch (\Throwable $e) {
            \Log::error('Date change request failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => __('api.something_went_wrong')], 500);
        }

        $messages = [
            'applied' => __('api.date_change_applied'),
            'pending_review' => __('api.date_change_pending_review'),
            'awaiting_payment' => __('api.date_change_awaiting_payment'),
        ];

        return response()->json([
            'success' => true,
            'action' => $result['action'],
            'redirect' => $result['redirect'] ?? null,
            'message' => $messages[$result['action']] ?? '',
        ]);
    }

    /**
     * Browser return URL after paying a date-change surcharge. Applies the new dates on success.
     */
    public function dateChangeCallback(Request $request, DateChangeService $dateChangeService, $requestId, $transactionId)
    {
        $customer = auth()->user();

        $dateChangeRequest = DateChangeRequest::with('booking')
            ->where('id', $requestId)
            ->whereHas('booking', fn ($q) => $q->where('customer_id', $customer->id))
            ->first();

        if (! $dateChangeRequest) {
            return redirect()->route('customer.booking')->with('error', __('api.booking_not_found'));
        }

        $booking = $dateChangeRequest->booking;

        // Idempotent: already applied.
        if ($dateChangeRequest->status === DateChangeStatus::Applied->value) {
            return redirect()->route('customer.booking.details', [$booking->number_of_booking, 'showPopup' => '1']);
        }

        // Confirm the surcharge payment with Geidea (webhook is primary; this is the manual fallback).
        $processPaymentService = new ProcessPaymentService;
        $data = $request->all();
        $data['transaction_id'] = $transactionId;
        $handlePayment = $processPaymentService->handleCallBack('geidea', $data);

        if (($handlePayment['status'] ?? false) !== true) {
            return redirect()->route('customer.booking.details', [$booking->number_of_booking])
                ->with('error', __('api.payment_failed'));
        }

        try {
            $dateChangeRequest->update(['gateway_order_id' => $handlePayment['order_id'] ?? $dateChangeRequest->gateway_order_id]);
            $dateChangeService->applyDates($dateChangeRequest);
            $dateChangeRequest->update([
                'status' => DateChangeStatus::Applied->value,
                'gateway_reference' => $handlePayment['reference'] ?? null,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Failed to apply date change after surcharge payment: '.$e->getMessage());
            $dateChangeRequest->update(['status' => DateChangeStatus::Failed->value, 'error' => $e->getMessage()]);

            return redirect()->route('customer.booking.details', [$booking->number_of_booking])
                ->with('error', __('api.date_change_apply_failed'));
        }

        return redirect()->route('customer.booking.details', [$booking->number_of_booking, 'showPopup' => '1'])
            ->with('success', __('api.date_change_applied'));
    }

    /**
     * Re-initiate a stuck/failed surcharge payment for an awaiting-payment request.
     */
    public function retryDateChangePayment(Request $request, DateChangeService $dateChangeService, $requestId)
    {
        $dcRequest = $this->customerDateChangeRequest($requestId);
        if (! $dcRequest) {
            return response()->json(['success' => false, 'message' => __('api.booking_not_found')], 404);
        }

        try {
            $result = $dateChangeService->retryPayment($dcRequest);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => collect($e->errors())->flatten()->first()], 422);
        } catch (\Throwable $e) {
            \Log::error('Date change payment retry failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => __('api.something_went_wrong')], 500);
        }

        $messages = [
            'applied' => __('api.date_change_applied'),
            'pending_review' => __('api.date_change_pending_review'),
            'awaiting_payment' => __('api.date_change_awaiting_payment'),
        ];

        return response()->json([
            'success' => true,
            'action' => $result['action'],
            'redirect' => $result['redirect'] ?? null,
            'message' => $messages[$result['action']] ?? '',
        ]);
    }

    /**
     * Customer withdraws an open date-change request (frees the reserved window).
     */
    public function cancelDateChangeRequest(Request $request, DateChangeService $dateChangeService, $requestId)
    {
        $dcRequest = $this->customerDateChangeRequest($requestId);
        if (! $dcRequest) {
            return response()->json(['success' => false, 'message' => __('api.booking_not_found')], 404);
        }

        try {
            $dateChangeService->cancelByCustomer($dcRequest);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => collect($e->errors())->flatten()->first()], 422);
        }

        return response()->json(['success' => true, 'message' => __('api.date_change_request_canceled')]);
    }

    private function customerBooking($bookingId): ?Booking
    {
        $customer = auth()->user();

        return $this->booking->where([
            ['id', $bookingId],
            ['customer_id', $customer->id],
        ])->first();
    }

    private function customerDateChangeRequest($requestId): ?DateChangeRequest
    {
        $customer = auth()->user();

        return DateChangeRequest::with('booking')
            ->where('id', $requestId)
            ->whereHas('booking', fn ($q) => $q->where('customer_id', $customer->id))
            ->first();
    }
}
