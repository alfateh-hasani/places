@php
    $isActive = (bool) data_get($entry, $column['name']);
@endphp

<div class="form-check form-switch d-inline-block m-0">
    <input
        type="checkbox"
        role="switch"
        class="form-check-input js-building-active-switch"
        style="cursor: pointer;"
        data-url="{{ route('admin.building.toggle-active', $entry->getKey()) }}"
        aria-label="{{ __('cms.is_active') }}"
        @checked($isActive)
    >
</div>
