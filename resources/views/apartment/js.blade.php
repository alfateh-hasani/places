<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    function toggleFavorite(apartmentId) {
        $.ajax({
            url: '{{ route('customer.toggle.favorite') }}',
            type: 'POST',
            data: {
                apartment_id: apartmentId,
                _token: '{{ csrf_token() }}'
            },
            success: function(data) {
                if (data.success) {
                    let icon = $('#favorite-icon-' + apartmentId);
                    if (data.action === 'added') {
                        console.log(data.action);
                        
                        icon.attr('src', '{{ asset("assets/img/favorite-active.svg") }}');  
                        Swal.fire({
                            icon: 'success',
                            title: '{{ __("apartment.favorite_added") }}',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        console.log(data.action);
                        icon.attr('src', '{{ asset("assets/img/favoritee.svg") }}'); 
                        Swal.fire({
                            icon: 'info',
                            title: '{{ __("apartment.favorite_removed") }}',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }
                    // window.location.reload();

                } else {
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __("apartment.favorite_failed") }}',
                        text: data.message || '{{ __("apartment.favorite_failed") }}'
                    });
                }
            },
            error: function(xhr, status, error) {
                if (xhr.status === 401) {
                    Swal.fire({
                        icon: 'error',
                        title: '{{__("apartment.favorite_login_title")}}',
                        text: '{{ __("apartment.favorite_login") }}'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: '{{ __("apartment.favorite_failed") }}'
                    });
                    console.error('Error:', error);
                }
            }
        });
    }
    const nightlyRate = {{ $apartment->price }};
    let discount = 0;

    function calculateNightsAndCost() {
        const checkin = new Date($('#checkin').val());
        const checkout = new Date($('#checkout').val());
        const timeDifference = checkout - checkin;
        const nights = timeDifference / (1000 * 60 * 60 * 24);
        if (nights > 0) {
            const totalCost = nights * nightlyRate;
            $('#totalNights').text(nights + ' ' + "{{ __('apartment.nights') }}");
            let finalCost = totalCost;
            if (discount > 0) {
                finalCost = totalCost - (totalCost * (discount / 100));  
            }
            $('#totalCost').text(finalCost.toFixed(2) + ' ' + "{{ __('apartment.price') }}");
            if (discount > 0) {
                $('#discountedCost').text("{{ __('apartment.discounted_price') }}: " + finalCost.toFixed(2) + ' ' + "{{ __('apartment.price') }}");
            } else {
                $('#discountedCost').text('');
            }
        } else {
            $('#totalNights').text(0);
            $('#totalCost').text('0.00');
            $('#discountedCost').text('');
        }
    }

    $(document).ready(function() {
        // Calculate nights and cost on date change
        $('#checkin, #checkout').on('change', calculateNightsAndCost);

        $("#booking").validate({
            rules: {
                checkin: "required",
                checkout: {
                    required: true,
                    greaterThan: "#checkin"
                },
                adults_count:{
                    required: true,
                    min: 1,
                    max: "{{$apartment->adults_count}}"
                },
                children_count:{
                    required: true,
                    min: 0,
                    max: {{$apartment->children_count}}
                }
                
            },
            messages: {
                checkin: "{{__('apartment.checkin_required')}}",
                checkout: {
                    required: "{{__('apartment.checkout_required')}}",
                    greaterThan: "{{__('apartment.checkout_greater_than')}}"
                },
                adults_count:{
                    required: "{{__('apartment.adults_count_required')}}",
                    min: "{{__('apartment.adults_count_min')}}",
                    max: "{{__('apartment.adults_count_max')}}"
                },
                children_count:{
                    required: "{{__('apartment.children_count_required')}}",
                    min: "{{__('apartment.children_count_min')}}",
                    max: "{{__('apartment.children_count_max')}}"
                }
            },
            submitHandler: function(form) {
                HoldOn.open({
                    theme: "sk-cube-grid",  
                    message: "{{__('apartment.loading_message')}}"  
                });
                var apartment_id = $('#apartment_id').val();
                $.ajax({
                   
                    url: "{{ route('web-booking.determine',"+apartment_id+") }}",  
                    method: "POST",
                    data: $(form).serialize(), 
                    success: function(response) {
                        HoldOn.close();
                        Swal.fire({
                            icon: 'success',
                            title: "{{__('apartment.success')}}",
                            text: "{{__('apartment.booking_success_message')}}",
                            button: true,
                        });
                        location.reload();
                    },
                    error: function(xhr) {
                        HoldOn.close();
                        Swal.fire({
                            icon: 'error',
                            title: "{{__('apartment.error')}}",
                            text: "{{__('apartment.booking_failed_message')}}",
                            button: true,
                        });
                    }
                });
            }
        });
    });

    const bookedDays = @json($booked_days);
    console.log(bookedDays);
    console.log(typeof flatpickr);

    function initializeDatePicker(selector) {
        if (typeof flatpickr === "undefined") {
            console.error("flatpickr library is not loaded.");
            return;
        }

        // Prepare disable dates array with ranges
        const disableDates = bookedDays.map(booking => {
            return {
                from: booking.check_in,
                to: new Date(new Date(booking.check_out).setDate(new Date(booking.check_out).getDate() - 1)).toISOString().split('T')[0]
            };
        });

        flatpickr(selector, {
            dateFormat: "Y-m-d",
            minDate: "today",
            allowInput: false,
            disable: disableDates,
            onDayCreate: function(dObj, dStr, fp, dayElem) {
                const dateStr = dayElem.dateObj.toISOString().split('T')[0];
                const isCheckInDate = bookedDays.some(booking => booking.check_in === dateStr);
                const isCheckOutDate = bookedDays.some(booking => booking.check_out === dateStr);

                // Style check-in dates as disabled and check-out dates as available
                if (isCheckInDate) {
                    dayElem.style.backgroundColor = "#0000001a";
                    dayElem.style.color = "#fff";
                    dayElem.classList.add("flatpickr-disabled");
                } else if (isCheckOutDate) {
                    dayElem.style.backgroundColor = "#fff";
                    dayElem.style.color = "#000";
                    dayElem.classList.remove("flatpickr-disabled"); // Make check-out date selectable
                }
            },
            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates.length > 0 && instance.calendarContainer) {
                    const selectedDayElem = instance.calendarContainer.querySelector(`.flatpickr-day[aria-label="${selectedDates[0].toDateString()}"]`);
                    if (selectedDayElem) {
                        selectedDayElem.style.backgroundColor = "#0000001a";
                        selectedDayElem.style.color = "#fff";
                    }
                }
            }
        });
    }

    document.addEventListener("DOMContentLoaded", function() {
        initializeDatePicker("#checkin");
        initializeDatePicker("#checkout");

        const checkinInput = document.getElementById('checkin');
        const checkoutInput = document.getElementById('checkout');

        checkinInput.addEventListener('change', function() {
            const checkinDate = new Date(this.value);
            const minCheckoutDate = new Date(checkinDate);
            minCheckoutDate.setDate(minCheckoutDate.getDate() + 1);

            const year = minCheckoutDate.getFullYear();
            const month = String(minCheckoutDate.getMonth() + 1).padStart(2, '0');
            const day = String(minCheckoutDate.getDate()).padStart(2, '0');
            const formattedDate = `${year}-${month}-${day}`;

            checkoutInput.value = formattedDate;
            checkoutInput.min = formattedDate;
        });
    });


</script> 