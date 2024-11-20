@extends('layouts.master')
@push('css')
    <style>
        iframe {
            width: 100%;
            height: 100%;
            border: 0;
        }
        </style>
@endpush
@section('content')
@include('pages.partials.breadcrumb')

<section class="py-12 bg-footer">
    <div class="container">
        <div class="lg:grid lg:grid-cols-2 lg:gap-4 w-full mx-0">
            <div class="pr-0 xl:pr-24">
                <p class="font-normal text-base text-price mb-5">
                    {{$page->{'name_'.app()->getLocale()} ?? ''}}
                </p>
                <p class="font-semibold text-3xl sm:text-5xl mb-6">
                    {!!$page->{'content_'.app()->getLocale()} ?? ''!!}
                </p>
                <p class="font-light text-base text-gri mb-12"> 
                   
                </p>
                <ul>
                    <li class="mb-9">
                        <a href="mailto:{{$email}}">
                            <div class="w-12 h-12 rounded-full me-6 bg-[#fae3dd] text-center pt-3 ltr:float-left rtl:float-right translate-y-1">
                                <img class="inline-block" src="{{asset('assets/img/mail.svg')}}" />
                            </div> 
                            <p class="font-semibold text-lg text-black">{{__('site.send_us_email')}} <br>
                                {{$email}}     
                            </p>
                        </a>
                    </li>
                    <li class="mb-9">
                        <a>
                            <div class="w-12 h-12 rounded-full me-6 bg-[#fae3dd] text-center pt-3 ltr:float-left rtl:float-right translate-y-1">
                                <img class="inline-block" src="{{asset('assets/img/tel.svg')}}" /></div> 
                                <p class="font-semibold text-lg text-black"> {{__('site.send_phone')}} <br> {{$phone}}</p></a></li>
                    <li class="mb-9"><a><div class="w-12 h-12 rounded-full me-6 bg-[#fae3dd] text-center pt-3 ltr:float-left rtl:float-right translate-y-1">
                        <img class="inline-block" src="{{asset('assets/img/address.svg')}}" /></div> <p class="font-semibold text-lg text-black">
                             {{__('site.address')}} <br> 
                            {{$address}}
                        </p></a></li>
                </ul>
            </div>
            <div class="bg-white border border-border rounded-2xl p-7 sm:p-12">
                <p class="font-semibold text-3xl text-black mb-8">
                    {{__('site.contact_us')}}
                </p>
                <form id="contact-us" method="POST">
                    @csrf
                    <div class="lg:grid lg:grid-cols-2 lg:gap-4 w-full mx-0">
                        <input name="name" class="w-full mb-4 border border-border bg-footer rounded-lg h-12 px-4" type="name" placeholder="{{__('site.name')}}" />
                        <input name="phone" class="w-full mb-4 border border-border bg-footer rounded-lg h-12 px-4" type="phone" placeholder="{{__('site.phone')}}" />
                    </div>
                    <input  name="email" class="w-full mb-4 border border-border bg-footer rounded-lg h-12 px-4" type="email" placeholder="{{__('site.email')}}" />
                    <textarea  name="message" class="w-full mb-4 border border-border bg-footer rounded-lg h-12 h-52 px-4 pt-4 resize-none" placeholder="{{__('site.message')}}"></textarea>
                    
                    
                    <button class="bg-price py-4 px-16 font-normal text-sm text-white rounded-full">
                        {{__('site.send')}}
                        <img class="w-3 inline-block ml-3" src="{{asset('assets/img/slider-right.svg')}}" /></button>

                    {!!  GoogleReCaptchaV3::render(['contact_us_id'=>'contact_us']) !!}
                    {!!  GoogleReCaptchaV3::init() !!}
                </form>
            </div>
        </div>
    </div>
</section> 

<section class="py-12 container">
    <p class="font-semibold text-2xl mb-10">
        {{__('site.location')}}
    </p>
 
    <div class="border border-border rounded-xl p-4">
        <div class="h-52 lg:h-96 rounded-xl overflow-hidden" id="map">
            {!! $map !!}
        </div>
    </div>
</section>

@endsection

@push('js')
@include('customer.section.script-form')

<script>
    $(document).ready(function() {
        $("#contact-us").validate({
            rules: {
                name: "required",
                phone: {
                    required: true,
                    minlength: 9
                },
                massage: "required",
            },
            messages: {
                name: "{{__('customer.first_name_required')}}",
                phone: {
                    required: "{{__('customer.phone_required')}}",
                    minlength: "{{__('customer.phone_min')}}"
                },
                massage: "{{__('customer.massage_required')}}",
            },
            submitHandler: function(form) {
                HoldOn.open({
                    theme: "sk-cube-grid",  
                    message: "{{__('customer.loading_message')}}"  
                });
    
                $.ajax({
                    url: "{{ route('home.contact-us') }}",  
                    method: "POST",
                    data: $(form).serialize(), 
                    success: function(response) {
                        HoldOn.close();
                        Swal.fire({
                            icon: 'success',
                            title: "{{__('customer.success')}}",
                            text: "{{__('customer.success_message')}}",
                            button: true,
                        });
                        location.reload();
                    },
                    error: function(xhr) {
                        HoldOn.close();
                        Swal.fire({
                            icon: 'error',
                            title: "{{__('customer.error')}}",
                            text: "{{__('customer.error_message')}}",
                            button: true,
                        });
                    }
                });
            }
        });
    });
</script>
@endpush