@extends('layouts.master')
@push('css')

<style>
#verify_coupon{
    margin-right: 10px !important;
}
    </style>
@endpush
@section('content')


<section class="container py-10">
    <a>
        <img class="inline-block mr-4" src="{{asset('assets/img/back.svg')}}" />
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
                    <div class="float-left rtl:float-right   ltr:mr-5 rtl:ml-5 mb-3 lg:mb-0">
                        <img class="inline-block ltr:mr-2 rtl:ml-2 -translate-y-1" src="{{asset('assets/img/location.svg')}}" />
                        <p class="inline-block font-normal text-xl text-title">
                            {{ $apartment->building->city->ml('name').', '.$apartment->building->ml('name') }}    
                        </p>
                    </div>
                    <div class="float-left rtl:float-right  ltr:mr-5 rtl:ml-5 mb-3 lg:mb-0">
                        <img class="inline-block ltr:mr-2 rtl:ml-2 -translate-y-0.5" src="{{asset('assets/img/star.svg')}}" />
                        <p class="inline-block font-normal text-base text-reviews">
                            {{ $apartment->rating }}
                        </p>
                    </div>
                    <div class="float-left rtl:float-right  ltr:mr-5 rtl:ml-5 mb-3 lg:mb-0">
                        <img class="inline-block ltr:mr-2 rtl:ml-2 -translate-y-0.5" src="{{asset('assets/img/feature-3.svg')}}" />
                        <p class="inline-block font-normal text-base text-reviews">
                            {{__('apartment.area'). $apartment->area }}
                        </p>
                    </div>
                    <div class="clear-both"></div>
                    <form action="{{route('web-booking.add')}}" method="POST">
                        <input type="hidden" id="apartment_id" name="apartment_id" value="{{$apartment->id}}">
                        <input type="hidden" id="number_of_nights" name="number_of_nights" value="{{$number_of_nights}}">
                        <input type="hidden" name="total_price" value="{{$total_price}}">
                        <input type="hidden" name="check_in" value="{{request()->checkin}}">
                        <input type="hidden" name="check_out" value="{{request()->checkout}}">
                       
                        @csrf
                        <h1 class="font-semibold text-2xl text-title border-t border-blackopacity pt-8 mt-8">
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
                                    <img class="inline-block" src="{{$item['icon']}}" width="50"/>
                                    <p class="inline-block ml-4 font-normal text-sm text-title">
                                        {{$item['explanation']}}
                                    </p>
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
                  
                    <p class="font-light text-base text-gri mt-3 mb-2 ease-in-out duration-900 max-h-[72px] overflow-hidden">
                        {!!$policy_description!!}
                    </p>
                    <button class="showmore font-normal text-sm text-blue underline"> </button>
                </div>
            </div>
            <div class="hidden xl:block xl:basis-4/12 xl:pl-5 rtl:xl:pr-5">
                <div class="border border border-filterborder rounded-xl px-5 py-6">
                    <div class="rounded-xl overflow-hidden border border-border">
                        <div class="relative">
                            <div class="slider" style="max-width: 450px">
                                @foreach ($apartment->getMedia('image') as $image)
                                   <div>
                                    <a style="width: 100%; max-width:100%; display:block" href="{{$apartment->link}}">
                                        <img 
                                            class="object-cover w-full" 
                                            src="{{ $image->getUrl('grid') }}" 
                                            alt="@lang('apartment.apartment_name_default')"
                                        />
                                    </a>

                                   </div>
                                @endforeach
                            </div>
                            
                        </div>
                    </div>               
                  
                        <p class="mb-4 font-normal text-base text-reviews">
                            <span class="font-bold text-2xl text-black translate-y-0.5 inline-block">
                                {{$apartment->price}} 
                            </span> 
                            {{__('apartment.sar')}}
                        </p>
                        
                    <p class="font-normal text-sm text-reviews text-center mb-6">
                        {{__('booking.not_charged_yet')}}
                    </p>
                    <ul>
                        <li class="mb-4 font-semibold text-sm text-title">
                            <span> {{__('booking.one_night')}}  </span>
                            <span class="float-right rtl:float-left">
                                {{$apartment->price .' '. __('apartment.price')}}
                            </span>
                            <div class="clear-both"></div>
                        </li>
                        <li class="mb-4 font-semibold text-sm text-title">
                            <span>
                                {{__('apartment.number_of_nights') }}

                            </span>
                            <span class="float-right rtl:float-left">
                                {{$number_of_nights }}
                            </span>
                            <div class="clear-both"></div>
                        </li>
                         
                    </ul>
                    
                    <p class="py-4 px-3 bg-filterbackground border border-filterborder rounded-lg font-semibold text-sm text-title mb-4">
                        {{__('booking.total_price')}}
                        <span id="total_price" class="float-right rtl:float-left">
                            {{$total_price .' '. __('apartment.price')}}    
                        </span></p>
                    <ul>
                        @foreach ($payment_details as $item)
                            <li>
                                <a class="block border border-filterborder py-3 px-4 rounded-lg mb-2 hover:bg-filterborder ease-in-out duration-300">
                                    <img class="inline-block" src="{{$item['icon']}}" width="50" />
                                    <p class="inline-block ml-4 font-normal text-sm text-title">
                                        {{$item['explanation']}}
                                    </p>
                                </a>
                            </li>
                        @endforeach
                        
                         
                    </ul>
                    <div class="flex flex-row items-center space-x-2">
                        <input type="text" id="coupon_code" name="coupon_code" 
                        placeholder="@lang('apartment.coupon_code')" class="border border-gray-300 rounded-lg h-12 px-3 flex-1">
                        <button type="button" id="verify_coupon" class="mr-4 bg-price rounded-lg h-12 px-4 font-semibold text-white">
                            @lang('apartment.verify_coupon')
                        </button>
                    </div>
                    <div id="coupon_message" class="mt-2 text-green-500"></div>
                    
                    <button class="bg-price rounded-lg h-12 w-full font-semibold text-white">
                        {{__('booking.book_now')}}
                    </button>
                </form>
                </div>
            </div>
        </div>
    </div>
</section>








@endsection

@push('js')
<script>
   $(document).ready(function() {
        $('#verify_coupon').on('click', function() {
            const couponCode = $('#coupon_code').val();
            const apartment_id = $('#apartment_id').val();
            const totalNights = $('#number_of_nights').val();
            if (couponCode.trim() === "") {
                $('#coupon_message').text("{{ __('apartment.enter_coupon') }}");
                return;
            }
            $.ajax({
                url: '{{ route("web-booking.coupons.verify") }}',
                method: 'POST',
                data: {
                    code: couponCode,
                    apartment_id: apartment_id,
                    total_nights: totalNights,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    // $('#verify_coupon').prop('disabled', true);
                    discount = response.discount;
                    let discountText = response.type === "percentage" ? "%" : "{{ __('apartment.price_unit') }}";
                    $('#coupon_message').text("{{ __('apartment.coupon_applied') }}: " + discount);
                    let total_price = "{{$total_price}}";
                     
                    $('#total_price').text(response.final_price + ' {{ __("apartment.price") }}');

                    calculateNightsAndCost();
                },
                error: function() {
                    $('#coupon_message').text("{{ __('apartment.error_verifying_coupon') }}");
                }
            }); 
        });
   });
</script>
@endpush