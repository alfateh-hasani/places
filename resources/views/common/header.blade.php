<div id="download-app-banner" class="fixed bottom-0 left-0 right-0  shadow-[0_-8px_25px_rgba(0,0,0,0.1)] transform translate-y-full transition-transform duration-300 ease-in-out md:hidden z-50">
    <div class="relative px-4 py-3">
        <!-- Close Button -->
        <button id="close-banner" class="absolute top-1 left-2 rtl:right-2 rtl:left-auto text-gray-400 hover:text-gray-600 p-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>

        <!-- Banner Content -->
        <div class="flex items-center justify-between space-x-4 rtl:space-x-reverse">
            <!-- App Icon -->
            <div class="flex-shrink-0">
                <img src="{{ asset('assets/img/places-logo-dark.png') }}" alt="Dyafa App" class="w-16 h-16 rounded-xl">
            </div>
 
            <!-- Text Content -->
            <div class="flex-grow">
                <h3 class="font-bold text-lg mb-1">@lang('site.download_app')</h3>
                <p class="text-sm text-gray-600">@lang('site.download_app_desc')</p>
            </div>

            <!-- Download Buttons -->
            <div class="flex flex-col space-y-2">
                <a href="https://apps.apple.com/us/app/dyafa-%D8%B6%D9%8A%D8%A7%D9%81%D8%A9/id6711337244"
                   class="block w-32" target="_blank" rel="noopener noreferrer">
                    <img src="{{ asset('img/AppStore.svg') }}" alt="App Store" class="w-full">
                </a>
                <a href="https://play.google.com/store/apps/details?id=co.Placess.app"
                   class="block w-32" target="_blank" rel="noopener noreferrer">
                    <img src="{{ asset('img/GooglePlay.svg') }}" alt="Google Play" class="w-full">
                </a>
            </div>
        </div>
    </div>
</div>

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const banner = document.getElementById('download-app-banner');
    const closeButton = document.getElementById('close-banner');
    
    // التحقق من وجود الكوكيز
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
    }

    // إظهار البانر إذا لم يكن هناك كوكيز
    if (!getCookie('app_banner_closed')) {
        setTimeout(() => {
            banner.classList.remove('translate-y-full');
        }, 1000);
    }

    // معالجة إغلاق البانر
    closeButton.addEventListener('click', function() {
        banner.classList.add('translate-y-full');
        
        // إضافة كوكيز لمدة 24 ساعة
        const date = new Date();
        date.setTime(date.getTime() + (24 * 60 * 60 * 1000));
        document.cookie = `app_banner_closed=1; expires=${date.toUTCString()}; path=/`;
    });

    // التحقق مما إذا كان المستخدم يستخدم تطبيق الويب
    if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
        banner.style.display = 'none';
    }
});
</script>
@endpush

<header class="fixed w-full py-6  top-0 z-50 lg:block hidden" data-aos="fade-down">
  <div class="container">
    <div class="flex items-center justify-between">
    <div>
      <a href="{{ route('home')}}">
        <img src="{{ asset('assets/img/places-logo-dark.png') }}?1" alt="{{ __('site.logo') }}" style="max-height: 35px" />
      </a>
    </div>
    <ul class="menu absolute flex items-center">
      {{-- <li class="float-left rtl:float-right px-6">
        <a  href="" class="font-normal text-base text-black">@lang('site.listings')</a>
        <ul class="absolute p-3  border border-border rounded-lg">
          @for ($i = 0; $i < 4; $i++)
          <li>
            <a class="block p-2 font-normal text-base text-black border-b border-border">@lang('site.sub_menu')</a>
          </li>
          @endfor
        </ul>
      </li> --}}

      @include('common.parts.nav-links', ['liClass' => 'px-6'])
    </ul>
    <div class="right flex items-center">
      <div class="lang">

        @foreach(LaravelLocalization::getSupportedLocales() as $localeCode => $properties)
          @continue($localeCode == app()->getLocale())
        <a href="{{ LaravelLocalization::getLocalizedURL($localeCode, null, [], true) }}" class="py-1.5 font-normal text-base text-black block">
          <img src="{{ asset('assets/img/lang.svg') }}" class="inline-block" alt="{{ __('site.language') }}" /> {{ strtoupper($localeCode) }}
        </a>
        @endforeach

      </div>
      <div class="login ltr:ml-5 rtl:mr-5 relative">
        @auth('customer')
          {{-- Show customer name and account links if logged in --}}
          <span class="font-normal text-base text-black inline-block py-1.5">
            @lang('site.hello'), {{ Auth::guard('customer')->user()->first_name }}
          </span>
          <ul class="absolute top-full left-0 z-30 p-3 min-w-[200px] bg-[#fff] shadow-lg border border-border rounded-lg">
            <li>
              <a href="{{ route('customer.account') }}" class="block p-2 font-normal text-base text-black border-b border-border">
                @lang('site.account')
              </a>
            </li>
            <li>
              <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="block p-2 font-normal text-base text-black">
                @lang('site.logout')
              </a>
              <form id="logout-form" action="{{ route('customer.logout') }}" method="POST" style="display: none;">
                @csrf
              </form>
            </li>
            
          </ul>
        @else
          {{-- Not logged in: a single button opening the phone-input modal, which
               then decides login vs. sign-up. No hover dropdown (it only repeated
               the same modal and overflowed the viewport). --}}
          <button type="button" data-src="#popup-5" data-fancybox dont-close-click-outside class="rounded-lg py-1.5 px-4 bg-gri font-normal text-base">
            <img src="{{ asset('assets/img/login-header3.svg') }}" class="inline-block" alt="" />
            @lang('site.login_or_signup')
          </button>
        @endauth
      </div>
    </div>
    </div>
  </div>
</header>

<header class="fixed w-full py-4  top-0 z-50 border border-blackopacity lg:hidden" data-aos="zoom-in">
  <div class="container">
   
    <button type="button" class="cursor-pointer float-left w-7 h-7 bg-gri rounded-full text-center font-normal text-xs text-white uppercase py-1.5 login-button">
      @auth('customer') {{ substr(Auth::guard('customer')->user()->first_name, 0, 2) }}    @endauth
    </button>
    

      <div class="logo absolute">
          <a href="{{ route('home')}}">
              <img class="h-7" src="{{ asset('assets/img/places-logo-dark.png') }}" alt="{{ config('app.name') }}" />
          </a>
      </div>
      <button type="button" class="cursor-pointer float-right py-1 menu-button">
          <img src="{{ asset('assets/img/menu.svg') }}" />
      </button>
      <div class="clear-both"></div>
  </div>
  <div class="fixed w-[95vw]  h-[100vh] top-0 p-5 right-menu">
      <button type="button" class="absolute ltr:right-5 rtl:left-5 top-5">
          <svg style="fill: #fff;" height="40" viewBox="0 0 32 32" width="40" xmlns="http://www.w3.org/2000/svg"><g data-name="Layer 22"><path d="m21 12.46-3.59 3.54 3.59 3.54a1 1 0 0 1 0 1.46 1 1 0 0 1 -.71.29 1 1 0 0 1 -.7-.29l-3.59-3.59-3.54 3.59a1 1 0 0 1 -.7.29 1 1 0 0 1 -.71-.29 1 1 0 0 1 0-1.41l3.54-3.59-3.54-3.54a1 1 0 0 1 1.41-1.41l3.54 3.54 3.54-3.54a1 1 0 0 1 1.46 1.41zm4.9 13.44a14 14 0 1 1 0-19.8 14 14 0 0 1 0 19.8zm-1.41-18.39a12 12 0 1 0 0 17 12 12 0 0 0 0-17z"></path></g>
          </svg>
      </button>
      <h6 class="font-semibold text-xl text-black mb-2">
        @lang('site.menu')
      </h6>
      <ul>
          @include('common.parts.nav-links', ['liClass' => 'mb-5'])
      </ul>
      <div class="mt-2">
          <h6 class="font-semibold text-xl text-black"> @lang('site.contact')</h6>
          <ul class="mt-4 lg:mt-10">
            <li><a href="mailto:{{ Config::get('settings.email') }}" class="block font-light text-black mb-5 hover:text-price ease-in-out duration-300"><img class="inline-block me-3" src="{{ asset('assets/img/mail.svg') }}" />   {{Config::get('settings.email')}}</a></li>
            @php $headerPhone = preg_replace('/^00/', '+', (string) Config::get('settings.phone')); @endphp
            <li><a href="tel:{{ $headerPhone }}" class="block font-light text-black mb-5 hover:text-price ease-in-out duration-300"><img class="inline-block me-3" src="{{ asset('assets/img/tel.svg') }}" /> <span dir="ltr" style="direction:ltr; unicode-bidi:isolate; display:inline-block;">{{ $headerPhone }}</span></a></li>
            <li><a href="https://www.google.com/maps/search/?api=1&query={{ urlencode(Config::get('settings.address_'.app()->getLocale())) }}" target="_blank" rel="noopener noreferrer" class="block font-light text-black mb-5 hover:text-price ease-in-out duration-300"><img class="inline-block me-3" src="{{ asset('assets/img/address.svg') }}" />          {{Config::get('settings.address_'.app()->getLocale())}}</a></li>
        </ul>
      </div>
  </div>
  <div class="fixed w-[95vw]  h-[100vh] top-0 p-5 left-menu">
      <button type="button" class="absolute ltr:right-5 rtl:left-5 top-5">
          <svg style="fill: #fff;" height="40" viewBox="0 0 32 32" width="40" xmlns="http://www.w3.org/2000/svg"><g data-name="Layer 22"><path d="m21 12.46-3.59 3.54 3.59 3.54a1 1 0 0 1 0 1.46 1 1 0 0 1 -.71.29 1 1 0 0 1 -.7-.29l-3.59-3.59-3.54 3.59a1 1 0 0 1 -.7.29 1 1 0 0 1 -.71-.29 1 1 0 0 1 0-1.41l3.54-3.59-3.54-3.54a1 1 0 0 1 1.41-1.41l3.54 3.54 3.54-3.54a1 1 0 0 1 1.46 1.41zm4.9 13.44a14 14 0 1 1 0-19.8 14 14 0 0 1 0 19.8zm-1.41-18.39a12 12 0 1 0 0 17 12 12 0 0 0 0-17z"></path></g>
          </svg>
      </button>
      @auth('customer')
        <h6 class="font-semibold text-xl text-black mb-2">@lang('site.profile')</h6>
        <ul>
          
            <li class="mb-5"><a href="{{ route('customer.account') }}"
              class="font-normal text-base text-black">   @lang('site.account')</a></li>
            <li class="mb-5"><a href="{{ route('customer.booking') }}"
                class="font-normal text-base text-black">   @lang('site.my_reservations')</a></li>
               
            <li class="mb-5"><a href="{{ route('customer.favorite') }}"
                  class="font-normal text-base text-black">   @lang('site.my_favorate')</a></li>
        </ul>
      @endauth
      <div class="login text-center relative">
          <ul class="w-full">
            @auth('customer')
              <li><a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="block py-1 px-4 mt-1 rounded-md border border-border font-normal text-base"><img src="{{ asset('assets/img/user.svg') }}" class="w-5 inline-block" /> @lang('site.logout')</a></li>
              <form id="logout-form" action="{{ route('customer.logout') }}" method="POST" style="display: none;">
                @csrf
              </form>
            @else
              <li><a data-src="#popup-5" data-fancybox dont-close-click-outside class="block py-1 px-4 mt-1 rounded-md border border-border font-normal text-base"><img src="{{ asset('assets/img/login.svg') }}" class="w-5 inline-block" /> @lang('site.login')</a></li>
              <li><a data-src="#popup-5" data-fancybox dont-close-click-outside class="block py-1 px-4 mt-1 rounded-md border border-border font-normal text-base"><img src="{{ asset('assets/img/user.svg') }}" class="w-5 inline-block" /> @lang('site.sign_up_new')</a></li>
            @endauth
          </ul>
      </div>
  </div>
</header>
