

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
                @csrf
                <div id="login-result"></div>
                <label>
                    <p class="text-sm mb-3">{{ __('site.your_mobile_number') }}</p>
                    
                </label>
                <div class="w-full">
                        <input autocomplete="off" type="tel" id="phoneNumber" name="phone" class="w-full border border-border rounded-lg h-12 px-3">
                          <div id="countriesDropdown" class="w-full"></div>
                    </div>
              
                
                <div class="md:grid md:grid-cols-1 md:gap-5 max-w-full mt-4">
                    <button type="submit" id="login-submit-button" class="py-4 rounded-lg bg-price text-white w-full">
                        @lang('site.login')
                    </button>
                </div>
            </form>
            <p class="text-sm mt-4 mb-10 text-center">
                 
            </p>
        </div>
    </div>
</div>


<div class="popup modal" id="popup-6">
  <div class="popup-contain text-center">
      <p class="p-5 border-b border-border text-left rtl:text-right">
          @lang('site.confirm_your_number')
      </p>
      <div class="px-5 text-left rtl:text-right pt-8">
          <p class="font-semibold text-xl mb-6">
              @lang('site.welcome_back')
              <img class="h-8 inline-block" src="{{ asset('assets/img/goodbye.png') }}" />
          </p>
          <p class="text-sm mb-4">
              @lang('site.enter_code_sms') <span dir="ltr" id="phone-number"></span>:
          </p>

          <div id="otp-result"></div>
           
          <form id="otp-form">
              <div class="flex mb-2 space-x-2    justify-center items-center" style="    direction: ltr;" dir="ltr">
                  @for ($i = 1; $i <= 4; $i++)
                  <div>
                      <label for="code-{{ $i }}" class="sr-only">Code {{ $i }}</label>
                      <input 
                          type="text" 
                          maxlength="1" 
                          id="code-{{ $i }}" 
                           
                          class="otp-input block w-12 h-12 text-center text-gray-900 bg-white border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 rtl:border-gray-500" 
                          data-focus-input-init 
                          data-focus-input-next="code-{{ $i+1 }}" 
                          data-focus-input-prev="code-{{ $i-1 }}" 
                          required 
                      />
                  </div>
                  @endfor
              </div>
              <div class="border-t border-border py-8 -mx-5 px-5 mt-4">
                <div class="flex justify-between items-center">
                    <p id="resend-timer" class="text-sm text-reviews">
                        2:00 : @lang('site.resend')
                    </p>
                    <div class="flex space-x-4 rtl:space-x-reverse">
                        <button type="button" id="resend-button" 
                                class="py-2 px-6 rounded-lg bg-price text-white" 
                                disabled>
                            @lang('site.resend')
                        </button>
                        <button type="submit" id="otp-submit-button" 
                                class="py-2 px-6 rounded-lg bg-price text-white" 
                                disabled>
                            @lang('site.login')
                        </button>
                    </div>
                </div>
            </div>
            
          </form>
      </div>
  </div>
</div>

<div class="popup modal" id="popup-7">
  <div class="popup-contain text-center">
      <p class="p-5 border-b border-border text-left rtl:text-right">
          @lang('site.sign_up')
      </p>
      <div class="px-5 text-left rtl:text-right pt-8">
          <p class="font-semibold text-xl mb-6 rtl:mb-4">
              @lang('site.welcome_to_dyafa') 
              <img class="h-8 inline-block rtl:ml-2" src="{{ asset('assets/img/goodbye.png') }}" />
          </p>

          <form>
                <div id="registration-result"></div>
              <input type="hidden" id="registration-token" name="token" />

              <div class="md:grid md:grid-cols-2 md:gap-5 max-w-full my-10 rtl:space-x-reverse">
                  <label class="block">
                      <p class="mb-1 rtl:text-right">@lang('site.first_name')</p>
                      <input class="w-full border border-border rounded-lg h-12 px-3 rtl:pl-0 rtl:pr-3" 
                             type="text" name="first_name" required />
                  </label>
                  <label class="block">
                      <p class="mb-1 rtl:text-right">@lang('site.last_name')</p>
                      <input class="w-full border border-border rounded-lg h-12 px-3 rtl:pl-0 rtl:pr-3" 
                             type="text" name="last_name" required />
                  </label>
                  <label class="col-span-2 block">
                      <p class="mb-1 rtl:text-right">@lang('site.email')</p>
                      <input class="w-full border border-border rounded-lg h-12 px-3 rtl:pl-0 rtl:pr-3" 
                             type="email" name="email" required />
                  </label>
                  <button type="submit" class="col-span-2 py-4 rounded-lg bg-price text-white mt-4">
                      @lang('site.create_new_account')
                  </button>
              </div>
          </form>
      </div>
  </div>
</div>


@push('js')
<link rel="stylesheet" href="{{ asset('telinput/css/intlTelInput.css')}}">
<script src="{{ asset('telinput/js/intlTelInputWithUtils.min.js')}}"></script>
<link rel="stylesheet" href="{{ asset('assets/css/HoldOn.min.css')}}" />
<script src="{{ asset('assets/js/jquery.validate.js')}}"></script>
 
<script src="{{ asset('assets/js/HoldOn.min.js')}}"></script>
<style>
    .alert.alert-danger {
    color: red;
    font-size: 14px;
    margin-bottom: 10px;
}
input.otp-input {
    direction: ltr;
}
.error-message.text-danger {
    color: red;
    font-size: 14px;
    margin-top: 3px;
    margin-bottom: 5px;
}
.popup-contain {
    max-width: 100%;
}
.iti {
    width: 100%;
}
 
@media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
    .iti__flag {
        background-image: url("{{ asset('telinput/img/flags@2x.png')}}");
    }
}
.iti__country-name {
    display: none !important;
}
.iti__selected-flag {
    padding-left: 6px !important;
}

ul.iti__country-list .iti__country {
    direction: ltr !important;
}
.iti__selected-flag {
    direction: ltr !important;
}   
.iti__selected-dial-code {
    direction: ltr !important;
}
div#popup-5 .fancybox-content {
 
    overflow: visible;
   
}

#countriesDropdown {
        
    z-index: 99999;
    position: relative;
    overflow: visible;
    width: 100%;
    direction: ltr;
}
div#popup-5 {
    overflow: visible !important;
}
.iti__country-list {
    
    left: 0 !important;
}

.iti.iti--container {
    top: 0 !important;
    left: 0 !important;
    position: static !important;
}
input#phoneNumber {
    padding-right: 10px !important;
    padding-left: 86px !important;
    direction: ltr;
}

button.iti__selected-country {
    background: #e9e9e9;
    
}

span.iti__dial-code {
    direction: ltr !important;
    margin-left: 4px;
}
button#login-submit-button:disabled {
    opacity: 0.6;
}
</style>
<script>

$( document ).ready(function() {

// Display Inline Messages for General Errors
function showGeneralErrorMessage(container, message) {
    const html = `<div class="alert alert-danger">${message}</div>`;
    $(container).html(html).fadeIn().delay(5000).fadeOut();
}

// AJAX Error Handling with Fallback
function handleAjaxError(xhr,   container) {
    HoldOn.close();  // Close loading animation
    clearInputErrors();  // Clear previous input errors

    if (xhr.status === 422) {
        // Input validation errors
        displayInputErrors(xhr.responseJSON.errors);
    } else {
        // General error message for other statuses
        let message = xhr.responseJSON?.message || '@lang("site.something_went_wrong")';
        showGeneralErrorMessage(container, message);
    }
}

// إعداد حقل الهاتف
const phoneInput = document.querySelector("#phoneNumber");
const iti = window.intlTelInput(phoneInput, {
      //  utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js",
    initialCountry: "auto",
    separateDialCode: true,
    formatOnDisplay: true,
    nationalMode: false,
    autoFormat: true,
    geoIpLookup: function(callback) {
        fetch("https://ipapi.co/json")
        .then(res => res.json())
        .then(data => callback(data.country_code))
        .catch(() => callback("sa")); // السعودية كدولة افتراضية في حالة الفشل
    },
    preferredCountries: ["sa", "ae", "kw", "bh", "om", "qa"],
    dropdownContainer: document.getElementById('countriesDropdown'),
});

// تحديث التحقق من صحة رقم الهاتف
$.validator.addMethod("validPhone", function(value, element) {
    return iti.isValidNumber();
}, "@lang('site.invalid_phone')");

// Open Registration Popup
function openRegistrationPopup(token) {
    $.fancybox.open({
        src: '#popup-7',
        type: 'inline',
        touch: false,
        clickSlide: false,
        clickOutside: false,
        afterShow: function() {
            $('#registration-token').val(token);
        }
    });
}

// Display Inline Messages
function showMessage(container, type, message) {
    const html = `<div class="alert alert-${type}">${message}</div>`;
    $(container).html(html).fadeIn().delay(3000).fadeOut();
}

// Clear Input Errors
function clearInputErrors() {
    $('.modal').find('.error-message').remove();
}

// Display Input Errors
function displayInputErrors(errors) {
    $.each(errors, function(field, message) {
        $(`input[name="${field}"]`).after(`<div class="error-message text-danger">${message}</div>`);
    });
}

// Registration Form Submission
$('#popup-7 form').validate({
    submitHandler: function(form) {
        HoldOn.open({ theme: "sk-rect" });
        let formData = new FormData(form);

        $.ajax({
            url: "{{ route('login.register') }}",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
            success: function(response) {
                HoldOn.close();
                showMessage('#registration-result', 'success', '{{ __('site.created_in_successfully')}}');
                window.location.href = response.redirect;
            },
            error: function(xhr) {
                handleAjaxError(xhr,   '#registration-result');

            }
        });
    }
});

// Switch to OTP Popup
function switchToOtpPopup(seconds) {
    $.fancybox.close('#popup-5');
    $.fancybox.open({
        src: '#popup-6',
        type: 'inline',
        touch: false,
        clickSlide: false,
        clickOutside: false,
        afterShow: function() {
            $('#code-1').focus();
        }
    });
    startCountdown(seconds);
}

// OTP Countdown
let resendCount = 5;
let otpTimeout = 120;
let otpInterval;

// Format remaining seconds as a m:ss clock (e.g. 120 -> "2:00", 59 -> "0:59").
function formatCountdown(totalSeconds) {
    let minutes = Math.floor(totalSeconds / 60);
    let seconds = totalSeconds % 60;
    return `${minutes}:${seconds.toString().padStart(2, '0')}`;
}

function startCountdown(seconds) {
    clearInterval(otpInterval);

    let timeLeft = parseInt(seconds, 10);
    if (isNaN(timeLeft) || timeLeft <= 0) {
        timeLeft = otpTimeout;
    }

    $('#resend-button').prop('disabled', true);
    $('#resend-timer').text(`${formatCountdown(timeLeft)} : @lang('site.resend')`);

    otpInterval = setInterval(() => {
        timeLeft--;
        $('#resend-timer').text(`${formatCountdown(timeLeft)} : @lang('site.resend')`);

        if (timeLeft <= 0) {
            clearInterval(otpInterval);
            $('#resend-button').prop('disabled', resendCount <= 0);
            $('#resend-timer').text(
                resendCount > 0
                    ? '@lang("site.resend_available")'
                    : '@lang("site.resend_limit_reached")'
            );
        }
    }, 1000);
}

// Login Form Validation and Submission
$('#login-form').validate({
    rules: { 
        phone: { 
            required: true, 
             
        } 
    },
    messages: {
        phone: {
            required: "@lang('site.phone_required')",
            validPhone: "@lang('site.invalid_phone')"
        }
    },
    submitHandler: function(form) {
        const phoneNumber = iti.getNumber();
        const formData = new FormData(form);
        formData.set('phone', phoneNumber);

        HoldOn.open({ theme: "sk-rect" });

        $.ajax({
            url: "{{ route('login.step1') }}",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
            success: function(response) {
                HoldOn.close();
                $('#phone-number').text(response.phone);
                switchToOtpPopup(response.retry_after);
                if (!response.has_account) $('#otp-form').data('registerRequired', true);
            },
            error: function(xhr) {
                HoldOn.close();
                if (xhr.status === 429 && xhr.responseJSON) {
                    // A code was already sent recently: move to the OTP step and
                    // resume the server-driven cooldown instead of resending.
                    $('#phone-number').text(xhr.responseJSON.phone);
                    switchToOtpPopup(xhr.responseJSON.retry_after);
                    if (!xhr.responseJSON.has_account) $('#otp-form').data('registerRequired', true);
                    showMessage('#otp-result', 'warning', xhr.responseJSON.message);
                    return;
                }
                handleAjaxError(xhr, '#login-result');
            }
        });
    }
});

// OTP Form Submission
$('#otp-form').on('submit', function (e) {
        e.preventDefault();
        HoldOn.open({ theme: "sk-rect" });

        let otpCode = $('.otp-input').map((_, el) => $(el).val()).get().join('');

        $.ajax({
            url: "{{ route('login.step2') }}",
            type: "POST",
            data: {
                otp: otpCode,
                phone: $('#phone-number').text(),
                _token: "{{ csrf_token() }}"
            },
            success: function (response) {
                HoldOn.close();

                if (response.register_required) {
                    openRegistrationPopup(response.token);
                } else {
                    showMessage('#otp-result', 'success', '{{ __('site.logged_in_successfully')}}');
                    window.location.href = response.redirect || '/';
                }
            },
            error: function (xhr) {
                handleAjaxError(xhr,   '#otp-result');

            }
        });
    });


 
// OTP Input Handling
$('.otp-input').on('input', function (e) {
    let $this = $(this);
    let nextInput = $this.data('focus-input-next');
    let prevInput = $this.data('focus-input-prev');

    // Replace Arabic numbers with English and remove non-digit characters
    let value = $this.val().replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/\D/g, '');
    $this.val(value);  // Set cleaned value

    // Move to the next input if a digit is entered
    if (value.length === 1 && nextInput) {
        $(`#${nextInput}`).focus();
    }

    // Enable or disable the submit button based on all inputs being filled
    let allFilled = $('.otp-input').toArray().every(input => $(input).val().length === 1);
    $('#otp-submit-button').prop('disabled', !allFilled);
});

// Backspace/Delete Navigation Between OTP Inputs
$('.otp-input').on('keydown', function (e) {
    let $this = $(this);
    let prevInput = $this.data('focus-input-prev');

    // Move focus to the previous input on Backspace or Delete if the current input is empty
    if ((e.key === 'Backspace' || e.key === 'Delete') && !$this.val() && prevInput) {
        $(`#${prevInput}`).focus().val('');  // Clear and move to the previous input
    }
});

// OTP Paste Handling
$('.otp-input').on('paste', function (e) {
    let data = e.originalEvent.clipboardData.getData('text').split('');
    $('.otp-input').each(function (index, input) {
        $(input).val(data[index] || '').trigger('input');  // Set each input value from the pasted content
    });
});


// OTP Resend Button Handler
$('#resend-button').on('click', function() {
    if (resendCount <= 0) {
        showMessage('#otp-result', 'warning', '@lang("site.resend_limit_reached_message")');
        return;
    }

    $(this).prop('disabled', true);

    $.ajax({
        url: "{{ route('login.resend_otp') }}",
        type: "POST",
        data: { phone: $('#phone-number').text(), _token: "{{ csrf_token() }}" },
        success: function(response) {
            resendCount--; // only a successful send counts against the attempt limit
            startCountdown(response.retry_after);
            showMessage('#otp-result', 'success', response.message || '@lang("site.otp_sent")');
        },
        error: function(xhr) {
            if (xhr.status === 429 && xhr.responseJSON) {
                // Still within the server cooldown window: honor its timer (no attempt consumed).
                startCountdown(xhr.responseJSON.retry_after);
                showMessage('#otp-result', 'warning', xhr.responseJSON.message);
                return;
            }
            // Genuine failure: keep the button usable so the user can retry, don't start a
            // timer, and surface the real reason from the server when available.
            $('#resend-button').prop('disabled', false);
            let message = (xhr.responseJSON && xhr.responseJSON.message) || '@lang("site.resend_failed")';
            showMessage('#otp-result', 'danger', message);
        }
    });
});


 
var handleChange = function() {
    let number = phoneInput.value.trim();
    console.log(number);
    console.log(iti.getNumber());
    if (number) {
        if (iti.isValidNumber()) {
            console.log('Valid Number:', iti.getNumber());
            $('#login-submit-button').prop('disabled', false);
        } else {
            console.log('Invalid Number');
            $('#login-submit-button').prop('disabled', true);
        }
    }
};

 
$('#phoneNumber').on('countrychange', function() {
    handleChange();
});
$('#phoneNumber').on('keyup', function() {
    handleChange();
});

});


</script>
@endpush