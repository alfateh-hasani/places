<header class="fixed w-full py-6 bg-white top-0 z-50 lg:block hidden">
  <div class="container">
    <div class="float-left">
      <a href="#">
        <img src="{{ asset('assets/img/logo.svg') }}" alt="Logo" />
      </a>
    </div>
    <ul class="menu absolute">
      <li class="float-left px-6">
        <a class="font-normal text-base text-black">Listings</a>
        <ul class="absolute pt-3">
          @for ($i = 0; $i < 4; $i++)
          <li>
            <a class="bg-white block py-1 px-4 mb-1 rounded-md border border-black font-normal text-base text-black hover:bg-gri hover:text-white ease-in-out duration-300">
              Sub Menu
            </a>
          </li>
          @endfor
        </ul>
      </li>
      <li class="float-left px-6">
        <a class="font-normal text-base text-black">Blog</a>
      </li>
      <li class="float-left px-6">
        <a class="font-normal text-base text-black">Contact Us</a>
      </li>
      <li class="clear-both"></li>
    </ul>
    <div class="right float-right">
      <div class="lang float-left">
        <button class="py-1.5 font-normal text-base text-black">
          <img src="{{ asset('assets/img/lang.svg') }}" class="inline-block" alt="Language" /> En
        </button>
        <ul class="absolute">
          <li><a class="bg-white block py-1 px-4 mb-1 rounded-md border border-black font-normal text-base text-black hover:bg-gri hover:text-white ease-in-out duration-300">Tr</a></li>
          <li><a class="bg-white block py-1 px-4 mb-1 rounded-md border border-black font-normal text-base text-black hover:bg-gri hover:text-white ease-in-out duration-300">Ar</a></li>
        </ul>
      </div>
      <div class="login float-left ml-5 relative">
        <button class="rounded-lg py-1.5 px-4 bg-gri font-normal text-base text-white">
          <img src="{{ asset('assets/img/login-header.svg') }}" class="inline-block" alt="Login" />
          LOGIN OR SIGN UP
          <img src="{{ asset('assets/img/header-arrow.svg') }}" class="inline-block" alt="Arrow" />
        </button>
        <ul class="absolute w-full">
          <li><a class="bg-white block py-1 px-4 mt-1 rounded-md border border-black font-normal text-base text-black hover:bg-gri hover:text-white ease-in-out duration-300">Login</a></li>
          <li><a class="bg-white block py-1 px-4 mt-1 rounded-md border border-black font-normal text-base text-black hover:bg-gri hover:text-white ease-in-out duration-300">Sign Up</a></li>
        </ul>
      </div>
      <div class="clear-both"></div>
    </div>
    <div class="clear-both"></div>
  </div>
</header>

<header class="fixed w-full py-4 bg-white top-0 z-50 lg:hidden">
  <div class="container">
    <div class="float-left w-7 h-7 bg-gri rounded-full text-center font-normal text-xs text-white uppercase py-1.5">
      ha
    </div>
    <div class="logo absolute">
      <a href="#">
        <img class="h-7" src="{{ asset('assets/img/logo.svg') }}" alt="Logo" />
      </a>
    </div>
    <div class="float-right py-1">
      <img src="{{ asset('assets/img/menu.svg') }}" alt="Menu" />
    </div>
    <div class="clear-both"></div>
  </div>
</header>
