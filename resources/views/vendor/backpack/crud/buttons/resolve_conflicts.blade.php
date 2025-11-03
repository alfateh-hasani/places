@if ($crud->hasAccess('list'))
    <a href="{{ url($crud->route.'/resolve-all-conflicts') }}" 
       class="btn btn-warning" 
       data-button-type="resolve-all-conflicts"
       onclick="return confirm('هل أنت متأكد من حل جميع التعارضات؟')">
        <i class="la la-check-circle"></i> حل جميع التعارضات
    </a>
@endif
