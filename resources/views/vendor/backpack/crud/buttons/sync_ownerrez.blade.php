@if ($crud->hasAccess('update'))
    <a href="{{ url($crud->route.'/'.$entry->getKey().'/sync') }}" 
       class="btn btn-sm btn-link" 
       data-toggle="tooltip" 
       title="مزامنة الآن مع OwnerRez"
       onclick="return confirm('هل تريد مزامنة هذا العقار الآن؟')">
        <i class="la la-sync"></i> مزامنة
    </a>
@endif

