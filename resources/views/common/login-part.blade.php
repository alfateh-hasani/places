

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
            <form id="login-form" method="post" >
                @csrf <!-- Include CSRF token -->

                <div id="login-result"></div>
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
                        60 : @lang('site.resend')
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




// Phone Validation Method: Converts Arabic numbers to English and ensures 9 digits.
$.validator.addMethod("validPhone", function(value, element) {
    let arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    let englishNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    // Replace Arabic numbers with English
    for (let i = 0; i < 10; i++) {
        let regex = new RegExp(arabicNumbers[i], 'g');
        value = value.replace(regex, englishNumbers[i]);
    }

    // Remove non-digit characters and leading zeros
    value = value.replace(/\D/g, '').replace(/^0+/, '');

    // Ensure the value is exactly 9 digits
    return this.optional(element) || value.length === 9;
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
                showMessage('#registration-result', 'success', 'Account created and logged in successfully!');
                window.location.href = response.redirect;
            },
            error: function(xhr) {
                handleAjaxError(xhr,   '#registration-result');

            }
        });
    }
});

// Switch to OTP Popup
function switchToOtpPopup() {
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
    startCountdown();
}

// OTP Countdown
let resendCount = 5;
let otpTimeout = 60;
let otpInterval;

function startCountdown() {
    let timeLeft = otpTimeout;
    otpInterval = setInterval(() => {
        timeLeft--;
        $('#resend-timer').text(`${timeLeft} : @lang('site.resend')`);

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
    rules: { phone: { required: true, validPhone: true } },
    messages: {
        phone: {
            required: "@lang('site.phone_required')",
            validPhone: "@lang('site.invalid_phone')"
        }
    },
    errorPlacement: function(error, element) {
        error.insertAfter(element.closest('.select-box'));
    },
    submitHandler: function(form) {
        HoldOn.open({ theme: "sk-rect" });

        $.ajax({
            url: "{{ route('login.step1') }}",
            type: "POST",
            data: new FormData(form),
            contentType: false,
            processData: false,
            headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
            success: function(response) {
                HoldOn.close();
                $('#phone-number').text(response.phone);
                switchToOtpPopup();
                if (!response.has_account) $('#otp-form').data('registerRequired', true);
            },
            error: function(xhr) {
                handleAjaxError(xhr,   '#login-result');
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
                    showMessage('#otp-result', 'success', 'Logged in successfully!');
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
    if (resendCount > 0) {
        resendCount--;
        startCountdown();
        $(this).prop('disabled', true);

        $.ajax({
            url: "{{ route('login.resend_otp') }}",
            type: "POST",
            data: { phone: $('#phone-number').text() },
            success: function() {
                showMessage('#otp-result', 'success', '@lang("site.otp_sent")');
            },
            error: function() {
                showMessage('#otp-result', 'danger', '@lang("site.resend_failed")');
            }
        });
    } else {
        showMessage('#otp-result', 'warning', '@lang("site.resend_limit_reached_message")');
    }
});

// Phone Number Input Handling
$('.phoneNumberInput').on('input', function() {
    let value = this.value.replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/\D/g, '').replace(/^0+/, '').slice(0, 9);
    this.value = value;
});

});
</script>
@endpush