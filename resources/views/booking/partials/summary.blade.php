@php($showTax = $showTax ?? false)
<p class="font-semibold py-4 mx-5">
    {{__('booking.summary')}}
</p>
<p class="text-title mx-5">  {{__('booking.night_price')}} <span class="float-right rtl:float-left font-semibold">{{$booking->total_price/$booking->number_of_nights }} {{ __('apartment.price') }}</span></p>
@if($booking->coupon_code != null)
    <p class="text-title mx-5">  {{__('booking.copon').' ( ' .$booking->coupon_code.' ) '}} <span class="float-right rtl:float-left font-semibold">{{$booking->discount}} {{ __('apartment.price') }}</span></p>
@endif
<div class="bg-feature border border-feature-border rounded-lg mx-5 mt-4 p-3">
    <p>         {{__('booking.summary')}} (    {{$booking->number_of_nights   .' '.__('booking.nights')}})@if($showTax)
        <span class="font-normal text-base text-reviews">
            ({{__('apartment.price_tax') }})
        </span>
        @endif</p>
    <p class="font-semibold text-lg">
        {{$booking->final_price}}
        {{ __('apartment.price') }}</p>
</div>
