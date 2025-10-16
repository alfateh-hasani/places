@extends(backpack_view('layouts.top_left'))

@section('content')
<div class="container-fluid">
    <h2 class="mb-4">📊 تقارير الحجوزات</h2>

    <!-- نموذج الفلترة -->
    <div class="card mb-4">
        <div class="card-header">
            <strong>فلترة التقارير</strong>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reports.index') }}">
                <div class="row">
                    <!-- الفلاتر الزمنية -->
                    <div class="col-md-3">
                        <label for="from_date">من تاريخ:</label>
                        <input type="date" name="from_date" id="from_date" class="form-control" value="{{ $fromDate }}">
                    </div>
                    <div class="col-md-3">
                        <label for="to_date">إلى تاريخ:</label>
                        <input type="date" name="to_date" id="to_date" class="form-control" value="{{ $toDate }}">
                    </div>
                    <!-- فلتر الشقة -->
                    <div class="col-md-3">
                        <label for="apartment_id">الشقة:</label>
                        <select name="apartment_id" id="apartment_id" class="form-control">
                            <option value="">-- اختر الشقة --</option>
                            @foreach(\App\Models\Apartment::all() as $apt)
                                <option value="{{ $apt->id }}" {{ request('apartment_id') == $apt->id ? 'selected' : '' }}>
                                    {{ $apt->name_ar }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <!-- فلتر المبنى -->
                    <div class="col-md-3">
                        <label for="building_id">المبنى:</label>
                        <select name="building_id" id="building_id" class="form-control">
                            <option value="">-- اختر المبنى --</option>
                            @foreach(\App\Models\Building::all() as $bld)
                                <option value="{{ $bld->id }}" {{ request('building_id') == $bld->id ? 'selected' : '' }}>
                                    {{ $bld->name_ar }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row mt-3">
                    <!-- فلتر المستخدم -->
                    <div class="col-md-3">
                        <label for="user_id">المستخدم:</label>
                        <select name="user_id" id="user_id" class="form-control">
                            <option value="">-- اختر المستخدم --</option>
                            @foreach(\App\Models\User::all() as $user)
                                <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <!-- فلتر المدينة -->
                    <div class="col-md-3">
                        <label for="city">المدينة:</label>
                        <select name="city" id="city" class="form-control">
                            <option value="">-- اختر المدينة --</option>
                            <!-- نفترض هنا أن قيمة "1" تمثل الرياض، و"2" تمثل جدة -->
                            <option value="1" {{ request('city') == '1' ? 'selected' : '' }}>الرياض</option>
                            <option value="2" {{ request('city') == '2' ? 'selected' : '' }}>جدة</option>
                            <!-- يمكنك إضافة المزيد -->
                        </select>
                    </div>
                    <div class="col-md-3 align-self-end">
                        <button type="submit" class="btn btn-primary btn-block">عرض التقارير</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- الملخص العام -->
    <div class="row">
        <div class="col-md-4">
            <div class="card text-white bg-success mb-3">
                <div class="card-header">الوحدات المتاحة</div>
                <div class="card-body">
                    <h3 class="card-title">{{ $availableUnits }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-danger mb-3">
                <div class="card-header">الوحدات المؤجرة</div>
                <div class="card-body">
                    <h3 class="card-title">{{ $rentedUnits }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-info mb-3">
                <div class="card-header">إجمالي الدخل</div>
                <div class="card-body">
                    <h3 class="card-title">{{ $totalIncome }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- تقارير المبيعات العامة -->
    <div class="card mb-4">
        <div class="card-header"><strong>المبيعات اليومية العامة</strong></div>
        <div class="card-body">
            @if($dailySales->count())
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>اليوم</th>
                                <th>إجمالي المبيعات</th>
                                <th>عدد الحجوزات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dailySales as $sale)
                                <tr>
                                    <td>{{ $sale->day }}</td>
                                    <td>{{ $sale->total_sales }}</td>
                                    <td>{{ $sale->total_bookings }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-center">لا توجد بيانات يومية.</p>
            @endif
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>المبيعات الشهرية العامة</strong></div>
        <div class="card-body">
            @if($monthlySales->count())
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>الشهر</th>
                                <th>إجمالي المبيعات</th>
                                <th>عدد الحجوزات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($monthlySales as $sale)
                                <tr>
                                    <td>{{ $sale->year }}-{{ str_pad($sale->month,2,'0',STR_PAD_LEFT) }}</td>
                                    <td>{{ $sale->total_sales }}</td>
                                    <td>{{ $sale->total_bookings }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-center">لا توجد بيانات شهرية.</p>
            @endif
        </div>
    </div>

    <!-- تقارير الشقة المحددة (إذا تم اختيارها) -->
    @if($apartmentDailySales)
        <div class="card mb-4">
            <div class="card-header"><strong>تقارير الشقة (يومي)</strong></div>
            <div class="card-body">
                @if($apartmentDailySales->count())
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>اليوم</th>
                                    <th>إجمالي المبيعات</th>
                                    <th>عدد الحجوزات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($apartmentDailySales as $sale)
                                    <tr>
                                        <td>{{ $sale->day }}</td>
                                        <td>{{ $sale->total_sales }}</td>
                                        <td>{{ $sale->total_bookings }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-center">لا توجد بيانات يومية للشقة المحددة.</p>
                @endif
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><strong>تقارير الشقة (شهري)</strong></div>
            <div class="card-body">
                @if($apartmentMonthlySales->count())
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>الشهر</th>
                                    <th>إجمالي المبيعات</th>
                                    <th>عدد الحجوزات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($apartmentMonthlySales as $sale)
                                    <tr>
                                        <td>{{ $sale->year }}-{{ str_pad($sale->month,2,'0',STR_PAD_LEFT) }}</td>
                                        <td>{{ $sale->total_sales }}</td>
                                        <td>{{ $sale->total_bookings }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-center">لا توجد بيانات شهرية للشقة المحددة.</p>
                @endif
            </div>
        </div>
    @endif

    <!-- تقارير المبنى المحدد (إذا تم اختيار مبنى) -->
    @if($buildingDailySales)
        <div class="card mb-4">
            <div class="card-header"><strong>تقارير المبنى (يومي)</strong></div>
            <div class="card-body">
                @if($buildingDailySales->count())
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>اليوم</th>
                                    <th>إجمالي المبيعات</th>
                                    <th>عدد الحجوزات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($buildingDailySales as $sale)
                                    <tr>
                                        <td>{{ $sale->day }}</td>
                                        <td>{{ $sale->total_sales }}</td>
                                        <td>{{ $sale->total_bookings }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-center">لا توجد بيانات يومية للمبنى المحدد.</p>
                @endif
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><strong>تقارير المبنى (شهري)</strong></div>
            <div class="card-body">
                @if($buildingMonthlySales->count())
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>الشهر</th>
                                    <th>إجمالي المبيعات</th>
                                    <th>عدد الحجوزات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($buildingMonthlySales as $sale)
                                    <tr>
                                        <td>{{ $sale->year }}-{{ str_pad($sale->month,2,'0',STR_PAD_LEFT) }}</td>
                                        <td>{{ $sale->total_sales }}</td>
                                        <td>{{ $sale->total_bookings }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-center">لا توجد بيانات شهرية للمبنى المحدد.</p>
                @endif
            </div>
        </div>
    @endif

    <!-- تقارير المستخدم المحدد (إذا تم اختيار مستخدم) -->
    @if($userDailySales)
        <div class="card mb-4">
            <div class="card-header"><strong>تقارير المستخدم (يومي)</strong></div>
            <div class="card-body">
                @if($userDailySales->count())
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>اليوم</th>
                                    <th>إجمالي المبيعات</th>
                                    <th>عدد الحجوزات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($userDailySales as $sale)
                                    <tr>
                                        <td>{{ $sale->day }}</td>
                                        <td>{{ $sale->total_sales }}</td>
                                        <td>{{ $sale->total_bookings }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-center">لا توجد بيانات يومية للمستخدم المحدد.</p>
                @endif
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><strong>تقارير المستخدم (شهري)</strong></div>
            <div class="card-body">
                @if($userMonthlySales->count())
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>الشهر</th>
                                    <th>إجمالي المبيعات</th>
                                    <th>عدد الحجوزات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($userMonthlySales as $sale)
                                    <tr>
                                        <td>{{ $sale->year }}-{{ str_pad($sale->month,2,'0',STR_PAD_LEFT) }}</td>
                                        <td>{{ $sale->total_sales }}</td>
                                        <td>{{ $sale->total_bookings }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-center">لا توجد بيانات شهرية للمستخدم المحدد.</p>
                @endif
            </div>
        </div>
    @endif

    <!-- الشقق الأكثر مبيعاً -->
    <div class="card mb-4">
        <div class="card-header"><strong>الشقق الأكثر مبيعاً</strong></div>
        <div class="card-body">
            @if($topApartments->count())
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>الشقة</th>
                                <th>إجمالي المبيعات</th>
                                <th>عدد الحجوزات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topApartments as $apt)
                                <tr>
                                    <td>{{ $apt->apartment_name }}</td>
                                    <td>{{ $apt->total_sales }}</td>
                                    <td>{{ $apt->total_bookings }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-center">لا توجد بيانات للشقق الأكثر مبيعاً.</p>
            @endif
        </div>
    </div>

    <!-- مبيعات حسب المدينة -->
    <div class="card mb-4">
        <div class="card-header"><strong>المبيعات حسب المدينة</strong></div>
        <div class="card-body">
            @if($salesByCity->count())
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>المدينة</th>
                                <th>إجمالي المبيعات</th>
                                <th>عدد الحجوزات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($salesByCity as $citySale)
                                <tr>
                                    <td>{{ $citySale->city_id }}</td>
                                    <td>{{ $citySale->total_sales }}</td>
                                    <td>{{ $citySale->total_bookings }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-center">لا توجد بيانات للمبيعات حسب المدينة.</p>
            @endif
        </div>
    </div>
</div>
@endsection
