<link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/dark.css">

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<style>
 
</style>
<script>
$(document).ready(function() {
    const apartmentId = {{ $apartment_id }};
    let isCalculating = false;

    /**
     * حساب السعر من الـ Backend باستخدام PricingService
     */
    function calculateNightsAndCost() {
        const checkinVal = $('#checkin').val();
        const checkoutVal = $('#checkout').val();

        if (!checkinVal || !checkoutVal) {
            $('#totalNights').text('0');
            $('#totalCost').text('0.00 {{ __("apartment.price") }}');
            return;
        }

        const checkin = new Date(checkinVal);
        const checkout = new Date(checkoutVal);
        const nights = (checkout - checkin) / (1000 * 60 * 60 * 24);

        if (nights <= 0) {
            $('#totalNights').text('0');
            $('#totalCost').text('0.00 {{ __("apartment.price") }}');
            return;
        }

        // منع الطلبات المتعددة
        if (isCalculating) return;
        isCalculating = true;

        // عرض مؤشر التحميل
        $('#totalCost').html('<i class="la la-spinner la-spin"></i>');

        // استدعاء API لحساب السعر الفعلي
        $.ajax({
            url: "{{ route('apartments.calculate-price', ['apartmentId' => $apartment_id]) }}",
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                check_in: checkinVal,
                check_out: checkoutVal
            },
            success: function(response) {
                if (response.success) {
                    // تحديث جميع عناصر السعر
                    $('#totalNights').text(response.nights + ' ' + "{{ __('apartment.nights') }}");
                    $('#totalCost').text(response.total.toFixed(2) + ' ' + "{{ __('apartment.price') }}");
                    $('#mainPrice').text(response.total.toFixed(2));
                    $('#nightlyPrice').text(response.one_night_price.toFixed(2) + ' {{ __("apartment.price") }}');
                    
                    // عرض معلومات الخصم إذا وجد
                    if (response.discount > 0) {
                        $('#discountedCost').html(
                            '<span class="text-green-600"><i class="la la-tag"></i> ' +
                            "{{ __('apartment.long_stay_discount') }}: " + 
                            response.discount.toFixed(2) + ' {{ __("apartment.price") }}</span>'
                        );
                    } else {
                        $('#discountedCost').text('');
                    }
                }
            },
            error: function(xhr) {
                console.error('Error calculating price:', xhr);
                $('#totalCost').text('--');
                alert("{{ __('apartment.price_calculation_error') }}");
            },
            complete: function() {
                isCalculating = false;
            }
        });
    }

    /**
     * تحديث السعر الأولي عند تحميل الصفحة
     */
    function updateInitialPrice() {
        const checkinVal = $('#checkin').val();
        const checkoutVal = $('#checkout').val();
        
        if (checkinVal && checkoutVal) {
            calculateNightsAndCost();
        }
    }

    // تأكد من عدم السماح بتاريخ مغادرة أقل من تاريخ الوصول
    $.validator.addMethod("greaterThan", function(value, element, params) {
        var checkinDate = new Date($(params).val());
        var checkoutDate = new Date(value);
        return checkoutDate > checkinDate;
    }, "{{ __('apartment.checkout_greater_than') }}");

    $("#booking").validate({
        rules: {
            checkin: "required",
            checkout: {
                required: true,
                greaterThan: "#checkin"
            },
            adults_count: {
                required: true,
                min: 1,
                max: "{{$apartment->adults_count}}"
            },
            children_count: {
                required: true,
                min: 0,
                max: {{$apartment->children_count}}
            }
        },
        messages: {
            checkin: "{{ __('apartment.checkin_required') }}",
            checkout: {
                required: "{{ __('apartment.checkout_required') }}",
                greaterThan: "{{ __('apartment.checkout_greater_than') }}"
            },
            adults_count: {
                required: "{{ __('apartment.adults_count_required') }}",
                min: "{{ __('apartment.adults_count_min') }}",
                max: "{{ __('apartment.adults_count_max') }}"
            },
            children_count: {
                required: "{{ __('apartment.children_count_required') }}",
                min: "{{ __('apartment.children_count_min') }}",
                max: "{{ __('apartment.children_count_max') }}"
            }
        },
        submitHandler: function(form) {
            HoldOn.open({
                theme: "sk-cube-grid",
                message: "{{ __('apartment.loading_message') }}"
            });

            var apartment_id = $('#apartment_id').val();
            var bookingUrlTemplate = "{{ route('web-booking.determine', ['apartment_id' => $apartment_id ]) }}";
            var bookingUrl = bookingUrlTemplate.replace('APARTMENT_ID_PLACEHOLDER', apartment_id);

            $.ajax({
                url: bookingUrl,
                method: "POST",
                data: $(form).serialize(), 
                success: function(response) {
                    HoldOn.close();
                    Swal.fire({
                        icon: 'success',
                        title: "{{ __('apartment.success') }}",
                        text: "{{ __('apartment.booking_success_message') }}",
                        button: true,
                    });
                    location.reload();
                },
                error: function(xhr) {
                    HoldOn.close();
                    Swal.fire({
                        icon: 'error',
                        title: "{{ __('apartment.error') }}",
                        text: "{{ __('apartment.booking_failed_message') }}",
                        button: true,
                    });
                }
            });
        }
    });

    const bookedDays = @json($booked_days);

    function buildDisabledDates(bookings) {
        const dates = [];
        bookings.forEach(booking => {
            const checkIn = new Date(booking.check_in);
            const checkOut = new Date(booking.check_out);
            const current = new Date(checkIn);
            while (current < checkOut) {
                dates.push(current.toISOString().split('T')[0]);
                current.setDate(current.getDate() + 1);
            }
        });
        return dates;
    }

    const disabledDates = buildDisabledDates(bookedDays);

    const commonOptions = {
        dateFormat: "Y-m-d",
        minDate: "today",
        allowInput: false,
        disable: disabledDates,
        locale: "ar",
        time_24hr: true,
        weekNumbers: false,
        static: true,
        enableTime: false,
        noCalendar: false,
        inline: false,
    };

    var checkinPicker = flatpickr("#checkin", {
        ...commonOptions,
        onChange: function(selectedDates, dateStr, instance) {
            if (selectedDates.length > 0) {
                const checkinDate = selectedDates[0];
                const minCheckoutDate = new Date(checkinDate);
                minCheckoutDate.setDate(minCheckoutDate.getDate() + 1);

                checkoutPicker.set('minDate', minCheckoutDate);

                if (!checkoutPicker.selectedDates.length || checkoutPicker.selectedDates[0] <= checkinDate) {
                    checkoutPicker.setDate(minCheckoutDate, true);
                }

                calculateNightsAndCost();
            }
        }
    });

    var checkoutPicker = flatpickr("#checkout", {
        ...commonOptions,
        onChange: function(selectedDates, dateStr, instance) {
            calculateNightsAndCost();
        }
    });

    // تحديث التقويم بالتواريخ الجديدة من الكاش
    function refreshCalendarBlockedDates() {
        $.getJSON("{{ route('apartments.blocked-dates', $apartment_id) }}", function(response) {
            if (response.booked_days) {
                const newDisabled = buildDisabledDates(response.booked_days);
                checkinPicker.set('disable', newDisabled);
                checkoutPicker.set('disable', newDisabled);
            }
        });
    }

    // جلب أحدث بيانات فور فتح الصفحة
    refreshCalendarBlockedDates();

    // تحديث تلقائي كل 5 دقائق (يتزامن مع تحديث الكاش)
    setInterval(refreshCalendarBlockedDates, 5 * 60 * 1000);

    // منع إدخال تواريخ غير صحيحة يدويًا
    $('#checkout').on('change', function() {
        var checkinVal = $('#checkin').val();
        var checkoutVal = $(this).val();
        if (new Date(checkoutVal) <= new Date(checkinVal)) {
            alert("{{ __('apartment.checkout_greater_than') }}");
            $(this).val('');
        }
    });

    // تحديث السعر الأولي عند تحميل الصفحة
    $(document).ready(function() {
        // انتظار قليل للتأكد من تحميل التقويم
        setTimeout(function() {
            updateInitialPrice();
        }, 500);
    });

});
</script>
<script>
    $(document).ready(function() {
        $(".counter-button").click(function() {
            var targetId = $(this).attr("data-target");
            var input = $("#" + targetId);
            var value = parseInt(input.val()) || 0;
            var min = parseInt(input.attr("min")) || 0;
            var max = parseInt(input.attr("max")) || 10;
            if ($(this).hasClass("increment") && value < max) {
                input.val(value + 1);
            } else if ($(this).hasClass("decrement") && value > min) {
                input.val(value - 1);
            }
        });
    });
    </script>