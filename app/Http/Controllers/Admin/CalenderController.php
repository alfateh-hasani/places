<?php

namespace App\Http\Controllers\Admin;

use App\Models\Apartment;
use App\Models\Booking;
use App\Models\OwnerRezBooking;
use App\Models\OwnerRezPropertyMapping;
use App\Services\OwnerRez\OwnerRezSyncService;
use App\Services\Pricing\PricingService;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Carbon\Carbon;

/**
 * Class FeatureController
 *
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class CalenderController extends CrudController
{
    public function showCalendar($apartmentId)
    {
        return view('admin.apartments.calendar', compact('apartmentId'));
    }

    /**
     * عرض تقويم الأسعار المخصصة (منفصل عن تقويم الحجوزات)
     */
    public function showPricingCalendar($apartmentId)
    {
        $apartment = Apartment::with('pricing')->findOrFail($apartmentId);

        // إنشاء سجل pricing إذا لم يكن موجوداً
        if (! $apartment->pricing) {
            \App\Models\ApartmentPrice::create([
                'apartment_id' => $apartment->id,
                'base_price' => $apartment->price,
                'weekend_price' => null,
                'long_stay_discount' => null,
                'is_active' => 1,
            ]);
            $apartment->load('pricing');
        }

        return view('admin.apartments.pricing-calendar', compact('apartmentId', 'apartment'));
    }

    /**
     * جلب الأسعار المخصصة للتقويم
     */
    public function getCustomPrices($apartmentId)
    {
        $customPrices = \App\Models\ApartmentPriceCalendar::where('apartment_id', $apartmentId)->get();
        $apartment = Apartment::findOrFail($apartmentId);

        $events = [];

        // إضافة الأسعار المخصصة
        foreach ($customPrices as $price) {
            $events[] = [
                'id' => 'price_'.$price->id,
                'title' => $price->custom_price.' ر.س',
                'start' => $price->date,
                'end' => $price->date,
                'backgroundColor' => '#17A2B8',
                'borderColor' => '#138496',
                'textColor' => '#fff',
                'allDay' => true,
                'extendedProps' => [
                    'type' => 'custom_price',
                    'price_id' => $price->id,
                    'custom_price' => $price->custom_price,
                    'date' => $price->date,
                ],
            ];
        }

        return response()->json($events);
    }

    public function getApartmentBookings($apartmentId)
    {
        $bookings = Booking::where('apartment_id', $apartmentId)
            ->select('id', 'number_of_booking', 'check_in', 'check_out', 'is_airbnb_booking', 'customer_full_name', 'customer_email', 'total_price', 'status')
            ->get();

        $events = [];

        foreach ($bookings as $booking) {
            $sourceColor = $booking->is_airbnb_booking ? '#FF5733' : '#2ECC71';

            $statusColors = [
                'pending' => '#FFC107',
                'approved' => '#28A745',
                'rejected' => '#DC3545',
                'booked' => '#007BFF',
            ];
            $statusColor = $statusColors[$booking->status] ?? '#6C757D';
            $finalColor = "linear-gradient(135deg, $sourceColor 50%, $statusColor 50%)";

            $events[] = [
                'id' => $booking->id,
                'title' => "حجز #{$booking->number_of_booking}",
                'start' => $booking->check_in,
                'end' => $booking->check_out,
                'backgroundColor' => $finalColor,
                'borderColor' => $sourceColor,
                'textColor' => '#fff',
                'extendedProps' => [
                    'type' => 'booking',
                    'customer_name' => $booking->customer_full_name,
                    'customer_email' => $booking->customer_email,
                    'total_price' => $booking->total_price,
                    'status' => ucfirst($booking->status),
                    'source' => $booking->is_airbnb_booking ? 'Airbnb' : 'Website',
                ],
            ];
        }

        // طبقة OwnerRez: إضافة الحجوزات غير المزامنة من Airbnb + كشف التعارض
        $mapping = OwnerRezPropertyMapping::where('apartment_id', $apartmentId)
            ->where('sync_enabled', true)
            ->first();

        if ($mapping && config('ownerrez.availability.enabled')) {
            try {
                $syncedIds = OwnerRezBooking::where('apartment_id', $apartmentId)
                    ->pluck('ownerrez_booking_id')
                    ->flip();

                // الحجوزات النشطة محلياً لمقارنة التعارض
                $activeLocalBookings = $bookings->whereIn('status', ['pending', 'approved', 'booked'])
                    ->map(fn ($b) => [
                        'check_in' => Carbon::parse($b->check_in),
                        'check_out' => Carbon::parse($b->check_out),
                    ])
                    ->values();

                $from = now()->format('Y-m-d');
                $to = now()->addYear()->format('Y-m-d');

                $ownerRezBookings = app(OwnerRezSyncService::class)->getActiveBookings(
                    $mapping->ownerrez_property_id,
                    $from,
                    $to
                );

                foreach ($ownerRezBookings as $b) {
                    if ($syncedIds->has((string) $b['id'])) {
                        continue;
                    }

                    $arrival = Carbon::parse($b['arrival']);
                    $departure = Carbon::parse($b['departure']);

                    $hasConflict = $activeLocalBookings->contains(
                        fn ($local) => $arrival->lt($local['check_out']) && $departure->gt($local['check_in'])
                    );

                    $events[] = [
                        'id' => 'ownerrez_'.$b['id'],
                        'title' => $hasConflict
                            ? '⚠ تعارض Airbnb #'.$b['id']
                            : 'Airbnb #'.$b['id'].' (غير مزامن)',
                        'start' => $b['arrival'],
                        'end' => $b['departure'],
                        'backgroundColor' => $hasConflict ? '#8B0000' : '#FF8C00',
                        'borderColor' => $hasConflict ? '#8B0000' : '#FF8C00',
                        'textColor' => '#fff',
                        'extendedProps' => [
                            'type' => $hasConflict ? 'ownerrez_conflict' : 'ownerrez_unsynced',
                            'source' => 'OwnerRez',
                            'has_conflict' => $hasConflict,
                            'customer_name' => trim(($b['guest']['first_name'] ?? '').' '.($b['guest']['last_name'] ?? '')) ?: null,
                        ],
                    ];
                }
            } catch (\Exception $e) {
                \Log::warning('OwnerRez admin calendar fetch failed', [
                    'apartment_id' => $apartmentId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json($events);
    }

    /**
     * حفظ أو تحديث سعر مخصص ليوم معين
     */
    public function saveCustomPrice($apartmentId)
    {
        $validated = request()->validate([
            'date' => 'required|date',
            'custom_price' => 'required|numeric|min:0',
        ]);

        $price = \App\Models\ApartmentPriceCalendar::updateOrCreate(
            [
                'apartment_id' => $apartmentId,
                'date' => $validated['date'],
            ],
            [
                'custom_price' => $validated['custom_price'],
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ السعر المخصص بنجاح',
            'data' => $price,
        ]);
    }

    /**
     * حذف سعر مخصص
     */
    public function deleteCustomPrice($apartmentId, $priceId)
    {
        $price = \App\Models\ApartmentPriceCalendar::where('apartment_id', $apartmentId)
            ->where('id', $priceId)
            ->first();

        if ($price) {
            $price->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف السعر المخصص بنجاح',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'السعر غير موجود',
        ], 404);
    }

    /**
     * معاينة حساب السعر لفترة معينة
     */
    public function previewPricing($apartmentId)
    {
        $validated = request()->validate([
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
        ]);

        $apartment = Apartment::findOrFail($apartmentId);
        $checkIn = Carbon::parse($validated['check_in']);
        $checkOut = Carbon::parse($validated['check_out']);

        $pricingService = app(PricingService::class);
        $result = $pricingService->calculate($apartment, $checkIn, $checkOut);

        // إضافة تفاصيل إضافية
        $nights = $checkIn->diffInDays($checkOut);
        $result['nights'] = $nights;
        $result['check_in'] = $checkIn->format('Y-m-d');
        $result['check_out'] = $checkOut->format('Y-m-d');
        $result['breakdown'] = $this->getPriceBreakdown($apartment, $checkIn, $checkOut, $pricingService);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * الحصول على تفصيل الأسعار لكل ليلة
     */
    private function getPriceBreakdown($apartment, $checkIn, $checkOut, $pricingService)
    {
        $nights = $checkIn->diffInDays($checkOut);
        $breakdown = [];

        for ($day = 0; $day < $nights; $day++) {
            $date = $checkIn->copy()->addDays($day);
            $nextDate = $date->copy()->addDay();

            $result = $pricingService->calculate($apartment, $date, $nextDate);

            $breakdown[] = [
                'date' => $date->format('Y-m-d'),
                'day_name' => $date->locale('ar')->dayName,
                'price' => $result['one_night_price'],
                'is_weekend' => in_array($date->dayOfWeek, [Carbon::FRIDAY, Carbon::SATURDAY]),
            ];
        }

        return $breakdown;
    }
}
