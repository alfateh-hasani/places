<?php

namespace App\Services;

use App\Models\Apartment;
use App\Models\Booking;
use App\Models\Building;
use App\Models\Coupon;
use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\Transaction;
use App\Services\Pricing\PricingService;
use Carbon\Carbon;
use Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Log;

class BookingService
{
    protected $paymentService;

    protected $pricingService;

    public function __construct(ProcessPaymentService $paymentService, PricingService $pricingService)
    {
        $this->paymentService = $paymentService;
        $this->pricingService = $pricingService;
    }

    public function validateCoupon($apartment, $couponCode): ?Coupon
    {
        if (! $couponCode) {
            return null;
        }

        $coupon = Coupon::with('apartments')->where('code', $couponCode)->first();

        if (! $coupon) {
            throw ValidationException::withMessages(['coupon_code' => __('api.coupon_invalid')]);
        }

        // ملاحظة: building() علاقة belongsTo (نموذج واحد أو null) وليست مجموعة — نعتمد على building_id مباشرة.
        $apartmentIds = $coupon->apartments->pluck('id');
        $hasApartmentScope = $apartmentIds->isNotEmpty();
        $hasBuildingScope = ! is_null($coupon->building_id);

        // كوبون عام بلا نطاق — صالح لكل الوحدات.
        if (! $hasApartmentScope && ! $hasBuildingScope) {
            return $coupon;
        }

        if (! $apartment) {
            throw ValidationException::withMessages(['coupon_code' => __('api.coupon_invalid_apartment')]);
        }

        $matchesApartment = $hasApartmentScope && $apartmentIds->contains($apartment->id);
        $matchesBuilding = $hasBuildingScope && (int) $coupon->building_id === (int) $apartment->building_id;

        if (! $matchesApartment && ! $matchesBuilding) {
            throw ValidationException::withMessages(['coupon_code' => __('api.coupon_invalid_apartment')]);
        }

        return $coupon;
    }

    /**
     * @param  int|null  $excludeBookingId  Ignore this booking when checking overlaps
     *                                       (used when re-checking a booking's own new date range).
     */
    public function checkAvailability(Apartment $apartment, $checkIn, $checkOut, ?int $excludeBookingId = null): string
    {
        try {
            $checkInDate = Carbon::parse($checkIn);
            $checkOutDate = Carbon::parse($checkOut);
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'dates' => __('تنسيق التواريخ غير صالح.'),
            ]);
        }

        if (! $checkInDate->lt($checkOutDate)) {
            throw ValidationException::withMessages([
                'dates' => __('يجب أن يكون تاريخ الدخول قبل تاريخ الخروج.'),
            ]);
        }

        // 1. التحقق من الحجوزات المحلية
        // ملاحظة: customer_canceled = "طلب إلغاء قيد المراجعة" يبقى حاجزاً للوحدة حتى يُقبل الإلغاء نهائياً (يصبح canceled)
        $activeStatuses = ['pending', 'approved', 'booked', 'customer_canceled'];

        $overlapExists = Booking::where('apartment_id', $apartment->id)
            ->when($excludeBookingId, fn ($q) => $q->where('id', '!=', $excludeBookingId))
            ->whereIn('status', $activeStatuses)
            ->where('check_in', '<', $checkOutDate)
            ->where('check_out', '>', $checkInDate)
            ->exists();

        if ($overlapExists) {
            throw ValidationException::withMessages([
                'apartment_id' => __('api.already_booked'),
            ]);
        }

        // 1b. حجب النوافذ المحجوزة بطلبات تعديل تواريخ مفتوحة (لوحدات أخرى) لتفادي تسابق نافذتين على نفس المدى
        $requestedOverlap = \App\Models\DateChangeRequest::query()
            ->whereIn('status', \App\Enums\DateChangeStatus::openValues())
            ->whereHas('booking', fn ($q) => $q->where('apartment_id', $apartment->id))
            ->when($excludeBookingId, fn ($q) => $q->where('booking_id', '!=', $excludeBookingId))
            ->where('new_check_in', '<', $checkOutDate)
            ->where('new_check_out', '>', $checkInDate)
            ->exists();

        if ($requestedOverlap) {
            throw ValidationException::withMessages([
                'apartment_id' => __('api.already_booked'),
            ]);
        }

        // 2. التحقق من OwnerRez إذا كان العقار مربوطاً
        $mapping = $apartment->ownerrezMapping;
        if ($mapping && $mapping->check_availability_enabled && config('ownerrez.availability.enabled')) {
            try {
                // عند إعادة فحص حجز لتعديل تواريخه، نستثني حجزه نفسه في OwnerRez أيضاً
                $excludeOwnerRezBookingId = $excludeBookingId
                    ? Booking::where('id', $excludeBookingId)->value('ownerrez_booking_id')
                    : null;

                $ownerRezService = app(\App\Services\OwnerRez\OwnerRezSyncService::class);
                $isAvailable = $ownerRezService->checkAvailability(
                    $mapping->ownerrez_property_id,
                    $checkInDate->format('Y-m-d'),
                    $checkOutDate->format('Y-m-d'),
                    $excludeOwnerRezBookingId
                );

                if (! $isAvailable) {
                    throw ValidationException::withMessages([
                        'apartment_id' => __('api.apartment_not_available_external'),
                    ]);
                }
            } catch (\App\Exceptions\OwnerRez\OwnerRezApiException $e) {
                // إذا فشل الاتصال بـ OwnerRez
                Log::error('OwnerRez availability check failed', [
                    'apartment_id' => $apartment->id,
                    'error' => $e->getMessage(),
                ]);

                // إذا كان strict mode مفعل، نرفض الحجز
                if (config('ownerrez.availability.strict_mode')) {
                    throw ValidationException::withMessages([
                        'apartment_id' => __('api.ownerrez_service_unavailable'),
                    ]);
                }
                // وإلا نسمح بالحجز مع تسجيل تحذير
            }
        }

        $formattedCheckIn = $checkInDate->toDateString();
        $formattedCheckOut = $checkOutDate->toDateString();

        return "الشقة متاحة للحجز من {$formattedCheckIn} إلى {$formattedCheckOut}.";
    }

    /**
     * حساب الأسعار باستخدام نظام التسعير الجديد
     *
     * @deprecated يُفضل استخدام calculatePricesWithDates() التي تستخدم PricingService
     */
    // public function calculatePrices(float $apartmentPrice, int $numberOfNights, ?Coupon $coupon = null): array
    // {
    //     $totalPrice = round($apartmentPrice * $numberOfNights, 2);
    //     $discount = 0;

    //     if ($coupon) {
    //         $discount = $coupon->type === 'percentage'
    //             ? round(($coupon->discount / 100) * $totalPrice, 2)
    //             : min($coupon->discount, $totalPrice);
    //     }

    //     $finalPrice = max($totalPrice - $discount, 0);

    //     $vatRate = Config::get('settings.vat_rate', 15);
    //     $vat = $finalPrice > 0 ? round(($vatRate / 100) * $finalPrice, 2) : 0;

    //     return [
    //         'total_price' => number_format($totalPrice, 2, '.', ''),
    //         'discount' => number_format($discount, 2, '.', ''),
    //         'final_price' => number_format($finalPrice, 2, '.', ''),
    //         'vat' => number_format($vat, 2, '.', ''),
    //         'final_price_with_vat' => number_format($finalPrice + $vat, 2, '.', ''),
    //     ];
    // }

    /**
     * حساب الأسعار باستخدام نظام التسعير الجديد (PricingService)
     * يأخذ بعين الاعتبار: السعر الأساسي، نهاية الأسبوع، الأسعار المخصصة، خصم الإقامة الطويلة
     * ملاحظة: الأسعار المدخلة في الادمن شاملة للضريبة
     */
    public function calculatePricesWithDates(Apartment $apartment, $checkIn, $checkOut, ?Coupon $coupon = null): array
    {
        // تحويل التواريخ إلى Carbon objects إذا كانت strings
        $checkInDate = is_string($checkIn) ? Carbon::parse($checkIn) : $checkIn;
        $checkOutDate = is_string($checkOut) ? Carbon::parse($checkOut) : $checkOut;

        // استخدام PricingService للحصول على السعر الفعلي (شامل الضريبة)
        $priceInfo = $this->pricingService->calculate($apartment, $checkInDate, $checkOutDate);

        // السعر الكلي شامل الضريبة من PricingService (قبل أي خصم)
        $totalPriceWithVat = $priceInfo['total'];

        // استخدام الضريبة والسعر بدون ضريبة من PricingService مباشرة
        $vatAmount = $priceInfo['vat'];
        $totalPriceWithoutVat = $priceInfo['net'];

        $vatRate = Config::get('settings.vat_rate', 15);

        if ($coupon && $coupon->type === 'percentage') {
            // نطبّق نسبة الخصم مباشرة على السعر شامل الضريبة، ثم نستخرج الضريبة —
            // بدل الخصم على الصافي ثم إعادة إضافة الضريبة (الذي يسبّب انحراف تقريب بمقدار سنت).
            $finalPriceWithVat = round($totalPriceWithVat * (1 - $coupon->discount / 100), 2);
            $finalVat = round($finalPriceWithVat * $vatRate / (100 + $vatRate), 2);
            $finalPriceWithoutVat = round($finalPriceWithVat - $finalVat, 2);
            $couponDiscount = round($totalPriceWithoutVat - $finalPriceWithoutVat, 2);
        } elseif ($coupon) {
            // خصم مبلغ ثابت — يُطبّق على السعر بدون ضريبة
            $couponDiscount = min($coupon->discount, $totalPriceWithoutVat);
            $finalPriceWithoutVat = max($totalPriceWithoutVat - $couponDiscount, 0);
            $finalVat = round($finalPriceWithoutVat * ($vatRate / 100), 2);
            $finalPriceWithVat = $finalPriceWithoutVat + $finalVat;
        } else {
            $couponDiscount = 0;
            $finalPriceWithVat = $totalPriceWithVat;
            $finalVat = $vatAmount;
        }

        return [
            'total_price' => number_format($totalPriceWithVat, 2, '.', ''),     // السعر الكلي قبل الخصم (شامل الضريبة)
            'discount' => number_format($couponDiscount, 2, '.', ''),           // خصم الكوبون
            'final_price' => number_format($finalPriceWithVat, 2, '.', ''),     // السعر النهائي بعد الخصم (شامل الضريبة)
            'vat' => number_format($finalVat, 2, '.', ''),                      // الضريبة
            'price_breakdown' => $priceInfo['breakdown'] ?? [],                 // تفاصيل السعر لكل ليلة
        ];
    }

    public function calculateNumberOfNights($checkIn, $checkOut)
    {
        return Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut));
    }

    public function createPayment($validatedData, $customer, $apartment, $plaform = 'web')
    {
        $coupon = null;
        if (! empty($validatedData['coupon_code'])) {
            $coupon = $this->validateCoupon($apartment, $validatedData['coupon_code']);
        }

        // استخدام نظام التسعير الجديد
        $prices = $this->calculatePricesWithDates($apartment, $validatedData['check_in'], $validatedData['check_out'], $coupon);

        $transaction = $this->paymentService->addTransaction($validatedData, $prices, $customer, $plaform);
        $this->createBooking($transaction->id, null);

        return $this->paymentService->processPayment($transaction, $validatedData['payment_method_code']);
    }

    public function createPaymentV2($booking, $validatedData)
    {
        $transaction = $this->paymentService->addTransactionV2($booking, $validatedData['payment_method_code']);

        return $this->paymentService->processPayment($transaction, $validatedData['payment_method_code']);
    }

    // createBooking

    public function createBooking($transaction_id, $payment_id)
    {
        DB::beginTransaction();
        $transaction = Transaction::where('id', $transaction_id)->first();

        if (! $transaction) {
            throw ValidationException::withMessages(['transaction_id' => __('api.transaction_not_exists')]);
        }
        $data = json_decode($transaction->booking_data);
        //

        $apartment = Apartment::where('id', $transaction->apartment_id)->first();
        $building = Building::where('id', $apartment->building_id)->first();

        $numberOfNights = $this->calculateNumberOfNights($data->check_in, $data->check_out);
        $oneNightPrice = $numberOfNights > 0 ? $data->total_price / $numberOfNights : 0;

        $booking = Booking::create([
            'apartment_id' => $transaction->apartment_id,
            'customer_id' => $transaction->customer_id,
            'customer_full_name' => $transaction->customer->first_name.' '.$transaction->customer->last_name,
            'customer_email' => $transaction->customer->email,
            'number_of_nights' => $numberOfNights,
            'adults_count' => $data->adults_count,
            'children_count' => $data->children_count,
            'total_price' => $data->total_price,
            'discount' => $data->discount,
            'final_price' => $data->final_price,
            'one_night_price' => $oneNightPrice,
            'booking_source' => $data->booking_source,
            'coupon_id' => $data->coupon_id ?? null,
            'coupon_code' => $data->coupon_code ?? null,
            'status' => 'pending',
            'payment_id' => $payment_id ?? null,
            'payment_status' => 'pending',
            'check_in' => $data->check_in,
            'check_out' => $data->check_out,

            'check_in_time' => $building->check_in_time,
            'check_out_time' => $building->check_out_time,

            'transaction_id' => $transaction->id,
            'tax' => $data->vat ?? 0,
        ]);
        $transaction->update(['booking_id' => $booking->id]);
        DB::commit();

        return $booking;
    }

    public function getDetermineBooking($apartment, $check_in, $check_out, $coupon = null)
    {
        $numberOfNights = $this->calculateNumberOfNights($check_in, $check_out);

        // استخدام نظام التسعير الجديد
        $prices = $this->calculatePricesWithDates($apartment, $check_in, $check_out, $coupon);
        \Log::info('prices', $prices);
        // حساب متوسط سعر الليلة من السعر الكلي (مع الضريبة) - قبل الخصم
        $avgPricePerNight = $numberOfNights > 0
            ? floatval($prices['total_price']) / $numberOfNights
            : floatval($apartment->price);

        return [
            'number_of_nights' => $numberOfNights,
            'one_nights' => number_format($avgPricePerNight, 2, '.', ''), // متوسط سعر الليلة (مع الضريبة)
            'total_price' => number_format(round($prices['total_price']), 2, '.', ''), // السعر الكلي قبل الخصم (شامل الضريبة)
            'discount' => $prices['discount'],
            'final_price' => number_format(round($prices['final_price']), 2, '.', ''), // السعر النهائي بعد الخصم (شامل الضريبة)
            'vat' => $prices['vat'],
        ];

    }

    public function validateGuestsCount($apartment, $number_of_adults, $number_of_children): void
    {
        $validations = [
            'number_of_adults' => ['value' => $number_of_adults, 'max' => $apartment->adults_count, 'message' => __('api.max_adults')],
            'number_of_children' => ['value' => $number_of_children, 'max' => $apartment->children_count, 'message' => __('api.max_children')],
        ];
        foreach ($validations as $key => $validation) {
            if ($validation['value'] > $validation['max']) {
                throw ValidationException::withMessages([$key => $validation['message']]);
            }
        }
    }

    public function deleteUnpaidBookings()
    {
        $tenMinutesAgo = now()->subMinutes(10);
        $unpaidBookings = Booking::where('status', 'pending')
            ->where('created_at', '<=', $tenMinutesAgo)
            ->get();

        foreach ($unpaidBookings as $booking) {
            $transaction = $booking->transaction;

            // إذا يوجد order_id لا نحذف - DeletePendingBookings يتحقق من Geidea
            if ($transaction && $transaction->order_id) {
                continue;
            }

            $booking->delete();
        }
    }

    public function completeBookingAfterPayment($transactionId)
    {
        DB::beginTransaction();
        try {

            $transaction = Transaction::with('booking')->where('id', $transactionId)->first();

            if (! $transaction || ! $transaction->booking) {
                throw new \Exception(__('api.transaction_not_exists'));
            }

            // تحديث حالة الحجز والدفع
            $booking = $transaction->booking;
            $booking->update([
                'payment_status' => 'paid',
                'status' => 'approved',
            ]);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in completeBookingAfterPayment: '.$e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
        DB::beginTransaction();

        try {
            $this->addPasscodeToSmartLock($booking);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in completeBookingAfterPayment: '.$e->getMessage());
        }

        return [
            'success' => true,
            'message' => '',
        ];

    }

    public function addPasscodeToSmartLock($booking)
    {
        try {
            $lockData = $this->getLockData($booking->apartment_id);

            $startDate = $booking->check_in->format('Y-m-d').' '.$booking->check_in_time?->format('H:i:s');
            $endDate = $booking->check_out->format('Y-m-d').' '.$booking->check_out_time?->format('H:i:s');

            $keyboardPwd = $this->generateRandomPasscode();
            $keyboardPwdName = $booking->id;

            // استدعاء خدمة Sciener
            $scienerLockService = new ScienerLockService($lockData['ttlock_username'], $lockData['ttlock_password']);
            $response = $scienerLockService->addCustomPasscode($lockData['lock_id'], $keyboardPwd, $startDate, $endDate, $keyboardPwdName);

            if (! $response) {
                // تسجيل الفشل في نظام إعادة المحاولة
                \App\Models\PasscodeRetryAttempt::createOrUpdateForBooking($booking, 'Failed to add passcode via Sciener API');
                $booking->markPasscodeAsFailed('Failed to add passcode via Sciener API');
                $booking->markPasscodeAsRetryScheduled();
                throw new \Exception(__('api.passcode_add_failed'));
            }

            // تخزين رمز المرور في قاعدة البيانات
            \App\Models\SmartLockPasscode::create([
                'smart_lock_id' => $lockData['lock_id'],
                'passcode_id' => $response['keyboardPwdId'],
                'apartment_id' => $booking->apartment_id,
                'customer_id' => $booking->customer_id,
                'booking_id' => $booking->id,
                'nickname' => $booking->id,
                'keyboard_pwd' => $keyboardPwd,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

            // إزالة أي محاولة إعادة سابقة إذا نجحت العملية
            \App\Models\PasscodeRetryAttempt::where('booking_id', $booking->id)->delete();
            $booking->markPasscodeAsGenerated();

        } catch (\Exception $e) {
            // تسجيل الفشل في نظام إعادة المحاولة
            \App\Models\PasscodeRetryAttempt::createOrUpdateForBooking($booking, $e->getMessage());
            $booking->markPasscodeAsFailed($e->getMessage());
            $booking->markPasscodeAsRetryScheduled();
            throw $e;
        }
    }

    private function getLockData($apartmentId)
    {

        $apartment = Apartment::where('id', $apartmentId)->first();

        if (! $apartment || ! $apartment->smart_lock_id) {
            throw new \Exception("Smart lock ID not found for apartment ID {$apartmentId}");
        }

        $lock = \App\Models\SmartLock::where('id', $apartment->smart_lock_id)->first();

        if (! $lock) {
            throw new \Exception("Smart lock not found for ID {$apartment->smart_lock_id}");
        }

        $building = Building::where('id', $lock->building_id)->first();

        if (! $building) {
            throw new \Exception("Building not found for ID {$lock->building_id}");
        }

        return [
            'lock_id' => $lock->lock_id,
            'building_id' => $building->id,
            'ttlock_username' => $building->ttlock_username,
            'ttlock_password' => $building->ttlock_password,
        ];
    }

    private function generateRandomPasscode($length = 6)
    {
        return substr(str_shuffle('0123456789'), 0, $length);
    }

    // bookingServices
    public function addServicesToBooking($request, $customer_id)
    {

        $date = Carbon::now()->toDateString();
        $booking = Booking::where([
            ['id', $request->booking_id],
            ['customer_id', $customer_id],
            ['check_out', '>', $date],
        ])->first();
        if (! $booking) {
            return [
                'success' => false,
                'message' => __('api.booking_not_found'),
            ];
        }
        try {
            $servicesData = Service::whereIn('id', $request->services)->get()->map(function ($service) use ($booking) {
                return [
                    'booking_id' => $booking->id,
                    'customer_id' => $booking->customer_id,
                    'apartment_id' => $booking->apartment_id,
                    'service_id' => $service->id,
                    'price' => $service->price,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            $servicesDat = ServiceBooking::insert($servicesData);

            // Send notification to apartment supervisor
            $this->sendServiceRequestNotifications($booking, $request->services);

            return [
                'success' => true,
                'message' => __('api.services_added'),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }

    }

    /**
     * Send service request notifications to apartment supervisor
     */
    private function sendServiceRequestNotifications($booking, $serviceIds)
    {
        try {
            $adminNotificationService = app(\App\Services\AdminNotificationService::class);

            // Get the created service bookings
            $serviceBookings = ServiceBooking::where('booking_id', $booking->id)
                ->whereIn('service_id', $serviceIds)
                ->get();

            foreach ($serviceBookings as $serviceBooking) {
                // Send notification to apartment supervisor
                $adminNotificationService->sendServiceRequestNotification($serviceBooking);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send service request notifications: '.$e->getMessage());
        }
    }

    /**
     * حساب السعر الكلي باستخدام نظام التسعير الجديد (PricingService)
     * يأخذ بعين الاعتبار: السعر الأساسي، نهاية الأسبوع، الأسعار المخصصة، خصم الإقامة الطويلة
     * ملاحظة: يُرجع السعر بدون ضريبة (لأن الأسعار المدخلة شاملة للضريبة ونحتاج لفصلها)
     */
    public function calculateTotalPrice(Apartment $apartment, $checkIn, $checkOut)
    {
        $checkInDate = Carbon::parse($checkIn)->startOfDay();
        $checkOutDate = Carbon::parse($checkOut)->startOfDay();

        // التأكد من أن checkOut أكبر من checkIn
        if ($checkOutDate->lt($checkInDate)) {
            throw ValidationException::withMessages([
                'checkout' => __('validation.custom.checkout.after_checkin'),
            ]);
        }

        // استخدام PricingService للحصول على السعر الفعلي (شامل الضريبة)
        $priceInfo = $this->pricingService->calculate($apartment, $checkInDate, $checkOutDate);

        // استخراج الضريبة وإرجاع السعر بدونها
        $totalPriceWithVat = $priceInfo['total'];
        $vatRate = Config::get('settings.vat_rate', 15);
        $vatAmount = round($totalPriceWithVat * $vatRate / (100 + $vatRate), 2);
        $totalPriceWithoutVat = round($totalPriceWithVat - $vatAmount, 2);

        return $totalPriceWithoutVat;
    }
}
