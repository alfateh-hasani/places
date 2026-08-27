@extends(backpack_view('blank'))

@section('header')
    <section class="container-fluid">
        <h2>
            <span class="text-capitalize">تعديل وقت الدخول</span>
        </h2>
    </section>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">تعديل وقت الدخول - الحجز رقم {{ $booking->number_of_booking }}</h3>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">
                        <i class="la la-check"></i> {{ session('success') }}
                        @if($booking->smartLockPasscodes()->latest()->first())
                            @php($latestPasscode = $booking->smartLockPasscodes()->latest()->first()->keyboard_pwd)
                            <br><strong>الرمز الجديد للغرفة:</strong>
                            <span class="badge badge-primary" style="font-size: 16px; padding: 8px;">
                                {{ $latestPasscode }}
                            </span>
                            <button type="button" class="btn btn-link btn-sm p-0" onclick="copyPasscodeToClipboard('{{ $latestPasscode }}', this)" title="{{ __('cms.copy_passcode') }}">
                                <i class="la la-copy"></i>
                            </button>
                        @endif
                    </div>
                @endif
                
                @if(session('error'))
                    <div class="alert alert-danger">
                        <i class="la la-exclamation-triangle"></i> {{ session('error') }}
                    </div>
                @endif
                
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                
                <form method="POST" action="{{ url(config('backpack.base.route_prefix')) }}/booking/{{ $booking->id }}/update-check-in-time">
                    @csrf
                    
                    <div class="form-group">
                        <label for="check_in_time">وقت الدخول الجديد:</label>
                        <input type="time" 
                               class="form-control" 
                               id="check_in_time" 
                               name="check_in_time" 
                               value="{{ $booking->check_in_time ? $booking->check_in_time->format('H:i') : '' }}"
                               required>
                        <small class="form-text text-muted">
                            اختر الوقت فقط (مثال: 14:30) - التاريخ سيتم أخذ تاريخ الوصول الحالي ({{ $booking->check_in->format('Y-m-d') }})
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label>معلومات الحجز:</label>
                        <div class="card bg-light">
                            <div class="card-body">
                                <p><strong>رقم الحجز:</strong> {{ $booking->number_of_booking }}</p>
                                <p><strong>العميل:</strong> {{ $booking->customer_full_name }}</p>
                                <p><strong>الشقة:</strong> {{ $booking->apartment->name_ar }}</p>
                                <p><strong>تاريخ الوصول:</strong> {{ $booking->check_in->format('Y-m-d')     }}</p>
                                <p><strong>تاريخ المغادرة:</strong> {{ $booking->check_out->format('Y-m-d') }}</p>
                                <p><strong>وقت الدخول الحالي:</strong> {{ $booking->check_in_time ? $booking->check_in_time->format('H:i') : 'غير محدد' }}</p>
                                <p><strong>رمز الغرفة الحالي:</strong>
                                    @if($booking->smartLockPasscodes()->latest()->first())
                                        @php($currentPasscode = $booking->smartLockPasscodes()->latest()->first()->keyboard_pwd)
                                        <span class="badge badge-info">{{ $currentPasscode }}</span>
                                        <button type="button" class="btn btn-link btn-sm p-0" onclick="copyPasscodeToClipboard('{{ $currentPasscode }}', this)" title="{{ __('cms.copy_passcode') }}">
                                            <i class="la la-copy"></i>
                                        </button>
                                    @else
                                        <span class="text-muted">غير محدد</span>
                                    @endif
                                </p>
                                
                                <hr>
                                <h6><strong>معلومات الباس كود:</strong></h6>
                                <p><strong>حالة الباس كود:</strong> 
                                    @switch($booking->passcode_status)
                                        @case('pending')
                                            <span class="badge badge-warning">معلق</span>
                                            @break
                                        @case('generated')
                                            <span class="badge badge-success">تم الإنشاء</span>
                                            @break
                                        @case('failed')
                                            <span class="badge badge-danger">فشل</span>
                                            @break
                                        @case('retry_scheduled')
                                            <span class="badge badge-info">إعادة محاولة مجدولة</span>
                                            @break
                                        @default
                                            <span class="badge badge-secondary">غير محدد</span>
                                    @endswitch
                                </p>
                                
                                @if($booking->passcode_generated_at)
                                    <p><strong>تاريخ الإنشاء:</strong> {{ $booking->passcode_generated_at->format('Y-m-d H:i:s') }}</p>
                                @endif
                                
                                @if($booking->passcode_error)
                                    <p><strong>خطأ الباس كود:</strong> 
                                        <span class="text-danger">{{ $booking->passcode_error }}</span>
                                    </p>
                                @endif
                                
                                @if($booking->passcode_retry_count > 0)
                                    <p><strong>عدد المحاولات:</strong> 
                                        <span class="badge badge-warning">{{ $booking->passcode_retry_count }}</span>
                                    </p>
                                @endif
                                
                                @if($booking->passcode_status === 'failed' || $booking->passcode_status === 'retry_scheduled')
                                    <div class="mt-3">
                                        <form method="POST" action="{{ url(config('backpack.base.route_prefix')) }}/booking/{{ $booking->id }}/regenerate-passcode" style="display: inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('هل تريد إعادة إنشاء الباس كود؟')">
                                                <i class="la la-refresh"></i> إعادة إنشاء الباس كود
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="la la-save"></i> حفظ التغييرات
                        </button>
                        <a href="{{ url(config('backpack.base.route_prefix')) }}/booking" class="btn btn-secondary">
                            <i class="la la-arrow-left"></i> العودة للحجوزات
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@include('admin.booking.copy_passcode_script')
@endsection
