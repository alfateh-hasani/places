@if (backpack_user()->can('direct-booking.create'))
    <a href="{{ backpack_url('direct-booking') }}" class="btn btn-success">
        <i class="la la-plus-circle"></i> {{ __('cms.direct_booking') }}
    </a>
@endif
