<footer class="border-t border-blackopacity pt-12">
  <div class="container border-b border-blackopacity pb-6">
    <div class="lg:grid lg:grid-cols-3 lg:gap-8 max-w-full">
      <div>
        <img src="{{ asset('assets/img/logo.svg') }}" alt="Logo" />
        <p class="font-normal text-sm lg:text-base text-black mt-8 text-justify">
          It is a long established fact that a reader will be distracted by the readable
          content of a page when looking at its layout. The point of using Lorem Ipsum is
          that it has a more-or-less normal distribution of letters, as opposed.
        </p>
      </div>
      <div class="my-6 lg:my-0">
        <h6 class="font-semibold text-xl text-black">Site Map</h6>
        <ul class="mt-4 lg:mt-10 grid grid-cols-2 gap-2 max-w-full mx-0">
          <li><a class="block font-light text-black mb-2 lg:mb-5" href="#">Home</a></li>
          <li><a class="block font-light text-black mb-2 lg:mb-5" href="#">Privacy & Policy</a></li>
          <li><a class="block font-light text-black mb-2 lg:mb-5" href="#">Blogs</a></li>
          <li><a class="block font-light text-black mb-2 lg:mb-5" href="#">Terms Of Use</a></li>
          <li><a class="block font-light text-black mb-2 lg:mb-5" href="#">Contact Us</a></li>
          <li><a class="block font-light text-black mb-2 lg:mb-5" href="#">FAQ</a></li>
        </ul>
      </div>
      <div>
        <h6 class="font-semibold text-xl text-black">Contact Us</h6>
        <ul class="mt-4 lg:mt-10">
          <li>
            <a class="block font-light text-black mb-5" href="mailto:info@testmail.com">
              <img class="inline-block mr-3" src="{{ asset('assets/img/mail.svg') }}" alt="Mail Icon" />
              info@testmail.com
            </a>
          </li>
          <li>
            <a class="block font-light text-black mb-5" href="tel:+966138589000">
              <img class="inline-block mr-3" src="{{ asset('assets/img/tel.svg') }}" alt="Phone Icon" />
              +966-13-858-9000
            </a>
          </li>
          <li>
            <a class="block font-light text-black mb-5" href="#">
              <img class="inline-block mr-3" src="{{ asset('assets/img/address.svg') }}" alt="Address Icon" />
              Address Here
            </a>
          </li>
        </ul>
      </div>
    </div>
  </div>

  <div class="container py-4">
    <div class="float-left w-full sm:w-auto text-center sm:text-left mb-3 sm:mb-0">
      <p class="inline-block font-normal text-base text-black">
        All Rights Reserved © {{ date('Y') }}
      </p>
      <a class="inline-block font-normal text-base text-price" href="#">Website Title</a>
    </div>

    <div class="float-right w-full sm:w-auto text-center sm:text-left">
      <ul class="social">
        <li class="inline-block">
          <a class="block w-8 h-8 bg-blackopacity rounded-lg relative" href="#">
            <img class="absolute" src="{{ asset('assets/img/linkedin.svg') }}" alt="LinkedIn" />
          </a>
        </li>
        <li class="inline-block">
          <a class="block w-8 h-8 bg-blackopacity rounded-lg relative" href="#">
            <img class="absolute" src="{{ asset('assets/img/twitter.svg') }}" alt="Twitter" />
          </a>
        </li>
        <li class="inline-block">
          <a class="block w-8 h-8 bg-blackopacity rounded-lg relative" href="#">
            <img class="absolute" src="{{ asset('assets/img/instagram.svg') }}" alt="Instagram" />
          </a>
        </li>
        <li class="inline-block">
          <a class="block w-8 h-8 bg-blackopacity rounded-lg relative" href="#">
            <img class="absolute" src="{{ asset('assets/img/facebook.svg') }}" alt="Facebook" />
          </a>
        </li>
      </ul>
    </div>

    <div class="clear-both"></div>
  </div>
</footer>


<div class="popup modal" id="popup-5">
    <div class="popup-contain text-center">
        <p class="p-5 border-b border-border text-left rtl:text-right">
            @lang('site.login')
        </p>
        <div class="px-5 text-left rtl:text-right pt-8">
            <p class="font-semibold text-xl mb-6">
                @lang('site.welcome_back') 
                <img class="h-8 inline-block" src="{{ asset('assets/img/goodbye.png') }}" alt="Goodbye" />
            </p>
            <form id="login-form" method="post">
                @csrf <!-- Include CSRF token -->
                 <label>
                 <p class="text-sm mb-3">{{ __('site.your_mobile_number') }}</p>
                <div dir="ltr" class="select-box m-0 w-full border border-border rounded-lg">
                                <div style="    direction: ltr !important;  text-align: left !important;" class="selected-option">
                                    <div style="direction: ltr !important;" dir="ltr">
                                        <span class="iconify" data-icon="flag:sa-4x3"></span>
                                        <strong>+966</strong>
                                    </div>
                                    <input  class="phoneNumberInput" style="text-align: left; direction: ltr;" type="tel" id="phoneNumber" name="phone" placeholder="5xxxxxxxxx">
                                </div>
                                
                            </div>
                    </label>
                <div class="md:grid md:grid-cols-1 md:gap-5 max-w-full mt-4">
                    <button type="submit" class="py-4 rounded-lg bg-price text-white w-full">
                        @lang('site.login')
                    </button>
                </div>
            </form>
            <p class="text-sm mt-4 mb-10 text-center">
                @lang('site.dont_have_account') 
                <a class="text-price" href="">@lang('site.register')</a>
            </p>
        </div>
    </div>
</div>


@push('js')
 
<link rel="stylesheet" href="https://conference.kkesh.med.sa/front/assets/css/HoldOn.min.css" />
<script src="https://conference.kkesh.med.sa/front/assets/js/jquery.validate.js"></script>
 
<script src="https://conference.kkesh.med.sa/front/assets/js/HoldOn.min.js"></script>

<script>
    $(document).ready(function() {


       $.validator.addMethod("validPhone", function (value, element) {
        // Convert Arabic numbers to English
        let arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        let englishNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        // Replace Arabic numbers with English numbers
        for (let i = 0; i < 10; i++) {
            let regex = new RegExp(arabicNumbers[i], 'g');
            value = value.replace(regex, englishNumbers[i]);
        }

        // Remove non-digit characters
        value = value.replace(/\D/g, '');

        // Remove leading zeros
        while (value.charAt(0) === '0') {
            value = value.substr(1);
        }

        // Ensure the value is exactly 9 digits
        return this.optional(element) || value.length === 9;
    }, "@lang('site.invalid_phone')");


        $('#login-form').validate({
            ignore: [],
            rules: {
                phone: {
                    required: true ,
                    validPhone: true
                }
            },
            messages: {
                phone: {
                    required: "@lang('site.phone_required')",
                    validPhone: "@lang('site.invalid_phone')" 
                     
                }
            },
            errorPlacement: function(error, element) {
                error.insertAfter($(element).parent().parent()); 
            },
            submitHandler: function(form) {
                HoldOn.open({ theme: "sk-rect" });
                var formData = new FormData(form);
                $.ajax({
                    url: "{{ route('login.step1') }}", // Adjust to your Laravel route
                    type: "POST",
                    data: formData,
                    contentType: false,
                    processData: false,
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}" // CSRF Token for security
                    },
                    success: function(response) {
                        HoldOn.close();
                        
                    },
                    error: function(xhr) {
                        HoldOn.close();
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            let errorMessages = '';
                            $.each(errors, function(key, value) {
                                errorMessages += value[0] + '<br>';
                            });
                            
                        }   else {
                            
                        }
                    }
                });
            }
        });
    });
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Select all input fields with the class 'phoneNumber'
    var inputs = document.querySelectorAll('.phoneNumberInput');

    inputs.forEach(function (input) {
        input.addEventListener('input', function () {
            // Arabic and English digit mappings
            let arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
            let englishNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

            // Convert Arabic numbers to English
            let value = this.value;
            for (let i = 0; i < 10; i++) {
                let regex = new RegExp(arabicNumbers[i], 'g');
                value = value.replace(regex, englishNumbers[i]);
            }

            // Remove non-digit characters
            value = value.replace(/\D/g, '');

            // Remove leading zeros
            while (value.charAt(0) === '0') {
                value = value.substr(1);
            }

            // Limit to 9 digits
            value = value.slice(0, 9);

            // Update input value
            this.value = value;
        });

         
    });
});

   

</script>
@endpush