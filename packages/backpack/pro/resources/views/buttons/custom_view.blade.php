@if ($crud->hasAccess('customView') && $crud->hasOperationSetting('customViews'))
    <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        {{ ucfirst(trans('backpack::crud.custom_views.title_short')) }}
    </button>
    <div class="dropdown-menu">
        @php
            $href = url($crud->route);
            $active = request()->url() === $href;
        @endphp
        <h6 class="dropdown-header">{{ trans('backpack::crud.custom_views.default') }}</h6>
        <a class="dropdown-item d-block {{ request()->url() === $href ? 'disabled active' : '' }}"
            href="{{ $href }}">
            {{ ucfirst($crud->entity_name_plural) }}
        </a>

        <div class="dropdown-divider"></div>

        <h6 class="dropdown-header">{{ trans('backpack::crud.custom_views.title') }}</h6>
        @foreach ($crud->getOperationSetting('customViews') as $view)
            @php
                $href = url("$crud->route/view/$view->route");
                $active = request()->url() === $href;
            @endphp
            <a class="dropdown-item {{ $active ? 'disabled active' : '' }}" href="{{ $href }}">
                {{ $view->title }}
            </a>
        @endforeach
    </div>
@endif
