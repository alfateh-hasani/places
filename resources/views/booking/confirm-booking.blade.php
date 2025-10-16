@extends('layouts.master')
@push('css')
<link href="{{ asset('assets/css/slick.css?'.time())}}" rel="stylesheet" />
<link href="{{ asset('assets/css/slick-theme.css?'.time())}}" rel="stylesheet" />

@endpush
@section('content')


<section class="container py-10">
    <a>
        <img class="inline-block me-4 rtl:rotate-180" src="{{asset('assets/img/back.svg')}}" />
        <span class="font-semibold text-2xl text-title translate-y-1 inline-block">
            {{__('booking.review_payment')}}
        </span>
    </a>
</section>

<section class="descriptions pb-24">
    <div class="container">
        <div class="xl:flex xl:flex-row">
            <div class="xl:basis-8/12">
                <div class="container">
                    <div class="float-left rtl:float-right  ltr:mr-5 rtl:ml-5 rtl:ml-5 mb-6 w-full">
                        <h1 class="float-left rtl:float-right  font-semibold text-2xl text-title"> 
                            {{ $apartment->ml('name') }}
                        </h1>
                    </div>
                    @if($errors->any())
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    @endif
                   
                    <div class="clear-both"></div>
                    <form action="{{route('web-booking.add',['uuid'=>$booking->uuid])}}" method="POST" id="booking_form">
                        <input type="hidden" id="apartment_id" name="apartment_id" value="{{$apartment->id}}">
                        <input type="hidden" id="number_of_nights" name="number_of_nights" value="{{$booking->number_of_nights}}">
                        <input type="hidden" name="total_price" value="{{$booking->total_price}}">
                        <input type="hidden" name="check_in" value="{{$booking->check_in}}">
                        <input type="hidden" name="check_out" value="{{$booking->check_out}}">
                        @csrf
                        <h1 class="font-semibold text-2xl text-title border-t border-blackopacity ">
                            {{__('booking.payment_method')}}
                        </h1>
                        <ul class="pb-8 border-b border-blackopacity">
                            @foreach ($payment_details as $key => $item)
                                <li>
                                    <input type="radio" 
                                        name="payment_method_code" 
                                        id="check{{$key}}" 
                                        value="{{$item['value']}}" 
                                        class="hidden" 
                                        required 
                                        {{ $key === 0 ? 'checked' : '' }} />
                                    
                                    <label for="check{{$key}}" class="cursor-pointer block border border-filterborder mt-2 py-3 px-4 rounded-lg mb-2 hover:bg-filterborder ease-in-out duration-300">
                                        <img class="inline-block" src="{{$item['icon']}}" style="max-width: 80%;     max-height: 40px;" />
                                        
                                        <div class="w-5 h-5 border rounded-full border-filterborder float-right rtl:float-left mt-1 relative">
                                            <div class="w-3 h-3 rounded-full bg-price absolute opacity-0"></div>
                                        </div>
                                        <div class="clear-both"></div>
                                    </label>
                                </li>
                            @endforeach
                        </ul>
               
                </div>
                <div class="py-2 xl:py-7 detail-description border-b border-blackopacity mb-8">
                    <h5 class="font-semibold text-xl text-filterhover mb-6">
                        {{$policy_title}}
                    </h5>
                    {{-- <ul>
                        <li class="inline-block w-4/12 font-semibold text-base text-title mb-2"><img class="inline-block ltr:mr-2 rtl:ml-2" src="assets/img/feature-ok.svg" /> Free Cancellation For 48 Hours</li>
                        <li class="inline-block w-4/12 font-semibold text-base text-title mb-2"><img class="inline-block ltr:mr-2 rtl:ml-2" src="assets/img/feature-ok.svg" /> Free Cancellation For 48 Hours</li>
                        <li class="inline-block w-4/12 font-semibold text-base text-title mb-2"><img class="inline-block ltr:mr-2 rtl:ml-2" src="assets/img/feature-ok.svg" /> Dive Right In</li>
                        <li class="inline-block w-4/12 font-semibold text-base text-title mb-2"><img class="inline-block ltr:mr-2 rtl:ml-2" src="assets/img/feature-ok.svg" /> Dive Right In</li>
                    </ul> --}}
                  
                    <div class="font-light text-base text-gri mt-3 mb-2 ease-in-out duration-900 max-h-[72px] overflow-hidden">
                        {!!$policy_description!!}
                    </div>
                    <button class="showmore font-normal text-sm text-blue underline"> </button>
                </div>
            </div>
            <div class=" xl:block xl:basis-4/12 xl:pl-5 rtl:xl:pr-5">
                <div class="border border border-filterborder rounded-xl px-5 py-6">
                    <div class="">
                        <div class="relative">
                            <div class="slider slider-checkout" style="max-width: 450px">
                                @foreach ($apartment->getMedia('image') as $image)
                                   <div>
                                        <a style="width: 100%; max-width:100%; display:block" class="rounded-xl overflow-hidden border border-border" href="{{$apartment->link}}">
                                            <img 
                                                class="object-cover w-full block" 
                                                src="{{ $image->getUrl('grid') }}" 
                                                alt="@lang('apartment.apartment_name_default')"
                                            />
                                        </a>
                                   </div>
                                @endforeach
                            </div>
                            
                        </div>
                    </div>               
                  
                        <p class="my-4 font-normal text-base text-reviews">
                            <span class="font-bold text-2xl text-black translate-y-0.5 inline-block">
                                {{$booking->one_night_price}} 
                            </span> 
                            {{__('apartment.sar')}}
                        </p>

                        
                        <div class=" grid grid-cols-2 mx-0 w-full gap-2">
                            <div class="border border-border bg-[#f6f6f6] rounded-lg px-4">
                                <p>
                                    <span class="block text-sm translate-y-1.5">
                                        {{__('booking.check_in')}}
                                    </span>
                                     {{$booking->check_in->format('Y-m-d')}}
                                </p>
                            </div>
                            <div class="border border-border bg-[#f6f6f6] rounded-lg px-4">
                                <p>
                                    <span class="block text-sm translate-y-1.5">  
                                        {{__('booking.check_out')}}
                                    </span>
                                    {{$booking->check_out->format('Y-m-d')}}
                                </p>
                            </div>
                        </div>

                        <div class="border border-border bg-[#f6f6f6] rounded-lg px-4 grid grid-cols-2 mx-0 w-full gap-2 mt-2 mb-4">
                            <p>
                                <span class="block text-sm translate-y-1.5">
                                    {{__('booking.check_in_time')}}
                                </span>
                              {{ Config::get('settings.check_in_time') }}
                            </p>
                            <p class="ps-4 checkout-time relative">
                                <span class="block text-sm translate-y-1.5">
                                    {{__('booking.check_out_time')}}
                                </span>
                                {{ Config::get('settings.check_out_time') }}
                            </p>
                        </div>

                        <p class="font-semibold my-3 text-gray-500">  
                            {{__('site.summary_reservastion')}}
                        </p>


                   
                        <ul>
                            <li class="mb-4 font-semibold text-sm text-title">
                                <span> {{__('booking.one_night')}}  </span>
                                <span class="float-right rtl:float-left">
                                    {{$booking->one_night_price .' '. __('apartment.price')}}
                                </span>
                                <div class="clear-both"></div>
                            </li>
                            <li class="mb-4 font-semibold text-sm text-title">
                                <span>
                                    {{__('apartment.number_of_nights') }}
                                </span>
                                <span class="float-right rtl:float-left">
                                    {{$booking->number_of_nights }}
                                </span>
                                <div class="clear-both"></div>
                            </li>

                           

                            
                            

                            <!-- عرض السعر الكلي قبل الخصم -->
                            <li class="mb-4 font-semibold text-sm text-title">
                                <span>
                                    {{__('booking.subtotal') }}
                                </span>
                                <span class="float-right rtl:float-left">
                                   {{$booking->total_price}} {{  __('apartment.price')}}
                                </span>
                                <div class="clear-both"></div>
                            </li>

                            <li class="mb-4 font-semibold text-sm text-title">
                                <span>
                                    {{__('apartment.vat') }}
                                </span>
                                <span class="float-right rtl:float-left">
                                   <span id="tax-row"> {{$booking->tax}} </span> {{  __('apartment.price')}}
                                </span>
                                <div class="clear-both"></div>
                            </li>


                             @if($booking->discount)
                            <li class="mb-4 font-semibold text-sm text-title">
                                <span>
                                    {{__('apartment.coupon_discount') }}
                                </span>
                                <span class="float-right rtl:float-left">
                                   -{{$booking->discount .' '. __('apartment.price')}}
                                </span>
                                <div class="clear-both"></div>
                            </li>

                            @endif
                            
                            <!-- عرض السعر النهائي بعد الخصم -->
                            @if($booking->discount > 0)
                            <li class="mb-4 font-semibold text-sm text-title">
                                <span>
                                    {{__('booking.final_price') }}
                                </span>
                                <span class="float-right rtl:float-left">
                                   {{$booking->final_price}} {{  __('apartment.price')}}
                                </span>
                                <div class="clear-both"></div>
                            </li>
                            @endif
                            
                            <!-- هنا سيتم إدراج الخصم عند تطبيق الكوبون -->
                        </ul>
                    
                    <!-- تحديد ID لهذا العنصر ليكون نقطة مرجعية للإضافة -->
                        <p id="total_price_container" class="py-4 px-3   border border-filterborder rounded-lg font-semibold text-sm text-title mb-4">
                            {{__('booking.total_price')}}
                            <span class="font-normal text-base text-reviews">
                                ({{__('apartment.price_tax') }})
                            </span>
                            <span id="total_price" class="float-right rtl:float-left">
                                {{  $booking->final_price .' '.__('apartment.price') }} 
                            </span>
                        </p>
                    <ul>
                        @foreach ($payment_details as $item)
                            <li>
                                <a class="block border border-filterborder py-3 px-4 rounded-lg mb-2 hover:bg-filterborder ease-in-out duration-300">
                                    <img class="inline-block" src="{{$item['icon']}}" height="50" />
                                      
                                </a>
                            </li>
                        @endforeach
                        
                         
                    </ul>
                    <div class="flex flex-row items-center">
                        <input type="text" id="coupon_code" name="coupon_code" 
                        @if($booking->coupon_code)
                            value="{{ $booking->coupon_code }}"
                        @endif
                        placeholder="@lang('apartment.coupon_code')" 
                        class="border border-gray-300 rounded-lg h-12 px-3 flex-1">
                    
                        <button type="button" id="verify_coupon" class="mr-4 bg-price rounded-lg h-12 px-4 font-semibold text-white">
                            @lang('apartment.verify_coupon')
                        </button>
                    
                        <button type="button" id="remove_coupon" 
                            class="mr-4  bg-red-500 rounded-lg h-12 px-4 font-semibold text-white"
                            @if(!$booking->coupon_code) style="display: none;" @endif>
                            @lang('apartment.remove_coupon')
                        </button>
                    </div>
                    
                    <div id="coupon_message" class="mt-2 text-green-500"></div>
                    
                    <button class="bg-price rounded-lg h-12 w-full font-semibold text-white hidden md:block">
                        {{__('booking.book_now')}}
                    </button>
                </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- زر الحجز الثابت للجوال -->
<div class="fixed bottom-0 left-0 right-0 p-4 bg-white shadow-[0_-8px_25px_rgba(0,0,0,0.1)] md:hidden z-50">
    <div class="container">
        <div class="flex items-center justify-between gap-4">
            <div class="flex flex-col">
                <span class="text-sm text-gray-600">{{__('booking.total_price')}}</span>
                <span class="text-lg font-bold text-price">{{ $booking->final_price }}  </span>
            </div>
            <button id="book_now_mobile" class="bg-price rounded-lg h-12 px-8 font-semibold text-white flex-shrink-0">
                {{__('booking.book_now')}}
            </button>
        </div>
    </div>
</div>

<!-- إضافة مساحة في الأسفل للجوال لتجنب تداخل المحتوى مع الزر الثابت -->
<div class="h-24 md:hidden"></div>








@endsection

@push('js')
<script>
     $(document).ready(function() {
    $('#verify_coupon').on('click', function() {
        const couponCode = $('#coupon_code').val().trim();
        
        if (couponCode === "") {
            $('#coupon_message').text("{{ __('apartment.enter_coupon') }}");
            return;
        }

        $.ajax({
            url: '{{ route("web-booking.coupons.verify",["uuid"=>$booking->uuid]) }}',
            method: 'POST',
            data: {
                code: couponCode,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                let discountText = response.type === "percentage" ? "%" : "{{ __('apartment.price_unit') }}";
                let discountValue = response.type === "percentage" ? (response.discount + "%") : (response.discount + " {{ __('apartment.price_unit') }}");

                let taxValue = response.vat; 
                
                $('#coupon_message').text("{{ __('apartment.coupon_applied') }}: " + discountValue);
                $('#total_price').text(response.final_price + ' {{ __("apartment.price") }}');

                // إزالة أي خصم سابق لمنع التكرار
                $('#discount_row').remove();

                // إضافة الخصم إلى الفاتورة
                let discountHtml = `
                    <li id="discount_row" class="mb-4 font-semibold text-sm text-title text-green-600">
                        <span>{{ __('apartment.discount_applied') }}</span>
                        <span class="float-right rtl:float-left"> -${discountValue} </span>
                        <div class="clear-both"></div>
                    </li>
                `;
                $(discountHtml).insertBefore('#total_price_container');

                // إظهار زر حذف الكوبون
                $('#remove_coupon').show();
                $('#tax-row').text(taxValue);

 
            },
            error: function() {
                $('#coupon_message').text("{{ __('apartment.error_verifying_coupon') }}");
            }
        });
    });

    // **📌 زر حذف الكوبون**
    $('#remove_coupon').on('click', function() {
        $.ajax({
            url: '{{ route("web-booking.coupons.remove", ["uuid"=>$booking->uuid]) }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                $('#coupon_code').val(""); // مسح الكود
                $('#coupon_message').text("{{ __('apartment.coupon_removed') }}");
                $('#discount_row').remove(); // إزالة الخصم
                $('#total_price').text(response.final_price + ' {{ __("apartment.price") }}');

                // إخفاء زر حذف الكوبون
                $('#remove_coupon').hide();
                $('#tax-row').text(response.vat);

 
            },
            error: function() {
                $('#coupon_message').text("{{ __('apartment.error_removing_coupon') }}");
            }
        });
    });
});


$('#book_now_mobile').on('click', function() {
    $(this).prop('disabled', true);
    $('#booking_form').submit();
});
    </script>
    
@endpush