@php
    $hasMapping = $entry->ownerrezMapping()->exists();
@endphp

@if ($crud->hasAccess('update'))
    @if($hasMapping)
        <a href="{{ url(config('backpack.base.route_prefix').'/ownerrez-property-mapping/'.$entry->ownerrezMapping->id.'/edit') }}" 
           class="btn btn-sm btn-success" 
           data-toggle="tooltip" 
           title="تعديل ربط OwnerRez">
            <i class="la la-link"></i> مربوط مع OwnerRez
        </a>
    @else
        <a href="{{ url(config('backpack.base.route_prefix').'/ownerrez-property-mapping/create?apartment_id='.$entry->id) }}" 
           class="btn btn-sm btn-outline-primary" 
           data-toggle="tooltip" 
           title="ربط هذه الشقة مع OwnerRez">
            <i class="la la-plus"></i> ربط مع OwnerRez
        </a>
    @endif
@endif

