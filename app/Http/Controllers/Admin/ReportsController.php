<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Booking;
use App\Models\OwnerRezPropertyMapping;
use App\Services\OwnerRez\OwnerRezApiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportsController extends Controller
{
    /**
     * عرض التقارير الشاملة مع تطبيق الفلاتر الزمنية والكيانية.
     *
     * تشمل التقارير:
     * - ملخص عام (الوحدات المتاحة، الوحدات المؤجرة، إجمالي الدخل)
     * - المبيعات اليومية والشهرية (لكل الحجوزات)
     * - تقارير لشقة محددة (يومي وشهري)
     * - تقارير لمبنى محدد (يومي وشهري)
     * - تقارير لمستخدم محدد (يومي وشهري)
     * - التقارير المالية الشهرية (لجميع الحجوزات أو لشقة معينة)
     * - الشقق الأكثر مبيعاً
     * - المبيعات حسب المدينة
     *
     * يمكن تطبيق الفلاتر التالية:
     * - الفترة (من تاريخ - إلى تاريخ)
     * - الشقة (apartment_id)
     * - المبنى (building_id)
     * - المستخدم (user_id)
     * - المدينة (city) (نفترض أن قيمة هذا الحقل تُطابق قيمة buildings.city_id)
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {

        // الحصول على الفلاتر من الطلب
        $fromDate = $request->input('from_date', Carbon::now()->startOfMonth()->toDateString());
        $toDate = $request->input('to_date', Carbon::now()->endOfMonth()->toDateString());
        $apartmentId = $request->input('apartment_id');   // لتقارير شقة محددة
        $buildingId = $request->input('building_id');    // لتقارير مبنى محدد
        $userId = $request->input('user_id');        // لتقارير عن يوزر (عميل) محدد
        $city = $request->input('city');           // لتصفية المبيعات حسب المدينة (قيمته تمثل city_id)

        // فلتر الفترة الزمنية (يُعتبر أي حجز يتقاطع مع الفترة)
        $dateFilter = function ($query) use ($fromDate, $toDate) {
            $query->where(function ($q) use ($fromDate, $toDate) {
                $q->whereBetween('check_in', [$fromDate, $toDate])
                    ->orWhereBetween('check_out', [$fromDate, $toDate])
                    ->orWhere(function ($q2) use ($fromDate, $toDate) {
                        $q2->where('check_in', '<=', $fromDate)
                            ->where('check_out', '>=', $toDate);
                    });
            });
        };

        /*
         * الاستعلام العام للحجوزات مع تطبيق الفلاتر:
         * نبدأ باستعلام أساسي على جدول bookings مع فلترة الفترة،
         * ثم نضيف الفلاتر الاختيارية (الشقة والمستخدم) مباشرة،
         * وإذا وُجدت فلاتر للمبنى أو المدينة، نقوم بانضمام جدول apartments (وأيضاً buildings).
         */
        $overallQuery = Booking::query();
        $overallQuery->where($dateFilter);
        if ($apartmentId) {
            $overallQuery->where('apartment_id', $apartmentId);
        }
        if ($userId) {
            $overallQuery->where('customer_id', $userId);
        }
        if ($buildingId || $city) {
            // نضيف انضمام جدول apartments لتطبيق فلتر المبنى والمدينة
            $overallQuery->join('apartments', 'bookings.apartment_id', '=', 'apartments.id');
            if ($buildingId) {
                $overallQuery->where('apartments.building_id', $buildingId);
            }
            if ($city) {
                // انضمام جدول buildings لتصفية المدينة
                $overallQuery->join('buildings', 'apartments.building_id', '=', 'buildings.id')
                    ->where('buildings.city_id', $city);
            }
            // لضمان استرجاع أعمدة bookings
            $overallQuery->select('bookings.*');
        }
        $overallBookings = $overallQuery->get();

        // حساب عدد الوحدات المؤجرة وإجمالي الدخل من الحجوزات المفلترة
        $rentedUnits = $overallBookings->count();
        $totalIncome = $overallBookings->sum('final_price');

        /**
         * حساب الوحدات المتاحة:
         * يتم جلب الشقق (مع تطبيق الفلاتر إن وُجدت) التي ليس لها أي حجز يتقاطع مع الفترة.
         */
        $availableUnitsQuery = Apartment::query();
        if ($apartmentId) {
            $availableUnitsQuery->where('id', $apartmentId);
        }
        if ($buildingId) {
            $availableUnitsQuery->where('building_id', $buildingId);
        }
        if ($city) {
            $availableUnitsQuery->join('buildings', 'apartments.building_id', '=', 'buildings.id')
                ->where('buildings.city_id', $city);
        }
        $availableUnits = $availableUnitsQuery->whereDoesntHave('bookings', function ($q) use ($dateFilter) {
            $q->where($dateFilter);
        })->count();

        // التقارير العامة: المبيعات اليومية والشهرية (لكافة الحجوزات وفقاً للفلاتر)
        $dailySales = (clone $overallQuery)
            ->select(
                DB::raw('DATE(check_in) as day'),
                DB::raw('SUM(final_price) as total_sales'),
                DB::raw('COUNT(*) as total_bookings')
            )
            ->groupBy('day')
            ->orderBy('day', 'asc')
            ->get();

        $monthlySales = (clone $overallQuery)
            ->select(
                DB::raw('YEAR(check_in) as year'),
                DB::raw('MONTH(check_in) as month'),
                DB::raw('SUM(final_price) as total_sales'),
                DB::raw('COUNT(*) as total_bookings')
            )
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        // تقارير الشقة المحددة (إذا تم اختيار شقة)
        $apartmentDailySales = null;
        $apartmentMonthlySales = null;
        if ($apartmentId) {
            $aptQuery = Booking::query();
            $aptQuery->where($dateFilter)->where('apartment_id', $apartmentId);
            $apartmentDailySales = (clone $aptQuery)
                ->select(
                    DB::raw('DATE(check_in) as day'),
                    DB::raw('SUM(final_price) as total_sales'),
                    DB::raw('COUNT(*) as total_bookings')
                )
                ->groupBy('day')
                ->orderBy('day', 'asc')
                ->get();
            $apartmentMonthlySales = (clone $aptQuery)
                ->select(
                    DB::raw('YEAR(check_in) as year'),
                    DB::raw('MONTH(check_in) as month'),
                    DB::raw('SUM(final_price) as total_sales'),
                    DB::raw('COUNT(*) as total_bookings')
                )
                ->groupBy('year', 'month')
                ->orderBy('year', 'asc')
                ->orderBy('month', 'asc')
                ->get();
        }

        // تقارير المبنى المحدد (إذا تم اختيار مبنى)
        $buildingDailySales = null;
        $buildingMonthlySales = null;
        if ($buildingId) {
            $bldQuery = Booking::query();
            $bldQuery->where($dateFilter)
                ->join('apartments', 'bookings.apartment_id', '=', 'apartments.id')
                ->where('apartments.building_id', $buildingId);
            $buildingDailySales = (clone $bldQuery)
                ->select(
                    DB::raw('DATE(check_in) as day'),
                    DB::raw('SUM(final_price) as total_sales'),
                    DB::raw('COUNT(*) as total_bookings')
                )
                ->groupBy('day')
                ->orderBy('day', 'asc')
                ->get();
            $buildingMonthlySales = (clone $bldQuery)
                ->select(
                    DB::raw('YEAR(check_in) as year'),
                    DB::raw('MONTH(check_in) as month'),
                    DB::raw('SUM(final_price) as total_sales'),
                    DB::raw('COUNT(*) as total_bookings')
                )
                ->groupBy('year', 'month')
                ->orderBy('year', 'asc')
                ->orderBy('month', 'asc')
                ->get();
        }

        // تقارير المستخدم (العميل) المحدد
        $userDailySales = null;
        $userMonthlySales = null;
        if ($userId) {
            $usrQuery = Booking::query();
            $usrQuery->where($dateFilter)->where('customer_id', $userId);
            $userDailySales = (clone $usrQuery)
                ->select(
                    DB::raw('DATE(check_in) as day'),
                    DB::raw('SUM(final_price) as total_sales'),
                    DB::raw('COUNT(*) as total_bookings')
                )
                ->groupBy('day')
                ->orderBy('day', 'asc')
                ->get();
            $userMonthlySales = (clone $usrQuery)
                ->select(
                    DB::raw('YEAR(check_in) as year'),
                    DB::raw('MONTH(check_in) as month'),
                    DB::raw('SUM(final_price) as total_sales'),
                    DB::raw('COUNT(*) as total_bookings')
                )
                ->groupBy('year', 'month')
                ->orderBy('year', 'asc')
                ->orderBy('month', 'asc')
                ->get();
        }

        /**
         * تقرير الشقق الأكثر مبيعاً:
         * يُبنى من استعلام مستقل (لا يعتمد على الاستعلام العام لتجنب انضمام جدول apartments مرتين)
         */
        $topApartmentsQuery = Booking::query();
        $topApartmentsQuery->where($dateFilter);
        if ($apartmentId) {
            $topApartmentsQuery->where('apartment_id', $apartmentId);
        }
        if ($userId) {
            $topApartmentsQuery->where('customer_id', $userId);
        }
        // دائمًا نحتاج للانضمام إلى جدول apartments للحصول على اسم الشقة
        $topApartmentsQuery->join('apartments', 'bookings.apartment_id', '=', 'apartments.id');
        if ($buildingId) {
            $topApartmentsQuery->where('apartments.building_id', $buildingId);
        }
        if ($city) {
            $topApartmentsQuery->join('buildings', 'apartments.building_id', '=', 'buildings.id')
                ->where('buildings.city_id', $city);
        }
        $topApartments = $topApartmentsQuery->select(
            'bookings.apartment_id',
            'apartments.name_ar as apartment_name',
            DB::raw('SUM(final_price) as total_sales'),
            DB::raw('COUNT(*) as total_bookings')
        )
            ->groupBy('bookings.apartment_id', 'apartments.name_ar')
            ->orderByDesc('total_sales')
            ->limit(5)
            ->get();

        // مبيعات حسب المدينة: الانضمام إلى جداول apartments وbuildings والتجميع حسب buildings.city_id
        $salesByCityQuery = Booking::query();
        $salesByCityQuery->where($dateFilter)
            ->join('apartments', 'bookings.apartment_id', '=', 'apartments.id')
            ->join('buildings', 'apartments.building_id', '=', 'buildings.id')
            ->select(
                'buildings.city_id',
                DB::raw('SUM(final_price) as total_sales'),
                DB::raw('COUNT(*) as total_bookings')
            )
            ->groupBy('buildings.city_id')
            ->orderByDesc('total_sales');
        $salesByCity = $salesByCityQuery->get();

        return view('admin.reports.index', compact(
            'fromDate',
            'toDate',
            'availableUnits',
            'rentedUnits',
            'totalIncome',
            'dailySales',
            'monthlySales',
            'apartmentDailySales',
            'apartmentMonthlySales',
            'buildingDailySales',
            'buildingMonthlySales',
            'userDailySales',
            'userMonthlySales',
            'topApartments',
            'salesByCity'
        ));
    }

    public function dailyCheckOutReport(Request $request)
    {
        // تحديد تاريخ اليوم الحالي
        $today = Carbon::now()->toDateString();

        // فلتر المصدر (all, app, airbnb)
        $source = $request->input('source', 'all');
        $site = $request->input('site', 'all');

        $query = Booking::whereDate('check_out', Carbon::today())
            ->with(['apartment.building']);

        // تطبيق الفلتر حسب المصدر
        if ($source === 'app') {
            // حجوزات من التطبيق (web, android, ios) وليست من Airbnb
            $query->where(function ($q) {
                $q->where(function ($q1) {
                    $q1->whereIn('booking_source', ['web', 'android', 'ios'])
                        ->where('is_airbnb_booking', 0);
                })->orWhere(function ($q2) {
                    $q2->whereNull('ownerrez_booking_id')
                        ->where('is_airbnb_booking', 0);
                });
            });
        } elseif ($source === 'airbnb') {
            // حجوزات Airbnb (من OwnerRez مع channel_name = airbnb أو is_airbnb_booking = 1)
            $query->where(function ($q) {
                $q->where('is_airbnb_booking', 1)
                    ->orWhere(function ($q2) {
                        $q2->whereNotNull('ownerrez_booking_id')
                            ->whereRaw('LOWER(channel_name) = ?', ['airbnb']);
                    });
            });
        }
        // إذا كان 'all' لا نضيف فلتر - يعرض جميع الحجوزات

        // تطبيق فلتر الموقع (site)
        if ($site !== 'all') {
            $query->where('site', $site);
        }

        $reports = $query->orderBy('check_out_time', 'asc')->paginate(25)->withQueryString();

        // جلب قيم site المتاحة مع caching لتجنب full table scan في كل طلب
        $availableSites = Cache::remember('booking_available_sites', now()->addHours(1), function () {
            return Booking::whereNotNull('site')->distinct()->pluck('site');
        });

        return view('admin.reports.daily_check_out', compact('reports', 'source', 'site', 'availableSites'));
    }

    public function ownerRezCheckoutToday(Request $request)
    {
        $today = Carbon::today()->toDateString();
        $bust = $request->boolean('bust');
        $offset = max(0, (int) $request->input('offset', 0));

        $cacheKey = "ownerrez_checkout_{$today}_offset_{$offset}";

        // Busting only clears offset=0; subsequent offsets expire naturally
        if ($bust && $offset === 0) {
            Cache::forget($cacheKey);
        }

        try {
            $data = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($today, $offset) {
                $apiService = new OwnerRezApiService;
                $apiService->withoutLogging()->withTimeout(30);

                $propertyIds = OwnerRezPropertyMapping::pluck('ownerrez_property_id')
                    ->filter()
                    ->implode(',');

                if (empty($propertyIds)) {
                    return [
                        'success' => true,
                        'bookings' => [],
                        'has_more' => false,
                        'next_offset' => null,
                        'date' => $today,
                        'warning' => 'لا توجد عقارات مرتبطة بـ OwnerRez في النظام',
                    ];
                }

                $response = $apiService->getBookings([
                    'property_ids' => $propertyIds,
                    'from' => $today,
                    'to' => $today,
                    'include_guest' => 'true',
                    'include_fields' => 'true',
                    'limit' => 100,
                    'offset' => $offset,
                ]);

                $items = $response['items'] ?? [];

                // إذا عادت 100 سجل بالضبط، قد توجد صفحة تالية
                $hasMore = count($items) >= 100;

                // فلترة: فقط الحجوزات التي تاريخ مغادرتها اليوم (وليست blocks)
                $bookings = collect($items)
                    ->filter(fn ($b) => ($b['departure'] ?? '') === $today &&
                        ($b['type'] ?? '') !== 'block'
                    )
                    ->values();

                return [
                    'success' => true,
                    'bookings' => $bookings,
                    'has_more' => $hasMore,
                    'next_offset' => $hasMore ? $offset + 100 : null,
                    'date' => $today,
                ];
            });

            // أضف خريطة أسماء الشقق (cached منفصلة) في كل response
            if (! empty($data['success'])) {
                $data['property_name_map'] = Cache::remember('ownerrez_property_name_map', now()->addHours(1), function () {
                    return OwnerRezPropertyMapping::with('apartment')
                        ->get()
                        ->mapWithKeys(fn ($m) => [
                            (string) $m->ownerrez_property_id => $m->apartment?->name_ar,
                        ])
                        ->filter()
                        ->all();
                });
            }

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('OwnerRez checkout today report failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب البيانات من OwnerRez: '.$e->getMessage(),
            ], 500);
        }
    }

    public function ownerRezMaintenanceToday(Request $request): \Illuminate\Http\JsonResponse
    {
        $today = Carbon::today()->toDateString();
        $bust = $request->boolean('bust');
        $offset = max(0, (int) $request->input('offset', 0));

        $cacheKey = "ownerrez_maintenance_{$today}_offset_{$offset}";

        if ($bust && $offset === 0) {
            Cache::forget($cacheKey);
        }

        try {
            $data = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($today, $offset) {
                $apiService = new OwnerRezApiService;
                $apiService->withoutLogging()->withTimeout(30);

                $propertyIds = OwnerRezPropertyMapping::pluck('ownerrez_property_id')
                    ->filter()
                    ->implode(',');

                if (empty($propertyIds)) {
                    return [
                        'success' => true,
                        'blocks' => [],
                        'has_more' => false,
                        'next_offset' => null,
                        'date' => $today,
                        'warning' => 'لا توجد عقارات مرتبطة بـ OwnerRez في النظام',
                    ];
                }

                $response = $apiService->getBookings([
                    'property_ids' => $propertyIds,
                    'from' => $today,
                    'to' => $today,
                    'limit' => 100,
                    'offset' => $offset,
                ]);

                $items = $response['items'] ?? [];
                $hasMore = count($items) >= 100;

                // فقط الـ blocks التي تنتهي اليوم (الصيانة والحجوزات المغلقة)
                $blocks = collect($items)
                    ->filter(fn ($b) => ($b['departure'] ?? '') === $today &&
                        ($b['type'] ?? '') === 'block'
                    )
                    ->values();

                return [
                    'success' => true,
                    'blocks' => $blocks,
                    'has_more' => $hasMore,
                    'next_offset' => $hasMore ? $offset + 100 : null,
                    'date' => $today,
                ];
            });

            if (! empty($data['success'])) {
                $data['property_name_map'] = Cache::remember('ownerrez_property_name_map', now()->addHours(1), function () {
                    return OwnerRezPropertyMapping::with('apartment')
                        ->get()
                        ->mapWithKeys(fn ($m) => [
                            (string) $m->ownerrez_property_id => $m->apartment?->name_ar,
                        ])
                        ->filter()
                        ->all();
                });
            }

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('OwnerRez maintenance today report failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب بيانات الصيانة من OwnerRez: '.$e->getMessage(),
            ], 500);
        }
    }
}
