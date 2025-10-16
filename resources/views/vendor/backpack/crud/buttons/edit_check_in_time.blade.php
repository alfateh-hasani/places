@if ($crud->hasAccess('update', $entry))
    <a href="{{ url(config('backpack.base.route_prefix')) }}/booking/{{ $entry->getKey() }}/edit-check-in-time" 
       class="btn btn-xs btn-info" 
       title="تعديل وقت الدخول">
        <i class="la la-clock"></i> وقت الدخول
    </a>
@endif
