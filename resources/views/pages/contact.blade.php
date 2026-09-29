@extends('layouts.master')
@push('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        #map iframe {
            width: 100%;
            height: 100%;
            border: 0;
        }
        /* Keep validation messages below their field so the form layout never breaks. */
        #contact-us span.contact-error {
            display: block;
            color: #ef4444;
            font-size: 0.8rem;
            margin-top: -0.5rem;
            margin-bottom: 0.75rem;
            text-align: start;
        }
        #contact-us input.contact-error,
        #contact-us textarea.contact-error {
            border-color: #ef4444;
        }
        /* Phone field: keep the placeholder right-aligned like the other RTL
           fields, but flip to LTR — so the leading + and digits start from the
           left — as soon as the field is focused or holds a value. */
        #contact-us .phone-ltr-value {
            direction: rtl;
            text-align: right;
        }
        #contact-us .phone-ltr-value:focus,
        #contact-us .phone-ltr-value:not(:placeholder-shown) {
            direction: ltr;
            text-align: left;
        }
        </style>
@endpush
@section('content')
@include('pages.partials.breadcrumb')

<section class="py-12 bg-footer">
    <div class="container">
        <div class="lg:grid lg:grid-cols-2 lg:gap-4 w-full mx-0">
            <div class="pr-0 xl:pr-24">
                @php
                    $locale = app()->getLocale();
                    $pageName = $page->{'name_'.$locale} ?? '';
                    $pageContent = (string) ($page->{'content_'.$locale} ?? '');
                @endphp
                {{-- Page title. The CMS "content" field is rich text that already
                     contains block-level <p> tags, so it must NOT be wrapped in a
                     <p> (nesting <p> inside <p> makes the browser collapse the outer
                     heading, which is why the title was rendering tiny). Render the
                     page name as the heading and the content as a description inside
                     a <div>. --}}
                <h1 class="font-semibold text-3xl sm:text-5xl text-price mb-5">{{ $pageName }}</h1>
                @if (filled(trim(strip_tags($pageContent))))
                    <div class="font-light text-base text-gritext mb-12">{!! $pageContent !!}</div>
                @endif
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
                    @php $contactPhone = preg_replace('/^00/', '+', (string) $phone); @endphp
                    <li class="mb-9">
                        <a href="tel:{{ $contactPhone }}">
                            <div class="w-12 h-12 rounded-full me-6 bg-[#fae3dd] text-center pt-3 ltr:float-left rtl:float-right translate-y-1">
                                <img class="inline-block" src="{{asset('assets/img/tel.svg')}}" /></div>
                                <p class="font-semibold text-lg text-black"> {{__('site.send_phone')}} <br> <span dir="ltr" style="direction:ltr; unicode-bidi:isolate; display:inline-block;">{{ $contactPhone }}</span></p></a></li>
                    <li class="mb-9"><a><div class="w-12 h-12 rounded-full me-6 bg-[#fae3dd] text-center pt-3 ltr:float-left rtl:float-right translate-y-1">
                        <img class="inline-block" src="{{asset('assets/img/address.svg')}}" /></div> <p class="font-semibold text-lg text-black">
                             {{__('site.address')}} <br> 
                            {{$address}}
                        </p></a></li>
                </ul>
            </div>
            <div class="bg-white border border-border rounded-2xl p-7 sm:p-12">
                <h2 class="font-semibold text-3xl text-black mb-8">
                    {{__('site.contact_us')}}
                </h2>
                <form id="contact-us" method="POST">
                    @csrf
                    <div class="lg:grid lg:grid-cols-2 lg:gap-4 w-full mx-0">
                        <div>
                            <input name="name" class="w-full mb-4 border border-border bg-footer rounded-lg h-12 px-4" type="text" placeholder="{{__('site.name')}}" />
                        </div>
                        <div>
                            <input name="phone" class="phone-ltr-value w-full mb-4 border border-border bg-footer rounded-lg h-12 px-4" type="tel" inputmode="tel" placeholder="{{__('site.phone')}}" />
                        </div>
                    </div>
                    <input  name="email" class="w-full mb-4 border border-border bg-footer rounded-lg h-12 px-4" type="email" placeholder="{{__('site.email')}}" />
                    <textarea  name="message" class="w-full mb-4 border border-border bg-footer rounded-lg h-52 px-4 pt-4 resize-none" placeholder="{{__('site.message')}}"></textarea>
                    
                    
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
    <h2 class="font-semibold text-2xl mb-10">
        {{__('site.location')}}
    </h2>
 
    @php
        // settings.map may hold: a full <iframe> embed, a Google Maps URL (from which we
        // derive a keyless embed via its @lat,lng), or be empty. Handle each so the map
        // always renders as an embedded iframe when coordinates are available.
        $mapEmbedSrc = null;
        if (! empty($map) && ! str_contains($map, '<iframe') && preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $map, $mapCoords)) {
            $mapEmbedSrc = 'https://www.google.com/maps?q='.$mapCoords[1].','.$mapCoords[2].'&z=16&hl='.app()->getLocale().'&output=embed';
        }
    @endphp

    <div class="border border-border rounded-xl p-4">
        <div class="h-52 lg:h-96 rounded-xl overflow-hidden" id="map" aria-label="{{__('site.location')}}">
            @if(! empty($map) && str_contains($map, '<iframe'))
                {!! $map !!}
            @elseif($mapEmbedSrc)
                <iframe src="{{ $mapEmbedSrc }}" width="100%" height="100%" style="border:0;"
                        loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                        title="{{__('site.location')}}"></iframe>
            @else
                <a href="{{ ! empty($map) ? $map : 'https://www.google.com/maps/search/?api=1&query='.urlencode($address) }}"
                   target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center gap-2 font-semibold text-price hover:underline ease-in-out duration-300">
                    <img class="w-5 h-5" src="{{ asset('assets/img/address.svg') }}" alt="" />
                    {{ __('site.view_on_map') }}
                </a>
            @endif
        </div>
    </div>
</section>

@endsection

@push('js')
@include('customer.section.script-form')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        // Phone may contain only digits and an optional leading "+".
        $.validator.addMethod("phoneChars", function (value, element) {
            return this.optional(element) || /^\+?[0-9]+$/.test(value);
        }, "{{__('customer.phone_digits')}}");

        $("#contact-us").validate({
            errorElement: "span",
            errorClass: "contact-error",
            rules: {
                name: "required",
                phone: {
                    required: true,
                    phoneChars: true,
                    minlength: 9
                },
                email: {
                    required: true,
                    email: true
                },
                message: "required",
            },
            messages: {
                name: "{{__('customer.first_name_required')}}",
                phone: {
                    required: "{{__('customer.phone_required')}}",
                    minlength: "{{__('customer.phone_min')}}"
                },
                email: {
                    required: "{{__('customer.email_required')}}",
                    email: "{{__('customer.email_email')}}"
                },
                message: "{{__('customer.message_required')}}",
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