<header class="fixed w-full py-6 bg-white top-0 z-50 lg:block hidden">
  <div class="container">
    <div class="float-left rtl:float-right">
      <a href="#">
        <img src="{{ asset('assets/img/logo.svg') }}" alt="Logo" />
      </a>
    </div>
    <ul class="menu absolute">
      <li class="float-left rtl:float-right px-6">
        <a class="font-normal text-base text-black">@lang('site.listings')</a>
        <ul class="absolute pt-3">
          @for ($i = 0; $i < 4; $i++)
          <li>
            <a class="bg-white block py-1 px-4 mb-1 rounded-md border border-black font-normal text-base text-black hover:bg-gri hover:text-white ease-in-out duration-300">
              @lang('site.sub_menu')
            </a>
          </li>
          @endfor
        </ul>
      </li>
      <li class="float-left rtl:float-right px-6">
        <a class="font-normal text-base text-black">@lang('site.blog')</a>
      </li>
      <li class="float-left rtl:float-right px-6">
        <a class="font-normal text-base text-black">@lang('site.contact_us')</a>
      </li>
      <li class="clear-both"></li>
    </ul>
    <div class="right float-right rtl:float-left">
      <div class="lang float-left rtl:float-right">
        <a href="/en" class="py-1.5 font-normal text-base text-black">
          <img src="{{ asset('assets/img/lang.svg') }}" class="inline-block" alt="Language" /> En
        </a>
       
      </div>
      <div class="login float-left rtl:float-right rtl:mr-5 ml-5 relative">
        <button class="rounded-lg py-1.5 px-4 bg-gri font-normal text-base text-white">
          <img src="{{ asset('assets/img/login-header.svg') }}" class="inline-block" alt="Login" />
          @lang('site.login_or_signup')
          <img src="{{ asset('assets/img/header-arrow.svg') }}" class="inline-block" alt="Arrow" />
        </button>
        <ul class="absolute w-full">
          <li><a class="bg-white block py-1 px-4 mt-1 rounded-md border border-black font-normal text-base text-black hover:bg-gri hover:text-white ease-in-out duration-300">@lang('site.login')</a></li>
          <li><a class="bg-white block py-1 px-4 mt-1 rounded-md border border-black font-normal text-base text-black hover:bg-gri hover:text-white ease-in-out duration-300">@lang('site.signup')</a></li>
        </ul>
      </div>
      <div class="clear-both"></div>
    </div>
    <div class="clear-both"></div>
  </div>
</header>

<header class="fixed w-full py-4 bg-white top-0 z-50 lg:hidden">
  <div class="container">
    <div class="float-left rtl:float-right w-7 h-7 bg-gri rounded-full text-center font-normal text-xs text-white uppercase py-1.5">
      ha
    </div>
    <div class="logo absolute">
      <a href="#">
        <img class="h-7" src="{{ asset('assets/img/logo.svg') }}" alt="Logo" />
      </a>
    </div>
    <div class="float-right rtl:float-left py-1">
      <img src="{{ asset('assets/img/menu.svg') }}" alt="Menu" />
    </div>
    <div class="clear-both"></div>
  </div>
</header>
