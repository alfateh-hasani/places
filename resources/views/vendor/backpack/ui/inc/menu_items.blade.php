{{-- This file is used for menu items by any Backpack v6 theme --}}
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i> {{ trans('backpack::base.dashboard') }}</a></li>
@includeWhen(class_exists(\Backpack\DevTools\DevToolsServiceProvider::class), 'backpack.devtools::buttons.sidebar_item')


<x-backpack::menu-item title="Locks" icon="la la-question" :link="backpack_url('lock')" />
<x-backpack::menu-item title="Apartments" icon="la la-question" :link="backpack_url('apartment')" />
<x-backpack::menu-item title="Features" icon="la la-question" :link="backpack_url('feature')" />
<x-backpack::menu-item title="Policies" icon="la la-question" :link="backpack_url('policy')" />