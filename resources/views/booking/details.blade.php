@extends('layouts.master')
@push('css')
<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<!-- Flatpickr — نفس تحميلات صفحة الحجز (base + dark theme) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/dark.css">

<style>
/* نفس ستايل تقويم صفحة الحجز (apartment/show.blade.php) */
.flatpickr-day {
    width: 30px;
    height: 30px;
    line-height: 30px;
    margin: 3px;
}
.flatpickr-calendar .flatpickr-day.selected,
.flatpickr-calendar .flatpickr-day.selected:hover,
.flatpickr-calendar .flatpickr-day.selected:focus,
span.flatpickr-day.selected{
    background: #EF552C !important;
    color: #fff !important;
    border-color: #EF552C !important;
}
/* التقويم يُلحق بـ body داخل المودال (static=false) فيلزم رفعه فوق طبقة SweetAlert2 */
.flatpickr-calendar.open {
    z-index: 99999;
}
/* نضمن أن الصفحة RTL لا تعكس صفوف رؤوس الأيام/الأرقام فتختل المحاذاة — نفرض LTR على التقويم. */
.flatpickr-calendar,
.flatpickr-calendar .flatpickr-weekdays,
.flatpickr-calendar .flatpickr-weekdaycontainer,
.flatpickr-calendar .flatpickr-days,
.flatpickr-calendar .dayContainer {
    direction: ltr !important;
}
/* داخل المودال كانت شبكة الأيام تلتفّ إلى 6 أعمدة بينما الرؤوس 7 → اختلال المحاذاة.
   نثبّت الصفّين على 7 خلايا × 36px = 252px مع box-sizing:border-box (يشمل الحدود) وبلا هوامش
   حتى يتّسع 7 أعمدة بالضبط وتتطابق مع الرؤوس. */
.flatpickr-calendar {
    width: auto !important;
}
.flatpickr-calendar .flatpickr-days,
.flatpickr-calendar .dayContainer,
.flatpickr-calendar .flatpickr-weekdays,
.flatpickr-calendar .flatpickr-weekdaycontainer {
    width: 252px !important;
    min-width: 252px !important;
    max-width: 252px !important;
}
.flatpickr-calendar .flatpickr-day {
    box-sizing: border-box !important;
    width: 36px !important;
    max-width: 36px !important;
    height: 36px !important;
    line-height: 36px !important;
    margin: 0 !important;
}
.flatpickr-calendar .flatpickr-weekday {
    box-sizing: border-box !important;
    max-width: 36px !important;
    flex: 1 1 36px !important;
}
</style>

<style>
     .cancel-booking-btn[disabled] {
        background-color: #f5d7d1;  
        color: #a8a8a8;  
        cursor: not-allowed; 
        opacity: 0.6;  
    }
    .bg-white {
    
    background-color: #0f0c0c;
}
/* SweetAlert2 buttons styling */
.swal2-confirm {
    background-color: #3085d6 !important;
    background: #3085d6 !important;
    color: white !important;
    border: none !important;
    padding: 10px 24px !important;
    border-radius: 4px !important;
    font-weight: bold !important;
}
.swal2-cancel {
    background-color: #d33 !important;
    background: #d33 !important;
    color: white !important;
    border: none !important;
    padding: 10px 24px !important;
    border-radius: 4px !important;
    margin-right: 10px !important;
    font-weight: bold !important;
}
.swal2-styled.swal2-confirm {
    background-color: #3085d6 !important;
    background: #3085d6 !important;
}
.swal2-styled.swal2-cancel {
    background-color: #d33 !important;
    background: #d33 !important;
}
/* زر "السابق" (deny) — أزرق أساسي، وفي بداية الصف للدلالة على الرجوع خطوة */
.swal2-deny,
.swal2-styled.swal2-deny {
    background-color: #3085d6 !important;
    background: #3085d6 !important;
    color: white !important;
    border: none !important;
    padding: 10px 24px !important;
    border-radius: 4px !important;
    font-weight: bold !important;
    order: -1 !important;
}
/* زر تأكيد التعديل (دفع/طلب) — أخضر. تخصيص أعلى من .swal2-confirm لتجاوز !important */
.swal2-popup .swal2-styled.dc-confirm-pay {
    background-color: #28a745 !important;
    background: #28a745 !important;
}
</style>
@endpush
@section('content')

@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<section class="profile py-5 lg:py-16    min-h-screen lg:min-h-min text-white">
    <div class="container">
        <div>
            <div class="inline-block w-8 h-8 rounded-full bg-filteritem relative">
                <svg class="absolute top-2/4 left-2/4 -translate-y-2/4 -translate-x-2/4" xmlns="http://www.w3.org/2000/svg" width="10.939" height="10.748" viewBox="0 0 10.939 10.748">
                  <path id="Path_843" data-name="Path 843" d="M5.843,11.343H16.116m-5.7,4.844L6.178,11.95a.856.856,0,0,1,0-1.211L10.416,6.5" transform="translate(-5.177 -5.97)" fill="none" stroke="#000" stroke-width="1.5"/>
                </svg>
            </div>
            <p class="inline-block font-semibold text-2xl ml-4 -translate-y-2">
                # {{$booking->number_of_booking }}
            </p>
        </div>
        <div class="py-8 px-6   rounded-2xl mt-6" style="background-color: #000;">
            <div class="border-b border-border pb-8 mb-8">
               <a href="{{route('apartments.show',$booking->apartment?->slug)}}" > 
                    <p class="font-semibold text-lg float-left  rtl:float-right py-2.5">
                        {{$booking->apartment->{'name_'.app()->getLocale()} }}
                    </p>
               </a>
                <div class="float-right rtl:float-left">
                    <a class="py-3 px-4 inline-block rounded-md bg-gri text-white ml-2" href="{{ route('customer.booking.print_details', $booking->number_of_booking) }}">
                        <svg class="inline-block" id="fi_2891455" enable-background="new 0 0 24 24" height="20" viewBox="0 0 24 24" width="20" xmlns="http://www.w3.org/2000/svg" fill="currentColor">
                            <path d="m21.5 18h-3c-.276 0-.5-.224-.5-.5s.224-.5.5-.5h3c.827 0 1.5-.673 1.5-1.5v-7c0-.827-.673-1.5-1.5-1.5h-19c-.827 0-1.5.673-1.5 1.5v7c0 .827.673 1.5 1.5 1.5h3c.276 0 .5.224.5.5s-.224.5-.5.5h-3c-1.379 0-2.5-1.122-2.5-2.5v-7c0-1.378 1.121-2.5 2.5-2.5h19c1.379 0 2.5 1.122 2.5 2.5v7c0 1.378-1.121 2.5-2.5 2.5z"></path>
                            <path d="m14.5 21h-6c-.276 0-.5-.224-.5-.5s.224-.5.5-.5h6c.276 0 .5.224.5.5s-.224.5-.5.5z"></path>
                            <path d="m14.5 19h-6c-.276 0-.5-.224-.5-.5s.224-.5.5-.5h6c.276 0 .5.224.5.5s-.224.5-.5.5z"></path>
                            <path d="m10.5 17h-2c-.276 0-.5-.224-.5-.5s.224-.5.5-.5h2c.276 0 .5.224.5.5s-.224.5-.5.5z"></path>
                            <path d="m18.5 7c-.276 0-.5-.224-.5-.5v-4c0-.827-.673-1.5-1.5-1.5h-9c-.827 0-1.5.673-1.5 1.5v4c0 .276-.224.5-.5.5s-.5-.224-.5-.5v-4c0-1.378 1.121-2.5 2.5-2.5h9c1.379 0 2.5 1.122 2.5 2.5v4c0 .276-.224.5-.5.5z"></path>
                            <path d="m16.5 24h-9c-1.379 0-2.5-1.122-2.5-2.5v-8c0-.276.224-.5.5-.5h13c.276 0 .5.224.5.5v8c0 1.378-1.121 2.5-2.5 2.5zm-10.5-10v7.5c0 .827.673 1.5 1.5 1.5h9c.827 0 1.5-.673 1.5-1.5v-7.5z"></path>
                        </svg>
                        <span class="inline-block ml-2 text-sm">   
                            {{__('booking.print')}}
                        </span>
                    </a>
                    
                    @if($booking->status !== 'customer_canceled' && $booking->status !== 'canceled')
                    <div class="relative group inline-block">
                        <button {{ $can_cancel ? '' : 'disabled' }}
                            class="py-3 px-4 inline-block rounded-md bg-[#fdeee9] text-price ml-2 cancel-booking-btn {{ !$can_cancel ? 'cursor-not-allowed opacity-50' : '' }}" 
                            data-booking-id="{{ $booking->id }}">
                            <span class="inline-block ml-2 text-sm">{{ __('إلغاء الحجز') }}</span>
                        </button>
                    
                        <!-- Tooltip -->
                        @if(!$can_cancel)
                        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 translate-y-2 px-3 py-2 mb-3 
                        bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10">
                            {{ __('booking.cancel_booking_required_hours', ['hours' => $cancel_before_hours ?? 24]) }}
                        </div>
                        @endif
                    </div>

                    <div class="relative group inline-block">
                        <button {{ $can_cancel ? '' : 'disabled' }}
                            class="py-3 px-4 inline-block rounded-md bg-[#eaf3ff] text-price ml-2 edit-dates-btn {{ !$can_cancel ? 'cursor-not-allowed opacity-50' : '' }}"
                            data-booking-id="{{ $booking->id }}"
                            data-check-in="{{ $booking->check_in->format('Y-m-d') }}"
                            data-check-out="{{ $booking->check_out->format('Y-m-d') }}">
                            <span class="inline-block ml-2 text-sm">{{ __('تعديل التواريخ') }}</span>
                        </button>
                        @if(!$can_cancel)
                        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 translate-y-2 px-3 py-2 mb-3
                        bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10">
                            {{ __('booking.date_change_required_hours', ['hours' => $cancel_before_hours ?? 24]) }}
                        </div>
                        @endif
                    </div>
                    @elseif($booking->status === 'customer_canceled')
                    <div class="py-3 px-4 inline-block rounded-md bg-gray-200 text-gray-600 ml-2 cursor-not-allowed">
                        <span class="inline-block ml-2 text-sm">{{ __('إلغاء الحجز') }} - {{ __('api.booking_status_customer_canceled') }}</span>
                    </div>
                    @endif
                    

                    @if (!$has_review and $booking->status == 'finished')
                        <button data-src="#popup-2" data-fancybox type="button" 
                                class="py-3 px-4 inline-block rounded-md bg-[#fdeee9] text-price ml-2 ">
                            <!-- Replace the comment below with the SVG icon -->
                            <svg class="inline-block" fill="currentColor" height="20" width="20" xmlns="http://www.w3.org/2000/svg">
                                <!-- Example SVG path, replace with your actual SVG code -->
                                <circle cx="10" cy="10" r="8"></circle>
                            </svg>
                            <span class="inline-block ml-2 text-sm">
                                {{ __('booking.review') }}
                            </span>
                        </button>
                    @endif
                   
                

                    
                     <a class="py-2.5 px-4 inline-block rounded-md ml-2 border border-price text-center text-price">
                         <svg class="inline-block" fill="currentColor" xmlns="http://www.w3.org/2000/svg" id="fi_5728913" data-name="Layer 1" viewBox="0 0 512 512" width="20" height="20"><path d="M489.417,279v-1.182c0-62.1-24.349-120.646-68.56-164.857S318.1,44.4,256,44.4,135.354,68.749,91.143,112.96s-68.56,102.758-68.56,164.856V279A27.578,27.578,0,0,0,0,306.081V397.1a27.571,27.571,0,0,0,27.538,27.539H44.556v3.934A39.075,39.075,0,0,0,83.586,467.6H98.705a23.94,23.94,0,0,0,23.912-23.913v-184.2a23.94,23.94,0,0,0-23.912-23.913H83.586a39.074,39.074,0,0,0-39.03,39.03v3.935H38.583v-.727C38.583,157.933,136.116,60.4,256,60.4s217.417,97.533,217.417,217.416v.727h-5.973v-3.935a39.074,39.074,0,0,0-39.03-39.03H413.3a23.94,23.94,0,0,0-23.912,23.913v184.2A23.94,23.94,0,0,0,413.3,467.6h15.119a39.075,39.075,0,0,0,39.03-39.031v-3.934h17.018A27.571,27.571,0,0,0,512,397.1V306.081A27.578,27.578,0,0,0,489.417,279Zm-428.861-4.39a23.056,23.056,0,0,1,23.03-23.03H98.705a7.921,7.921,0,0,1,7.912,7.913v184.2a7.921,7.921,0,0,1-7.912,7.913H83.586a23.056,23.056,0,0,1-23.03-23.031Zm-16,134.027H27.538A11.552,11.552,0,0,1,16,397.1V306.081a11.551,11.551,0,0,1,11.538-11.538H44.556Zm406.888,19.934a23.056,23.056,0,0,1-23.03,23.031H413.3a7.921,7.921,0,0,1-7.912-7.913v-184.2a7.921,7.921,0,0,1,7.912-7.913h15.119a23.056,23.056,0,0,1,23.03,23.03ZM496,397.1a11.552,11.552,0,0,1-11.538,11.539H467.444V294.543h17.018A11.551,11.551,0,0,1,496,306.081Z"></path></svg>
                     </a>
                </div>

                @if(!empty($date_change_request))
                    @php($dc = $date_change_request)
                    <div class="clear-both"></div>
                    <div class="mt-4 p-4 rounded-lg" style="background-color:#111; border:1px solid #333;">
                        <p class="text-sm mb-2">
                            {{ __('طلب تعديل التواريخ') }}:
                            <b>{{ $dc->new_check_in->format('Y-m-d') }} → {{ $dc->new_check_out->format('Y-m-d') }}</b>
                        </p>
                        @if($dc->status === 'awaiting_payment')
                            <p class="text-xs mb-3" style="color:#f0ad4e;">
                                {{ __('بانتظار دفع الفرق لإتمام التعديل') }}
                                @if((float) $dc->price_delta > 0)
                                    (<b style="color:#e74c3c;">+{{ number_format(abs((float) $dc->price_delta), 2) }} SAR</b>)
                                @endif
                            </p>
                            <button class="dc-retry-pay-btn py-2 px-4 rounded-md text-white ml-2" style="background:#3085d6;" data-request-id="{{ $dc->id }}">
                                {{ __('إكمال الدفع') }}
                            </button>
                            <button class="dc-cancel-req-btn py-2 px-4 rounded-md text-white" style="background:#d33;" data-request-id="{{ $dc->id }}">
                                {{ __('إلغاء طلب التعديل') }}
                            </button>
                        @elseif($dc->status === 'pending_review')
                            <p class="text-xs mb-3" style="color:#3498db;">{{ __('طلبك قيد المراجعة — سيتم استرداد الفرق بعد الموافقة') }}</p>
                            <button class="dc-cancel-req-btn py-2 px-4 rounded-md text-white" style="background:#d33;" data-request-id="{{ $dc->id }}">
                                {{ __('إلغاء طلب التعديل') }}
                            </button>
                        @endif
                    </div>
                @endif
                <div class="clear-both"></div>
            </div>
            <div class="grid lg:grid-cols-5 gap-6 max-w-full">
                <div class="col-span-3">
                    <img src="{{getImage($booking->apartment,'image')}}" class="w-full h-80 object-cover rounded-lg mb-4" />
                    <ul>
                        <li class="bg-feature border border-feature-border mb-4 rounded-lg p-4">
                            <p class="text-gri float-left rtl:float-right">{{__('booking.number_of_booking')}} :</p>
                            <p class="float-right rtl:float-left">#{{$booking->number_of_booking }}</p><div class="clear-both"></div></li>
                        <li class="bg-feature border border-feature-border mb-4 rounded-lg p-4">
                            <p class="text-gri float-left rtl:float-right">{{__('booking.status')}} :</p>
                            <p class="float-right rtl:float-left {{ $booking->status === 'customer_canceled' ? 'text-red-600' : ($booking->status === 'approved' ? 'text-[#10C13F]' : 'text-gray-600') }}">
                                @if($booking->status === 'customer_canceled')
                                    {{__('api.booking_status_customer_canceled')}}
                                    @if($booking->refund_status === 'pending')
                                        <span class="block text-xs mt-1 text-orange-600">({{__('cms.refund_status_pending')}})</span>
                                    @elseif($booking->refund_status === 'approved')
                                        <span class="block text-xs mt-1 text-green-600">({{__('cms.refund_status_approved')}})</span>
                                    @elseif($booking->refund_status === 'rejected')
                                        <span class="block text-xs mt-1 text-red-600">({{__('cms.refund_status_rejected')}})</span>
                                    @endif
                                @else
                                    {{__('booking.status_'.$booking->status )}}
                                @endif
                            </p>
                            <div class="clear-both"></div>
                        </li>
                        
                        <li class="bg-feature border border-feature-border mb-4 rounded-lg p-4">
                            <p class="text-gri float-left rtl:float-right">  {{__('booking.check_in')}} :</p>
                            <p class="float-right rtl:float-left">{{$booking->check_in?->format('y-m-d')}}</p><div class="clear-both"></div></li>
                        <li class="bg-feature border border-feature-border mb-4 rounded-lg p-4">
                            <p class="text-gri float-left rtl:float-right">     {{__('booking.check_out')}} :</p>
                            <p class="float-right rtl:float-left">{{$booking->check_out?->format('y-m-d')}}</p><div class="clear-both"></div></li>
                            @if($review)
                                <li class="bg-feature border border-feature-border mb-4 rounded-lg p-4">
                                    <p class="text-gri float-left rtl:float-right">     {{__('booking.review')}} :</p>
                                    <p class="float-right rtl:float-left">{{$review->review_text}}</p><div class="clear-both"></div>
                                </li>
                           
                                <li class="bg-feature border border-feature-border mb-4 rounded-lg p-4">
                                    <p class="text-gri float-left rtl:float-right">     {{__('booking.rating')}} :</p>
                                    <p class="float-right rtl:float-left">
                                        @for($i = 1; $i <= $review->rating; $i++)
                                            <svg class="w-6 inline mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 23.734 22.812">
                                                <path id="Path_1199" fill="#EF552C" data-name="Path 1199" d="M66.612,94.408l1.314,4.042a1.99,1.99,0,0,0,1.891,1.376h4.252a1.988,1.988,0,0,1,1.167,3.6l-3.438,2.5a1.987,1.987,0,0,0-.721,2.225l1.31,4.042a1.986,1.986,0,0,1-3.058,2.221l-3.438-2.5a1.983,1.983,0,0,0-2.337,0l-3.442,2.5a1.986,1.986,0,0,1-3.058-2.221l1.314-4.042a1.992,1.992,0,0,0-.721-2.225l-3.442-2.5a1.989,1.989,0,0,1,1.17-3.6h4.252a1.981,1.981,0,0,0,1.887-1.376l1.314-4.042a1.988,1.988,0,0,1,3.783,0" transform="translate(-52.854 -92.533)"></path>
                                            </svg>
                                        @endfor
                                        
                                    </p><div class="clear-both"></div>
                                </li>
                            @endif
                    </ul>
                </div>
                <div class=" border border-feature-border rounded-lg py-5 col-span-2">
                    <div class="border-b border-feature-border pb-5 mb-5 px-5">
                        <p class="float-left rtl:float-right font-semibold">   {{__('booking.info')}} </p>
                        <svg class="float-right rtl:float-left text-price" xmlns="http://www.w3.org/2000/svg" width="21" height="21" viewBox="0 0 21 21">
                            <g id="event" transform="translate(-1.5 -1.5)">
                                <rect id="Rectangle_19429" data-name="Rectangle 19429" width="20" height="18" rx="4" transform="translate(2 4)" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="1"/>
                                <path id="Path_4484" data-name="Path 4484" d="M2,8A4,4,0,0,1,6,4H18a4,4,0,0,1,4,4V9H2Z" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="1"/>
                                <path id="Path_4485" data-name="Path 4485" d="M6,2V6M18,2V6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="1"/>
                                <path id="Path_4486" data-name="Path 4486" d="M12,12l1.458,1.994,2.347.77-1.446,2-.007,2.47L12,18.48l-2.351.756-.007-2.47-1.446-2,2.347-.77Z" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="1"/>
                            </g>
                        </svg>
                        <div class="clear-both"></div>
                    </div>
                    <div class="bg-feature border border-feature-border rounded-lg mx-5 my-3 px-4 py-3">
                        <p class="float-left rtl:float-right text-xs">{{__('booking.check_in_time')}} <span class="block font-semibold text-sm">
                            {{Config::get('settings.check_in_time')}}    
                        </span></p>
                        <p class="float-right rtl:float-left text-xs w-2/4 border-l border-feature-border text-right">{{__('booking.check_out_time')}}  <span class="block font-semibold text-sm">
                            {{Config::get('settings.check_out_time')}}    
                        </span></p>
                        <div class="clear-both"></div>
                    </div>
                    {{-- <div class="bg-feature border border-feature-border rounded-lg mx-5 my-3 p-4">
                        <p class="float-left rtl:float-right text-sm">{{__('booking.link')}}   :</p>
                        <a class="float-right rtl:float-left text-sm underline decoration-solid">
                            {{__('booking.go_to_link')}}
                        </a>
                        <div class="clear-both"></div>
                    </div> --}}

                    @if($booking->status == 'approved')
                    <div class="border border-price bg-[#fdeee9] rounded-lg mx-5 px-3 py-5 relative pin text-price">
                        <svg class="float-left rtl:float-right" fill="currentColor" id="fi_16916738" height="32" viewBox="0 0 24 24" width="32" xmlns="http://www.w3.org/2000/svg">
                            <path d="m10.5 5.5c0 .27612-.22388.5-.5.5s-.5-.22388-.5-.5c0-.27618.22388-.5.5-.5s.5.22382.5.5zm2.5-.5c-.27612 0-.5.22382-.5.5 0 .27612.22388.5.5.5s.5-.22388.5-.5c0-.27618-.22388-.5-.5-.5zm-6 0c-.27612 0-.5.22382-.5.5 0 .27612.22388.5.5.5s.5-.22388.5-.5c0-.27618-.22388-.5-.5-.5zm3 3c-.27612 0-.5.22382-.5.5 0 .27612.22388.5.5.5s.5-.22388.5-.5c0-.27618-.22388-.5-.5-.5zm3 0c-.27612 0-.5.22382-.5.5 0 .27612.22388.5.5.5s.5-.22388.5-.5c0-.27618-.22388-.5-.5-.5zm-6 0c-.27612 0-.5.22382-.5.5 0 .27612.22388.5.5.5s.5-.22388.5-.5c0-.27618-.22388-.5-.5-.5zm3 3c-.27612 0-.5.22382-.5.5 0 .27612.22388.5.5.5s.5-.22388.5-.5c0-.27618-.22388-.5-.5-.5zm3 0c-.27612 0-.5.22382-.5.5 0 .27612.22388.5.5.5s.5-.22388.5-.5c0-.27618-.22388-.5-.5-.5zm-6 0c-.27612 0-.5.22382-.5.5 0 .27612.22388.5.5.5s.5-.22388.5-.5c0-.27618-.22388-.5-.5-.5zm13.75 5v2c0 .96484-.78516 1.75-1.75 1.75h-8.89307c-1.39453 0-2.60693-.98633-2.81982-2.29395-.13086-.80566.09424-1.62207.61768-2.2373.52393-.61523 1.2876-.96875 2.09521-.96875h9c.96484 0 1.75.78516 1.75 1.75zm-1.5 0c0-.1377-.1123-.25-.25-.25h-9c-.36719 0-.71436.16113-.95264.44043-.2417.28418-.34082.64844-.27979 1.02539.09619.58984.67188 1.03418 1.33936 1.03418h8.89307c.1377 0 .25-.1123.25-.25zm-9.25.5c-.27612 0-.5.22382-.5.5 0 .27612.22388.5.5.5s.5-.22388.5-.5c0-.27618-.22388-.5-.5-.5zm4 4.75h-8c-.68945 0-1.25-.56055-1.25-1.25v-16c0-.68945.56055-1.25 1.25-1.25h8c.68945 0 1.25.56055 1.25 1.25v9h1.5v-9c0-1.5166-1.2334-2.75-2.75-2.75h-8c-1.5166 0-2.75 1.2334-2.75 2.75v16c0 1.5166 1.2334 2.75 2.75 2.75h8c1.16302 0 2.15375-.72784 2.55518-1.75h-1.84424c-.20447.14587-.4411.25-.71094.25z"></path>
                        </svg>
                        <p class="absolute font-semibold text-black">{{__('booking.passcode')}}</p>
                        <p class="float-right rtl:float-left tracking-wider py-1">
                            {{ $active_passcode?->keyboard_pwd ?? __('booking.no_passcode') }}
                        </p>
                        <div class="clear-both"></div>
                    </div>
                    @endif
                    <p class="font-semibold py-4 mx-5">
                        {{__('booking.summary')}}
                    </p>
                    <p class="text-title mx-5">  {{__('booking.night_price')}} <span class="float-right rtl:float-left font-semibold">{{$booking->total_price/$booking->number_of_nights }} SAR</span></p>
                    @if($booking->coupon_code != null)
                        <p class="text-title mx-5">  {{__('booking.copon').' ( ' .$booking->coupon_code.' ) '}} <span class="float-right rtl:float-left font-semibold">{{$booking->discount}} SAR</span></p>
                    @endif
                    <div class="bg-feature border border-feature-border rounded-lg mx-5 mt-4 p-3">
                    
                        <p>         {{__('booking.summary')}} (    {{$booking->number_of_nights   .' '.__('booking.nights')}})

                            <span class="font-normal text-base text-reviews">
                                ({{__('apartment.price_tax') }})
                            </span>
                        </p>
                        <p class="font-semibold text-lg">
                            {{$booking->final_price}}
                            SAR</p>
                    </div>
                </div>
            </div>
        </div>
    </div>


</section>

<div class="popup modal" id="popup-1" style="display: none;">
    <div class="popup-contain text-center">
        <p class="p-5 border-b border-border text-left">
            {{__('booking.booking_success')}}
        </p>
        <img class="inline-block my-10" src="{{asset('assets/img/success.svg')}}" />
        <p class="font-semibold">
        
            {{__('booking.booking_confirmed_message')}}
            <br>
            {{__('booking.number_of_booking')}}: <span class="text-price" dir="ltr">#{{ $booking->number_of_booking }}</span>
        </p>
        <div class="md:grid md:grid-cols-2 md:gap-5 max-w-full my-10 mx-5">
            <a href="{{ route('customer.booking.details', $booking->number_of_booking) }}"  class="py-4 rounded-lg bg-price text-white">
                {{__('booking.view_booking')}}
            </a>
            <button id="closeMe"  class="py-4 rounded-lg bg-feature">
                {{__('booking.close')}}
            </button>
        </div>
    </div>
</div>

<div class="popup modal" id="popup-2">
    <div class="popup-contain text-center">
        <p class="p-5 border-b border-border text-left">
            {{__('booking.review')}}
        </p>
        <img class="inline-block mt-8 mb-5" src="{{asset('assets/img/goodbye.png')}}" />
        <p class="font-semibold text-lg mb-4">
            {{__('booking.review_message')}}
        </p>
        <p class="text-sm">
            {{__('booking.review_message_2')}}
        </p>
        <form id="review-form">
            <div class="mt-4 mx-5">
                <!-- Radio Buttons for Stars -->
                <section>
                    @for($i = 1; $i <= 5; $i++)
                        <label for="feedback-{{ $i }}" class="cursor-pointer">
                            <input 
                                class="hidden" 
                                value="{{ $i }}" 
                                type="radio" 
                                name="rating" 
                                id="feedback-{{ $i }}" 
                                {{ $i == 5 ? 'checked' : '' }} 
                            />
                            <svg class="w-6 inline-block" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 23.734 22.812">
                                <path id="Path_1199" data-name="Path 1199" d="M66.612,94.408l1.314,4.042a1.99,1.99,0,0,0,1.891,1.376h4.252a1.988,1.988,0,0,1,1.167,3.6l-3.438,2.5a1.987,1.987,0,0,0-.721,2.225l1.31,4.042a1.986,1.986,0,0,1-3.058,2.221l-3.438-2.5a1.983,1.983,0,0,0-2.337,0l-3.442,2.5a1.986,1.986,0,0,1-3.058-2.221l1.314-4.042a1.992,1.992,0,0,0-.721-2.225l-3.442-2.5a1.989,1.989,0,0,1,1.17-3.6h4.252a1.981,1.981,0,0,0,1.887-1.376l1.314-4.042a1.988,1.988,0,0,1,3.783,0" transform="translate(-52.854 -92.533)"></path>
                              </svg>
                        </label>
                    @endfor
                </section>
        
                <!-- Review Text -->
                <textarea class="w-full h-24 w-full mt-4 p-2 resize-none border border-border rounded-lg" name="review_text" placeholder="{{__('booking.review_placeholder')}}"></textarea>
        
                <!-- Hidden Apartment ID -->
                <input type="hidden" name="apartment_id" value="{{ $booking->apartment_id  }}" />
            </div>
        
            <div class="md:grid md:grid-cols-1 md:gap-5 max-w-full my-10 mx-5">
                <button type="submit" class="py-4 rounded-lg bg-price text-white">
                    {{__('booking.review_submit')}}
                </button>
            </div>
        </form>
        
        
    </div>
</div>

@endsection
@if(request()->query('showPopup') == 1)
@push('js')
<script>
  
  $.fancybox.open({
        src: '#popup-1',
        type: 'inline',
        touch: false,
        clickSlide: false,
        clickOutside: false,
        afterShow: function() {
            
        }
    });

$('#closeMe').on('click',function(){
      
  $.fancybox.close({
        src: '#popup-1',
    });

});
</script>
@endpush
@endif


@push('js')
<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Flatpickr (same date picker as the booking flow) — default English LTR for guaranteed header/day alignment -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
@include('customer.section.script-form')
<script>

$(document).ready(function() {
    // التأكد من تحميل SweetAlert2
    if (typeof Swal === 'undefined') {
        console.error('SweetAlert2 is not loaded');
        return;
    }
    
    $(".cancel-booking-btn").click(function() {
        var bookingId = $(this).data("booking-id");
        Swal.fire({
            title: "{{ __('booking.are_you_sure') }}",
            text: "{{ __('booking.cancel_booking_confirmation') }}",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '{{ __("booking.yes") }}',
            cancelButtonText: '{{ __("booking.no") }}',
            buttonsStyling: true
        }).then((result) => {
            if (result.isConfirmed) {
                HoldOn.open();
                $.ajax({
                    url: "{{ route('web-booking.cancel') }}",
                    type: "POST",
                    headers: {
                        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
                    },
                    data: {
                        booking_id: bookingId
                    },
                    success: function(response) {
                        HoldOn.close();
                        Swal.fire({
                            icon: 'success',
                            title: "{{__('booking.success')}}",
                            text: "{{__('api.booking_canceled_successfully')}}",
                            confirmButtonText: "{{__('booking.ok')}}",
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.reload();
                            }
                        });
                    },
                    error: function(xhr) {
                    HoldOn.close();
                    let errorMessage = "{{ __('booking.error_message') }}"; 
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message; 
                    }
                    Swal.fire({
                        icon: 'error',
                        title: "{{ __('booking.error') }}",
                        text: errorMessage,
                        button: true,
                    });
                }

                });
            }
        });
    });

    // ===== تعديل تواريخ الحجز =====
    var dcCsrf = $('meta[name="csrf-token"]').attr("content");

    // نبني قائمة التواريخ المحجوبة بنفس منطق حجز الوحدة، مع استثناء نطاق الحجز الحالي (مسموح للعميل).
    function dcBuildDisabled(bookings, ownIn, ownOut) {
        var own = new Set();
        var c = new Date(ownIn), end = new Date(ownOut);
        while (c < end) { own.add(c.toISOString().split('T')[0]); c.setDate(c.getDate() + 1); }

        var dates = [];
        (bookings || []).forEach(function (b) {
            var cur = new Date(b.check_in), co = new Date(b.check_out);
            while (cur < co) {
                var d = cur.toISOString().split('T')[0];
                if (!own.has(d)) { dates.push(d); }
                cur.setDate(cur.getDate() + 1);
            }
        });
        return dates;
    }

    function dcOpenModal(bookingId, curIn, curOut, disabledDates) {
        Swal.fire({
            title: "{{ __('تعديل التواريخ') }}",
            html:
                // نفس تخطيط حقول التواريخ في صفحة الحجز (apartment/show.blade.php)
                '<div class="flex flex-wrap -mx-2">' +
                    '<div class="flex flex-col w-1/2 px-2">' +
                        '<label for="dc-in" class="mb-1 font-semibold">{{ __('apartment.checkin_date') }}</label>' +
                        '<input type="date" id="dc-in" class="bg-blackopacity border border-gray-300 rounded-lg h-12 px-3 w-full" value="' + curIn + '">' +
                    '</div>' +
                    '<div class="flex flex-col w-1/2 px-2">' +
                        '<label for="dc-out" class="mb-1 font-semibold">{{ __('apartment.checkout_date') }}</label>' +
                        '<input type="date" id="dc-out" class="bg-blackopacity border border-gray-300 rounded-lg h-12 px-3 w-full" value="' + curOut + '">' +
                    '</div>' +
                '</div>',
            width: 520,
            showCancelButton: true,
            confirmButtonText: "{{ __('booking.next') }}",
            cancelButtonText: "{{ __('booking.no') }}",
            focusConfirm: false,
            didOpen: function () {
                // نفس إعدادات flatpickr في صفحة الحجز (apartment/js.blade.php → commonOptions)
                // static=false هنا فقط: داخل مودال SweetAlert لا يمكن تثبيت التقويم بجانب الحقل
                // (يُقص أسفل الأزرار) — يظهر منسدلًا فوق المودال بنفس الشكل.
                // position=auto (الافتراضي): يفتح أسفل الحقل وينقلب فوقه فقط عند عدم وجود مساحة.
                // نعرض التقويم LTR افتراضياً (بلا locale عربي) لضمان تطابق رؤوس الأيام مع أرقامها؛
                // الـ RTL داخل مودال SweetAlert كان يفكّ المحاذاة. التقويم للاختيار فقط والتواريخ رقمية.
                var commonOptions = {
                    dateFormat: "Y-m-d",
                    minDate: "today",
                    allowInput: false,
                    disable: disabledDates,
                    time_24hr: true,
                    weekNumbers: false,
                    static: false,
                    enableTime: false,
                    noCalendar: false,
                    inline: false,
                };
                // نُحلّل التاريخ كمنتصف ليل محلي (وليس UTC) وإلا يصبح minDate بعد منتصف الليل المحلي
                // فيُرفَض تاريخ المغادرة الافتراضي (منتصف الليل المحلي) ويُفرّغ الحقل.
                var minOut = new Date(curIn + 'T00:00:00'); minOut.setDate(minOut.getDate() + 1);

                window._dcOut = flatpickr("#dc-out", Object.assign({}, commonOptions, { defaultDate: curOut, minDate: minOut }));
                window._dcIn = flatpickr("#dc-in", Object.assign({}, commonOptions, {
                    defaultDate: curIn,
                    onChange: function (selectedDates) {
                        if (selectedDates.length > 0) {
                            var checkinDate = selectedDates[0];
                            var minCheckoutDate = new Date(checkinDate);
                            minCheckoutDate.setDate(minCheckoutDate.getDate() + 1);

                            window._dcOut.set('minDate', minCheckoutDate);
                            if (!window._dcOut.selectedDates.length || window._dcOut.selectedDates[0] <= checkinDate) {
                                window._dcOut.setDate(minCheckoutDate, true);
                            }
                        }
                    }
                }));

                // نضمن إبراز التاريخ المختار (خلفية برتقالية) في كلا الحقلين — بعض حالات type=date
                // لا تُعلّم defaultDate كـ selected، فنفرضه يدوياً.
                if (curIn) { window._dcIn.setDate(curIn, false); }
                if (curOut) { window._dcOut.setDate(curOut, false); }

                // المودال fixed بينما التقويم مُحدد بإحداثيات المستند — أعِد تموضعه عند أي تمرير
                // (صفحة الخلفية أو حاوية المودال) ليبقى ملتصقًا أسفل الحقل.
                window._dcReposition = function () {
                    if (window._dcIn && window._dcIn.isOpen) { window._dcIn._positionCalendar(); }
                    if (window._dcOut && window._dcOut.isOpen) { window._dcOut._positionCalendar(); }
                };
                window.addEventListener('scroll', window._dcReposition, true);
                window.addEventListener('resize', window._dcReposition);
            },
            willClose: function () {
                if (window._dcReposition) {
                    window.removeEventListener('scroll', window._dcReposition, true);
                    window.removeEventListener('resize', window._dcReposition);
                    window._dcReposition = null;
                }
                if (window._dcIn) { window._dcIn.destroy(); window._dcIn = null; }
                if (window._dcOut) { window._dcOut.destroy(); window._dcOut = null; }
            },
            preConfirm: function () {
                var newIn = document.getElementById('dc-in').value;
                var newOut = document.getElementById('dc-out').value;
                if (!newIn) {
                    Swal.showValidationMessage("{{ __('apartment.checkin_required') }}");
                    return false;
                }
                if (!newOut) {
                    Swal.showValidationMessage("{{ __('apartment.checkout_required') }}");
                    return false;
                }
                if (newOut <= newIn) {
                    Swal.showValidationMessage("{{ __('apartment.checkout_greater_than') }}");
                    return false;
                }
                Swal.showLoading();
                return $.ajax({
                    url: "{{ route('web-booking.date-change.calculate') }}",
                    type: "POST",
                    headers: { "X-CSRF-TOKEN": dcCsrf },
                    data: { booking_id: bookingId, new_check_in: newIn, new_check_out: newOut }
                }).then(function (res) {
                    return { quote: res.quote, newIn: newIn, newOut: newOut };
                }).catch(function (xhr) {
                    Swal.showValidationMessage((xhr.responseJSON && xhr.responseJSON.message) || "{{ __('booking.error_message') }}");
                    return false;
                });
            }
        }).then(function (result) {
            if (!result.isConfirmed) { return; }

            var q = result.value.quote;
            var delta = parseFloat(q.price_delta);
            var summary;
            if (delta > 0.001) {
                summary = "{{ __('سيتم تحصيل فرق قدره') }} <b style='color:#e74c3c;'>+" + Math.abs(delta).toFixed(2) + " SAR</b> {{ __('لأجل تأكيد التعديل') }}";
            } else if (delta < -0.001) {
                summary = "{{ __('سيتم استرداد فرق قدره') }} <b style='color:#28a745;'>-" + Math.abs(delta).toFixed(2) + " SAR</b> {{ __('بعد مراجعة الإدارة.') }}";
            } else {
                summary = "<span style='color:#6c757d;'>{{ __('لا يوجد فرق في السعر — سيتم تطبيق التواريخ مباشرة.') }}</span>";
            }

            // زر التأكيد: "دفع" عند وجود فرق مستحق (زيادة)، و"طلب" عدا ذلك.
            var confirmText = delta > 0.001 ? "{{ __('booking.pay') }}" : "{{ __('booking.request') }}";

            Swal.fire({
                title: "{{ __('booking.date_change_confirmation') }}",
                html: "<div style='text-align:center;'>" +
                    q.new_check_in + " → " + q.new_check_out + "<br><br>" +
                    "{{ __('السعر الجديد') }}: <b>" + parseFloat(q.new_price).toFixed(2) + " SAR</b><br>" + summary + "</div>",
                icon: 'question',
                showDenyButton: true,
                denyButtonText: "{{ __('booking.previous') }}",
                showCancelButton: true,
                confirmButtonText: confirmText,
                cancelButtonText: "{{ __('booking.no') }}",
                customClass: { confirmButton: 'dc-confirm-pay' }
            }).then(function (confirm) {
                // "السابق" → أعِد فتح مُنتقي التواريخ محتفظاً بالتواريخ المُختارة، بنفس القيود.
                if (confirm.isDenied) {
                    dcOpenModal(bookingId, result.value.newIn, result.value.newOut, disabledDates);
                    return;
                }
                if (!confirm.isConfirmed) { return; }
                HoldOn.open();
                $.ajax({
                    url: "{{ route('web-booking.date-change.request') }}",
                    type: "POST",
                    headers: { "X-CSRF-TOKEN": dcCsrf },
                    data: { booking_id: bookingId, new_check_in: result.value.newIn, new_check_out: result.value.newOut },
                    success: function (res) {
                        HoldOn.close();
                        if (res.action === 'awaiting_payment' && res.redirect) {
                            window.location.href = res.redirect;
                            return;
                        }
                        Swal.fire({
                            icon: 'success',
                            title: "{{ __('booking.success') }}",
                            text: res.message,
                            confirmButtonText: "{{ __('booking.ok') }}"
                        }).then(function () { window.location.reload(); });
                    },
                    error: function (xhr) {
                        HoldOn.close();
                        Swal.fire({
                            icon: 'error',
                            title: "{{ __('booking.error') }}",
                            text: (xhr.responseJSON && xhr.responseJSON.message) || "{{ __('booking.error_message') }}"
                        });
                    }
                });
            });
        });
    }

    $(".edit-dates-btn").click(function () {
        if ($(this).is('[disabled]')) { return; }
        var bookingId = $(this).data("booking-id");
        var curIn = $(this).data("check-in");
        var curOut = $(this).data("check-out");

        HoldOn.open();
        $.getJSON("{{ route('apartments.blocked-dates', $booking->apartment_id) }}")
            .done(function (resp) {
                HoldOn.close();
                dcOpenModal(bookingId, curIn, curOut, dcBuildDisabled(resp.booked_days || [], curIn, curOut));
            })
            .fail(function () {
                HoldOn.close();
                dcOpenModal(bookingId, curIn, curOut, dcBuildDisabled([], curIn, curOut));
            });
    });

    // منع إدخال تاريخ خروج غير صحيح يدويًا — نفس سلوك صفحة الحجز
    $(document).on('change', '#dc-out', function () {
        var checkinVal = $('#dc-in').val();
        var checkoutVal = $(this).val();
        if (checkinVal && checkoutVal && new Date(checkoutVal) <= new Date(checkinVal)) {
            alert("{{ __('apartment.checkout_greater_than') }}");
            $(this).val('');
        }
    });

    // إكمال/إعادة دفع فرق تعديل التواريخ
    $(document).on('click', '.dc-retry-pay-btn', function () {
        var requestId = $(this).data("request-id");
        HoldOn.open();
        $.ajax({
            url: "{{ url('web-booking/date-change') }}/" + requestId + "/retry-payment",
            type: "POST",
            headers: { "X-CSRF-TOKEN": dcCsrf },
            success: function (res) {
                if (res.action === 'awaiting_payment' && res.redirect) {
                    window.location.href = res.redirect;
                    return;
                }
                HoldOn.close();
                Swal.fire({
                    icon: 'success',
                    title: "{{ __('booking.success') }}",
                    text: res.message,
                    confirmButtonText: "{{ __('booking.ok') }}"
                }).then(function () { window.location.reload(); });
            },
            error: function (xhr) {
                HoldOn.close();
                Swal.fire({
                    icon: 'error',
                    title: "{{ __('booking.error') }}",
                    text: (xhr.responseJSON && xhr.responseJSON.message) || "{{ __('booking.error_message') }}"
                });
            }
        });
    });

    // إلغاء طلب تعديل التواريخ (يحرّر النافذة المحجوزة)
    $(document).on('click', '.dc-cancel-req-btn', function () {
        var requestId = $(this).data("request-id");
        Swal.fire({
            title: "{{ __('booking.are_you_sure') }}",
            text: "{{ __('سيتم إلغاء طلب تعديل التواريخ.') }}",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: "{{ __('booking.yes') }}",
            cancelButtonText: "{{ __('booking.no') }}"
        }).then(function (result) {
            if (!result.isConfirmed) { return; }
            HoldOn.open();
            $.ajax({
                url: "{{ url('web-booking/date-change') }}/" + requestId + "/cancel",
                type: "POST",
                headers: { "X-CSRF-TOKEN": dcCsrf },
                success: function (res) {
                    HoldOn.close();
                    Swal.fire({
                        icon: 'success',
                        title: "{{ __('booking.success') }}",
                        text: res.message,
                        confirmButtonText: "{{ __('booking.ok') }}"
                    }).then(function () { window.location.reload(); });
                },
                error: function (xhr) {
                    HoldOn.close();
                    Swal.fire({
                        icon: 'error',
                        title: "{{ __('booking.error') }}",
                        text: (xhr.responseJSON && xhr.responseJSON.message) || "{{ __('booking.error_message') }}"
                    });
                }
            });
        });
    });
});


 
$(document).ready(function () {
    $('#review-form').on('submit', function (e) {
        e.preventDefault();  
        let formData = {
            rating: $('input[name="rating"]:checked').val(),
            review_text: $('textarea[name="review_text"]').val(),
            apartment_id: $('input[name="apartment_id"]').val(),
            booking_id: '{{ $booking->id }}'
        };
        $.ajax({
            url: '{{ route('customer.post.review') }}',  
            method: 'POST',
            data: formData,
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
            },
            success: function(response) {
                HoldOn.close();
                Swal.fire({
                    icon: 'success',
                    title: "{{__('booking.success')}}",
                    text: "{{__('booking.success_message')}}",
                    button: true,
                });
                window.location.reload();
            },
            error: function(xhr) {
                HoldOn.close();
                Swal.fire({
                    icon: 'error',
                    title: "{{__('booking.error')}}",
                    text: "{{__('booking.error_message')}}",
                    button: true,
                });
            }
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const radioInputs = document.querySelectorAll('input[name="rating"]');
    const labels = document.querySelectorAll('label[for^="feedback-"]');
    updateStars(5);
    radioInputs.forEach((radio) => {
        radio.addEventListener('change', function () {
            updateStars(parseInt(this.value));
        });
    });

    function updateStars(selectedValue) {
        labels.forEach((label, index) => {
            const svg = label.querySelector('svg');
            if (index < selectedValue) {
                svg.innerHTML = `
                    <path id="Path_1199" fill="#EF552C" data-name="Path 1199" d="M66.612,94.408l1.314,4.042a1.99,1.99,0,0,0,1.891,1.376h4.252a1.988,1.988,0,0,1,1.167,3.6l-3.438,2.5a1.987,1.987,0,0,0-.721,2.225l1.31,4.042a1.986,1.986,0,0,1-3.058,2.221l-3.438-2.5a1.983,1.983,0,0,0-2.337,0l-3.442,2.5a1.986,1.986,0,0,1-3.058-2.221l1.314-4.042a1.992,1.992,0,0,0-.721-2.225l-3.442-2.5a1.989,1.989,0,0,1,1.17-3.6h4.252a1.981,1.981,0,0,0,1.887-1.376l1.314-4.042a1.988,1.988,0,0,1,3.783,0" transform="translate(-52.854 -92.533)"></path>
                `;
            } else {
                svg.innerHTML = `
                    <path d="M66.612,94.408l1.314,4.042a1.99,1.99,0,0,0,1.891,1.376h4.252a1.988,1.988,0,0,1,1.167,3.6l-3.438,2.5a1.987,1.987,0,0,0-.721,2.225l1.31,4.042a1.986,1.986,0,0,1-3.058,2.221l-3.438-2.5a1.983,1.983,0,0,0-2.337,0l-3.442,2.5a1.986,1.986,0,0,1-3.058-2.221l1.314-4.042a1.992,1.992,0,0,0-.721-2.225l-3.442-2.5a1.989,1.989,0,0,1,1.17-3.6h4.252a1.981,1.981,0,0,0,1.887-1.376l1.314-4.042a1.988,1.988,0,0,1,3.783,0" transform="translate(-52.854 -92.533)" style="opacity: 0.3;"></path>
                `;
            }
        });
    }
});


</script>


@endpush


 