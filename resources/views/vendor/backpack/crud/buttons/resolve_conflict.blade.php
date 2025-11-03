@if ($crud->hasAccess('list') && !$entry->conflicting_booking_id)
    <a href="{{ url($crud->route.'/'.$entry->getKey().'/resolve-conflict') }}" 
       class="btn btn-sm btn-success" 
       data-button-type="resolve-conflict"
       onclick="return confirm('هل أنت متأكد من حل هذا التعارض؟')">
        <i class="la la-check"></i> حل التعارض
    </a>
@endif
