{{-- This file is used for menu items by any Backpack v6 theme --}}
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i> {{ trans('backpack::base.dashboard') }}</a></li>
@includeWhen(class_exists(\Backpack\DevTools\DevToolsServiceProvider::class), 'backpack.devtools::buttons.sidebar_item')


<x-backpack::menu-item title="Locks" icon="la la-question" :link="backpack_url('lock')" />
<x-backpack::menu-item title="{{__('cms.buildings')}}" icon="la la-question" :link="backpack_url('buildings')" />
<x-backpack::menu-item title="{{__('cms.features')}}" icon="la la-question" :link="backpack_url('feature')" />
<x-backpack::menu-item title="{{__('cms.apartments')}}" icon="la la-question" :link="backpack_url('apartment')" />

<x-backpack::menu-item title="{{__('cms.policies')}}" icon="la la-question" :link="backpack_url('policy')" />
<x-backpack::menu-item title="{{__('cms.cities')}}" icon="la la-home" :link="backpack_url('city')" />


