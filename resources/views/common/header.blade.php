<header class="fixed w-full py-6 bg-white top-0 z-50 lg:block hidden">
  <div class="container">
    <div class="float-left rtl:float-right">
      <a href="{{ route('home')}}">
        <img src="{{ asset('assets/img/logo.svg') }}" alt="Logo" />
      </a>
    </div> 
    <ul class="menu absolute">
      <li class="float-left rtl:float-right px-6">
        <a  href="" class="font-normal text-base text-black">@lang('site.listings')</a>
        <ul class="absolute p-3 bg-white border border-border rounded-lg">
          @for ($i = 0; $i < 4; $i++)
          <li>
            <a class="block p-2 font-normal text-base text-black border-b border-border">@lang('site.sub_menu')</a>
          </li>
          @endfor
        </ul>
      </li>
      <li class="float-left rtl:float-right px-6">
        <a href="{{route('page','blog')}}" class="font-normal text-base text-black">@lang('site.blog')</a>
      </li>
      <li class="float-left rtl:float-right px-6">
        <a href="{{route('page','contact')}}" class="font-normal text-base text-black">@lang('site.contact_us')</a>
      </li>
      <li class="clear-both"></li>
    </ul>
    <div class="right float-right rtl:float-left">
      <div class="lang float-left rtl:float-right">
        
        @if(app()->getLocale() == 'ar')
        <a href="/en" class="py-1.5 font-normal text-base text-black block">
          <img src="{{ asset('assets/img/lang.svg') }}" class="inline-block" alt="Language" /> EN
        </a>
        @else
        <a href="/" class="py-1.5 font-normal text-base text-black block">
          <img src="{{ asset('assets/img/lang.svg') }}" class="inline-block" alt="Language" /> AR
        </a>

        @endif
       
      </div>
      <div class="login float-left rtl:float-right ltr:ml-5 rtl:mr-5 relative">
        @auth('customer')
          {{-- Show customer name and account links if logged in --}}
          <span class="font-normal text-base text-black inline-block py-1.5">
            @lang('site.hello'), {{ Auth::guard('customer')->user()->first_name }}
          </span>
          <ul class="absolute p-3 bg-white border border-border rounded-lg">
            <li>
              <a href="{{ route('customer.account') }}" class="block p-2 font-normal text-base text-black border-b border-border">
                @lang('site.account')
              </a>
            </li>
            <li>
              <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="block p-2 font-normal text-base text-black border-b border-border">
                @lang('site.logout')
              </a>
              <form id="logout-form" action="{{ route('customer.logout') }}" method="POST" style="display: none;">
                @csrf
              </form>
            </li>
            
          </ul>
        @else
          {{-- Show login/signup button if not logged in --}}
          <button data-src="#popup-5" data-fancybox dont-close-click-outside class="rounded-lg py-1.5 px-4 bg-gri font-normal text-base text-white">
            <img src="{{ asset('assets/img/login-header.svg') }}" class="inline-block" alt="Login" />
            @lang('site.login_or_signup')
          </button>
          <ul class="absolute p-3 bg-white border border-border rounded-lg" style="min-width: 190px">
            <li>
              <button data-src="#popup-5" data-fancybox dont-close-click-outside class="block p-2 font-normal text-base text-black border-b border-border">
                <img src="{{ asset('assets/img/login.svg') }}" class="w-5 inline-block" alt="Login" />
                @lang('site.login')
              </a>
            </li>
            <li>
              <button data-src="#popup-5" data-fancybox dont-close-click-outside class="block p-2 font-normal text-base text-black border-b border-border">
                <img src="{{ asset('assets/img/user.svg') }}" class="w-5 inline-block" alt="Login" />
                @lang('site.sign_up_new')
              </a>
            </li>
            
          </ul>
        @endauth
      </div>

      <div class="clear-both"></div>
    </div>
    <div class="clear-both"></div>
  </div>
</header>

<header class="fixed w-full py-4 bg-white top-0 z-50 border border-blackopacity lg:hidden" data-aos="zoom-in">
  <div class="container">
      <div class="cursor-pointer float-left w-7 h-7 bg-gri rounded-full text-center font-normal text-xs text-white uppercase py-1.5 login-button">ha</div>
      <div class="logo absolute">
          <a>
              <img class="h-7" src="{{ asset('assets/img/logo.svg') }}" />
          </a>
      </div>
      <div class="cursor-pointer float-right py-1 menu-button">
          <img src="{{ asset('assets/img/menu.svg') }}" />
      </div>
      <div class="clear-both"></div>
  </div>
  <div class="fixed w-[95vw] bg-white h-[100vh] top-0 p-5 right-menu">
      <button class="absolute ltr:right-5 rtl:left-5 top-5">
          <svg height="40" viewBox="0 0 32 32" width="40" xmlns="http://www.w3.org/2000/svg') }}" id="fi_2734822"><g id="Layer_22" data-name="Layer 22"><path d="m21 12.46-3.59 3.54 3.59 3.54a1 1 0 0 1 0 1.46 1 1 0 0 1 -.71.29 1 1 0 0 1 -.7-.29l-3.59-3.59-3.54 3.59a1 1 0 0 1 -.7.29 1 1 0 0 1 -.71-.29 1 1 0 0 1 0-1.41l3.54-3.59-3.54-3.54a1 1 0 0 1 1.41-1.41l3.54 3.54 3.54-3.54a1 1 0 0 1 1.46 1.41zm4.9 13.44a14 14 0 1 1 0-19.8 14 14 0 0 1 0 19.8zm-1.41-18.39a12 12 0 1 0 0 17 12 12 0 0 0 0-17z"></path></g>
          </svg>
      </button>
      <h6 class="font-semibold text-xl text-black mb-2">Menü</h6>
      <ul>
          <li class="mb-5">
              <a class="font-normal text-base text-black">Listings</a>
              <ul class="">
                  <li><a class="bg-white block py-1 px-4 mb-1 rounded-md font-normal text-base text-black opacity-70 hover:bg-gri hover:text-white ease-in-out duration-300">Sub Menu</a></li>
                  <li><a class="bg-white block py-1 px-4 mb-1 rounded-md font-normal text-base text-black opacity-70 hover:bg-gri hover:text-white ease-in-out duration-300">Sub Menu</a></li>
                  <li><a class="bg-white block py-1 px-4 mb-1 rounded-md font-normal text-base text-black opacity-70 hover:bg-gri hover:text-white ease-in-out duration-300">Sub Menu</a></li>
                  <li><a class="bg-white block py-1 px-4 mb-1 rounded-md font-normal text-base text-black opacity-70 hover:bg-gri hover:text-white ease-in-out duration-300">Sub Menu</a></li>
              </ul>
          </li>
          <li class="mb-5"><a class="font-normal text-base text-black">Blog</a></li>
          <li class="mb-5"><a class="font-normal text-base text-black">Contact Us</a></li>
      </ul>
      <div class="mt-20">
          <h6 class="font-semibold text-xl text-black">Site Map</h6>
          <ul class="mt-4 lg:mt-10">
              <li><a class="block font-light text-black mb-5 hover:text-price ease-in-out duration-300"><img class="inline-block me-3" src="{{ asset('assets/img/mail.svg') }}" /> Info@Testmail.Com</a></li>
              <li><a class="block font-light text-black mb-5 hover:text-price ease-in-out duration-300"><img class="inline-block me-3" src="{{ asset('assets/img/tel.svg') }}" /> +966-13-858-9000</a></li>
              <li><a class="block font-light text-black mb-5 hover:text-price ease-in-out duration-300"><img class="inline-block me-3" src="{{ asset('assets/img/address.svg') }}" /> Address Here</a></li>
          </ul>
      </div>
  </div>
  <div class="fixed w-[95vw] bg-white h-[100vh] top-0 p-5 left-menu">
      <button class="absolute ltr:right-5 rtl:left-5 top-5">
          <svg height="40" viewBox="0 0 32 32" width="40" xmlns="http://www.w3.org/2000/svg') }}" id="fi_2734822"><g id="Layer_22" data-name="Layer 22"><path d="m21 12.46-3.59 3.54 3.59 3.54a1 1 0 0 1 0 1.46 1 1 0 0 1 -.71.29 1 1 0 0 1 -.7-.29l-3.59-3.59-3.54 3.59a1 1 0 0 1 -.7.29 1 1 0 0 1 -.71-.29 1 1 0 0 1 0-1.41l3.54-3.59-3.54-3.54a1 1 0 0 1 1.41-1.41l3.54 3.54 3.54-3.54a1 1 0 0 1 1.46 1.41zm4.9 13.44a14 14 0 1 1 0-19.8 14 14 0 0 1 0 19.8zm-1.41-18.39a12 12 0 1 0 0 17 12 12 0 0 0 0-17z"></path></g>
          </svg>
      </button>
      <h6 class="font-semibold text-xl text-black mb-2">Profile</h6>
      <ul>
          <li class="mb-5"><a class="font-normal text-base text-black">Favorites</a></li>
          <li class="mb-5"><a class="font-normal text-base text-black">Address</a></li>
      </ul>
      <div class="mt-20">
          <h6 class="font-semibold text-xl text-black">Site Map</h6>
          <ul class="mt-4 lg:mt-10">
              <li><a class="block font-light text-black mb-5 hover:text-price ease-in-out duration-300"><img class="inline-block me-3" src="{{ asset('assets/img/mail.svg') }}" /> Info@Testmail.Com</a></li>
              <li><a class="block font-light text-black mb-5 hover:text-price ease-in-out duration-300"><img class="inline-block me-3" src="{{ asset('assets/img/tel.svg') }}" /> +966-13-858-9000</a></li>
              <li><a class="block font-light text-black mb-5 hover:text-price ease-in-out duration-300"><img class="inline-block me-3" src="{{ asset('assets/img/address.svg') }}" /> Address Here</a></li>
          </ul>
      </div>
      <div class="login text-center relative">
          <ul class="w-full">
              <li><a class="block py-1 px-4 mt-1 rounded-md border border-border font-normal text-base"><img src="{{ asset('assets/img/login.svg') }}" class="w-5 inline-block" /> Login</a></li>
              <li><a class="block py-1 px-4 mt-1 rounded-md border border-border font-normal text-base"><img src="{{ asset('assets/img/user.svg') }}" class="w-5 inline-block" /> Sign Up</a></li>
          </ul>
      </div>
  </div>
</header>
