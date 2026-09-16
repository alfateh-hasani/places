{{-- List line button: contextual cancellation/refund action for the row.
     Hidden for Airbnb-imported bookings — only the "view" action applies to them. --}}
@unless ($entry->is_airbnb_booking)
    @include('admin.booking.partials.manage_cancellation_button', ['booking' => $entry])
@endunless
