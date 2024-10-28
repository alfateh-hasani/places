@extends('layouts.master')
@push('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@24.6.0/build/css/intlTelInput.css" />
<link rel="stylesheet" href="{{asset('assets/plugin/HoldOn.min.css')}}" />

@endpush
@section('content')

<section class="profile py-5 lg:py-16 bg-[#eff3f6] min-h-screen lg:min-h-min">
    <div class="container">
        <div class="lg:grid lg:grid-cols-4 lg:gap-6 w-full mx-0">
           @include('customer.section.sidebar')
            <div class="col-span-3">
                @include('customer.section.header')
          
                <div class="bg-white py-8 px-6 rounded-2xl mt-5">
                    <div class="mb-6">
                        <svg class="inline-block" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
                            <path id="user" fill="currentColor" d="M11,2A10,10,0,1,0,21,12,10.01,10.01,0,0,0,11,2ZM4.955,19.006a7.532,7.532,0,0,1,5.852-2.941c.035,0,.069.01.1.01h.034c.033,0,.062-.009.095-.01a7.522,7.522,0,0,1,5.9,3.025,9.227,9.227,0,0,1-11.989-.084Zm5.962-3.688c-.037,0-.072.006-.109.007a3.311,3.311,0,1,1,.235,0C11,15.325,10.96,15.318,10.917,15.318Zm6.575,3.275a8.271,8.271,0,0,0-4.607-3.034,4.065,4.065,0,1,0-3.918,0,8.283,8.283,0,0,0-4.556,2.95,9.266,9.266,0,1,1,13.081.087Z" transform="translate(-1 -2)"/>
                        </svg>
                        <p class="inline-block ml-4">
                            {{__('customer.profile')}}
                            <span class="font-semibold text-price">
                                </span>
                            </p>
                    </div>

                    <form id="customerForm" class="md:grid md:grid-cols-2 md:gap-4 w-full mx-0">
                        @csrf
                        <label>
                            <p>
                                {{__('customer.first_name')}}
                            </p>
                            <input name="first_name" value="{{$customer->first_name}}" 
                            class="w-full border border-border rounded-lg h-12" type="text" placeholder="" />
                        </label>
                        <label>
                            <p>   {{__('customer.last_name')}}</p>
                            <input name="last_name" value="{{$customer->last_name}}" class="w-full border border-border rounded-lg h-12" 
                            type="text" placeholder="" />
                        </label>

                        <label>
                            <p>
                                {{__('customer.email')}}
                            </p>
                            <input dir="ltr" type="email" id="email" value="{{$customer->email}}" name="email" class="w-full border border-border rounded-lg h-12" />
                        </label>
                        <label>
                            <p>
                                {{__('customer.phone')}}
                            </p>
                            <input dir="ltr" readonly type="tel"   id="phone" value="{{$customer->phone}}" name="phone" class="w-full border border-border rounded-lg h-12" />
                        </label>
                        <label>
                            <p>
                                {{__('customer.emergency_phone')}}
                            </p>
                            <input dir="ltr" type="tel"   id="emergency_phone" value="{{$customer->emergency_phone}}" name="emergency_phone" class="w-full border border-border rounded-lg h-12" />
                        </label>
                         
                        <button class="h-12 bg-price rounded-lg col-span-2 font-semibold text-white">
                            {{__('customer.save')}}
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@24.6.0/build/js/intlTelInput.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/wow/1.1.2/wow.min.js"></script> 

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.3/jquery.validate.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{asset('assets/plugin/HoldOn.min.js')}}"></script>
<script>
    new WOW().init();   
    const phoneInput = document.querySelector("#phone");
    const emergencyPhoneInput = document.querySelector("#emergency_phone");
    window.intlTelInput(phoneInput, {
        loadUtilsOnInit: "https://cdn.jsdelivr.net/npm/intl-tel-input@24.6.0/build/js/utils.js",
        initialCountry: "SA",
        separateDialCode: true,
    });
    window.intlTelInput(emergencyPhoneInput, {
        loadUtilsOnInit: "https://cdn.jsdelivr.net/npm/intl-tel-input@24.6.0/build/js/utils.js",
        initialCountry: "SA",
        separateDialCode: true,
    });

    const changeIcon = (el) => {
        el.classList.toggle("fa-plus");
        el.classList.toggle("fa-minus");
    };
</script>
<script>
    $(document).ready(function() {
        $("#customerForm").validate({
            rules: {
                first_name: "required",
                last_name: "required",
                email: {
                    required: true,
                    email: true
                },
                emergency_phone: {
                    required: false,
                    minlength: 9
                }
            },
            messages: {
                first_name: "{{__('customer.first_name_required')}}",
                last_name: "{{__('customer.last_name_required')}}",
                email: {
                    required: "{{__('customer.email_required')}}",
                    email: "{{__('customer.email_required_email')}}",
                },
                emergency_phone: {
                    digits: "{{ __('customer.emergency_phone_digits')}}",
                    minlength: "{{__('customer.emergency_phone_minlength')}}",
                }
            },
            submitHandler: function(form) {
                HoldOn.open({
                    theme: "sk-cube-grid", // يمكنك تغيير الثيم هنا
                    message: "{{__('customer.loading_message')}}" // رسالة اختيارية
                });
    
                $.ajax({
                    url: "{{ route('customer.update') }}",  
                    method: "POST",
                    data: $(form).serialize(), 
                    success: function(response) {
                        // إيقاف HoldOn عند النجاح
                        HoldOn.close();
                        Swal.fire({
                            icon: 'success',
                            title: "{{__('customer.success')}}",
                            text: "{{__('customer.success_message')}}",
                            button: true,
                        });
                    },
                    error: function(xhr) {
                        // إيقاف HoldOn عند الفشل
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
    
    
<!-- End Javascript --
@endpush