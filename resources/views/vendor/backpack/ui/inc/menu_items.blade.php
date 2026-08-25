{{-- Dashboard --}}
<li class="nav-item">
    <a class="nav-link" href="{{ backpack_url('dashboard') }}">
        <i class="la la-home nav-icon"></i> {{ trans('backpack::base.dashboard') }}
    </a>
</li>

{{-- Smart Locks --}}
@can('smartlock.list')
    <x-backpack::menu-item title="{{__('cms.smart_locks')}}" icon="la la-lock" :link="backpack_url('smart-lock')" />
@endcan

{{-- Buildings --}}
@can('building.list')
    <x-backpack::menu-item title="{{__('cms.buildings')}}" icon="la la-building" :link="backpack_url('buildings')" />
@endcan

{{-- Features --}}
@can('feature.list')
    <x-backpack::menu-item title="{{__('cms.features')}}" icon="la la-list" :link="backpack_url('feature')" />
@endcan

{{-- Apartments --}}
@can('apartment.list')
    <x-backpack::menu-item title="{{__('cms.apartments')}}" icon="la la-home" :link="backpack_url('apartment')" />
@endcan

{{-- Apartment Label --}}
@can('apartmentlabel.list')
    <x-backpack::menu-item title="{{__('cms.apartment_label')}}" icon="la la-tag" :link="backpack_url('apartment-label')" />
@endcan

{{-- Policies --}}
@can('policy.list')
    <x-backpack::menu-item title="{{__('cms.policies')}}" icon="la la-file-alt" :link="backpack_url('policy')" />
@endcan

{{-- Cities --}}
@can('city.list')
    <x-backpack::menu-item title="{{__('cms.cities')}}" icon="la la-map-marker" :link="backpack_url('city')" />
@endcan

{{-- Notifications --}}
@can('notification.list')
    <x-backpack::menu-item title="{{__('cms.notification')}}" icon="la la-bell" :link="backpack_url('notifications')" />
@endcan

{{-- Contents Dropdown --}}
@canany(['slider.list', 'sliderapp.list', 'advantage.list', 'page.list', 'blog.list','faq.list','faqCategory.list','sitefeature.list'])
    <x-backpack::menu-dropdown title="{{__('cms.contents')}}" icon="la la-file">
        @can('slider.list')
            <x-backpack::menu-item title="{{__('cms.sliders')}}" icon="la la-image" :link="backpack_url('sliders')" />
        @endcan

        @can('sliderapp.list')
            <x-backpack::menu-item title="{{__('cms.sliders_app')}}" icon="la la-mobile" :link="backpack_url('sliders-app')" />
        @endcan

        {{-- @can('advantage.list')
            <x-backpack::menu-item title="{{__('cms.advantages')}}" icon="la la-thumbs-up" :link="backpack_url('advantages')" />
        @endcan --}}

        @can('page.list')
            <x-backpack::menu-item title="{{__('cms.pages')}}" icon="la la-file-text" :link="backpack_url('pages')" />
        @endcan

        @can('blog.list')
            <x-backpack::menu-item title="{{__('cms.blogs')}}" icon="la la-pen" :link="backpack_url('blogs')" />
        @endcan
        @can('faq.list')
            <x-backpack::menu-item title="{{__('cms.faqs')}}" icon="la la-question" :link="backpack_url('faq')" />
        @endcan
        @can('faqCategory.list')
            <x-backpack::menu-item title="{{__('cms.faq_categories')}}" icon="la la-question" :link="backpack_url('faq-category')" />
        @endcan
        @can('sitefeature.list')
            <x-backpack::menu-item title="{{__('cms.site_features')}}" icon="la la-star" :link="backpack_url('site-feature')" />
        @endcan
    </x-backpack::menu-dropdown>
@endcanany

{{-- Booking Management --}}
@can('booking.list')
    <x-backpack::menu-item title="{{__('cms.booking_management')}}" icon="la la-calendar-check" :link="backpack_url('booking')" />
@endcan

{{-- Canceled Bookings (Customer Cancellations) --}}
@can('booking.list')
    <x-backpack::menu-item title="{{__('cms.canceled_bookings')}}" icon="la la-times-circle" :link="backpack_url('canceled-bookings')" />
@endcan

{{-- Refunds tracker --}}
@can('refund.list')
    <x-backpack::menu-item title="{{__('cms.refunds')}}" icon="la la-money-bill-wave" :link="backpack_url('refund')" />
@endcan

{{-- Date-change requests --}}
@can('date_change.list')
    <x-backpack::menu-item title="{{__('cms.date_change_requests')}}" icon="la la-calendar-check" :link="backpack_url('date-change-requests')" />
@endcan

{{-- Airbnb Booking Management --}}
@can('booking.list')
    <x-backpack::menu-item title="{{__('cms.airbnb_booking_management')}}" icon="la la-airbnb" :link="backpack_url('airbnb-booking')" />
@endcan

{{-- Booking Channel Conflicts --}}
@can('booking_channel_conflict.list')
    <x-backpack::menu-item title="{{__('cms.booking_channel_conflicts')}}" icon="la la-exclamation-triangle" :link="backpack_url('booking-channel-conflicts')" />
@endcan

{{-- customer --}}

@can('customer.list')
    <x-backpack::menu-item title="{{__('cms.customers')}}" icon="la la-users" :link="backpack_url('customer')" />
@endcan
{{-- services and bookin service --}}
@canany(['service.list', 'bookingService.list'])
    <x-backpack::menu-dropdown title="{{__('cms.services')}}" icon="la la-cogs">
        @can('service.list')
            <x-backpack::menu-item title="{{__('cms.services')}}" icon="la la-cogs" :link="backpack_url('service')" />
        @endcan

        @can('bookingService.list')
        <x-backpack::menu-item title="طلبات الخدمات" icon="la la-list" :link="backpack_url('service-booking')" />
        @endcan
    </x-backpack::menu-dropdown>
@endcanany

{{-- Finance Dropdown --}}
@canany(['transaction.list', 'coupon.list'])
    <x-backpack::menu-dropdown title="{{__('cms.finance')}}" icon="la la-dollar-sign">
        @can('transaction.list')
            <x-backpack::menu-item title="{{__('cms.transactions')}}" icon="la la-money" :link="backpack_url('transaction')" />
        @endcan

        @can('coupon.list')
            <x-backpack::menu-item title="{{__('cms.coupons')}}" icon="la la-percent" :link="backpack_url('coupon')" />
        @endcan
    </x-backpack::menu-dropdown>
@endcanany

{{-- Settings --}}
@can('setting.view')
    <x-backpack::menu-item title="{{__('cms.setting')}}" icon="la la-cog" :link="backpack_url('setting')" />
@endcan

{{-- Translation Manager --}}
@can('translation-manager.view')
    <x-backpack::menu-item title="{{__('cms.translation-manager')}}" icon="la la-language" :link="backpack_url('translation-manager')" />
@endcan

{{-- Administrators Dropdown --}}
@canany(['user.list', 'role.list', 'permission.list'])
    <x-backpack::menu-dropdown title="المسؤولين" icon="la la-puzzle-piece">
        @can('user.list')
            <x-backpack::menu-dropdown-item title="المستخدمون" icon="la la-user" :link="backpack_url('user')" />
        @endcan
        @can('role.list')
            <x-backpack::menu-dropdown-item title="الأدوار" icon="la la-group" :link="backpack_url('role')" />
        @endcan
        @can('permission.list')
            <x-backpack::menu-dropdown-item title="الأذونات" icon="la la-key" :link="backpack_url('permission')" />
        @endcan
    </x-backpack::menu-dropdown>
@endcanany
<x-backpack::menu-item title="رسائل التواصل" icon="la la-envelope" :link="backpack_url('contact-us')" />

<x-backpack::menu-item title="📊 التقارير" icon="la la-chart-bar" :link="backpack_url('reports')" />
<x-backpack::menu-item title="⏳   الخروج اليومي" icon="la la-sign-out-alt" :link="backpack_url('reports/daily-checkout')" />
<x-backpack::menu-item title="🏠   الدخول اليومي" icon="la la-sign-in-alt" :link="backpack_url('reports/daily-checkin')" />

<x-backpack::menu-item title="📝 مراجعات العملاء" icon="la la-star" :link="backpack_url('review')" />

<x-backpack::menu-item title="التصنيفات" icon="la la-question" :link="backpack_url('category')" />

<x-backpack::menu-item title="التعريف" icon="la la-question" :link="backpack_url('onboarding')" />







<x-backpack::menu-item title='الإعدادات' icon='la la-cog' :link="backpack_url('setting')" />
<x-backpack::menu-item title="سجل التغييرات" icon="la la-stream" :link="backpack_url('activity-log')" />