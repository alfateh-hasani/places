@if($entry->canBeTransferred())
    <a href="{{ url('admin/booking/'.$entry->id.'/transfer-unit') }}"
       class="btn btn-sm btn-link"
       data-toggle="tooltip"
       title="{{ __('cms.transfer_unit_hint') }}">
        <i class="la la-exchange-alt"></i> {{ __('cms.transfer_unit') }}
    </a>
@endif
