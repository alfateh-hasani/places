{{-- This file is used for menu items by any Backpack v6 theme --}}
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i> {{ trans('backpack::base.dashboard') }}</a></li>
@includeWhen(class_exists(\Backpack\DevTools\DevToolsServiceProvider::class), 'backpack.devtools::buttons.sidebar_item')

<x-backpack::menu-item title="{{__('cms.smart_locks')}}" icon="la la-lock" :link="backpack_url('smart-lock')" />
<x-backpack::menu-item title="{{__('cms.buildings')}}" icon="la la-building" :link="backpack_url('buildings')" />
<x-backpack::menu-item title="{{__('cms.features')}}" icon="la la-list" :link="backpack_url('feature')" />
<x-backpack::menu-item title="{{__('cms.apartments')}}" icon="la la-home" :link="backpack_url('apartment')" />
<x-backpack::menu-item title="{{__('cms.apartment_label')}}" icon="la la-tag" :link="backpack_url('apartment-label')" />

<x-backpack::menu-item title="{{__('cms.policies')}}" icon="la la-file-alt" :link="backpack_url('policy')" />
<x-backpack::menu-item title="{{__('cms.cities')}}" icon="la la-map-marker" :link="backpack_url('city')" />
<x-backpack::menu-item title="{{__('cms.notification')}}" icon="la la-bell" :link="backpack_url('notifications')" />

<x-backpack::menu-dropdown title="{{__('cms.contents')}}" icon="la la-file">
    <x-backpack::menu-item title="{{__('cms.sliders')}}" icon="la la-image" :link="backpack_url('sliders')" />
    <x-backpack::menu-item title="{{__('cms.sliders_app')}}" icon="la la-mobile" :link="backpack_url('sliders-app')" />
    <x-backpack::menu-item title="{{__('cms.advantages')}}" icon="la la-thumbs-up" :link="backpack_url('advantages')" />
    <x-backpack::menu-item title="{{__('cms.pages')}}" icon="la la-file-text" :link="backpack_url('pages')" />
    <x-backpack::menu-item title="{{__('cms.blogs')}}" icon="la la-pen" :link="backpack_url('blogs')" />
</x-backpack::menu-dropdown>

<x-backpack::menu-item title="{{__('cms.booking')}}" icon="la la-calendar-check" :link="backpack_url('booking')" />

<x-backpack::menu-dropdown title="{{__('cms.finance')}}" icon="la la-dollar-sign">
    <x-backpack::menu-item title="{{__('cms.coupons')}}" icon="la la-percent" :link="backpack_url('coupon')" />
</x-backpack::menu-dropdown>

<x-backpack::menu-item title="{{__('cms.setting')}}" icon="la la-cog" :link="backpack_url('setting')" />
<x-backpack::menu-item title="{{__('cms.translation-manager')}}" icon="la la-language" :link="backpack_url('translation-manager')" />

<x-backpack::menu-item title="Menu" icon="la la-list" :link="backpack_url('menu-item')" />
