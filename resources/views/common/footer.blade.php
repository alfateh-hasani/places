<footer class="border-t border-blackopacity pt-12">
  <div class="container border-b border-blackopacity pb-6">
    <div class="lg:grid lg:grid-cols-3 lg:gap-8 max-w-full">
      <div>
        <img src="{{ asset('assets/img/places-logo-dark.webp') }}" width="180" height="35" loading="lazy" style="max-height: 35px" alt="{{ __('site.logo') }}" />
        <p class="font-normal text-sm lg:text-base text-black mt-8 text-justify">
            {{Config::get('settings.footer_'.app()->getLocale())}}
        </p>
      </div>
      <div class="my-6 lg:my-0">
        <h2 class="font-semibold text-xl text-black">
            {{__('site.quick_links')}}
        </h2> 
        <ul class="mt-4 lg:mt-10 grid grid-cols-2 gap-2 max-w-full mx-0">
          <li><a class="block font-light text-black mb-2 lg:mb-5 hover:text-price ease-in-out duration-300" href="{{route('home')}}">{{__('site.home')}}</a></li>
          <li><a class="block font-light text-black mb-2 lg:mb-5 hover:text-price ease-in-out duration-300" href="{{route('page','privacy-policy')}}"> {{__('site.privacy-policy')}}</a></li>
          <li><a class="block font-light text-black mb-2 lg:mb-5 hover:text-price ease-in-out duration-300" href="{{route('page','terms-and-conditions')}}"> {{__('site.terms')}}  </a></li>
          <li><a class="block font-light text-black mb-2 lg:mb-5 hover:text-price ease-in-out duration-300" href="{{route('page','blog')}}">{{__('site.blogs')}}</a></li>
          <li><a class="block font-light text-black mb-2 lg:mb-5 hover:text-price ease-in-out duration-300" href="{{route('page','contact')}}">
            {{__('site.contact')}}
          </a></li>
          <li><a class="block font-light text-black mb-2 lg:mb-5 hover:text-price ease-in-out duration-300" href="{{route('page','faq')}}">    {{__('site.faqs')}}  </a></li>
        </ul>
      </div>
      <div>
        <h2 class="font-semibold text-xl text-black">
            {{__('site.contact_us_menu')}}
        </h2>
        <ul class="mt-4 lg:mt-10">
          <li>
            <a class="block font-light text-black mb-5 hover:text-price ease-in-out duration-300" href="mailto:{{Config::get('settings.email')}}">
              <img class="inline-block me-3" src="{{ asset('assets/img/mail.svg') }}" alt="{{ __('site.mail_icon') }}" />
              {{Config::get('settings.email')}}
            </a>
          </li>
          <li>
            @php $footerPhone = preg_replace('/^00/', '+', (string) Config::get('settings.phone')); @endphp
            <a class="block font-light text-black mb-5 hover:text-price ease-in-out duration-300" href="tel:{{ $footerPhone }}">
              <img class="inline-block me-3" src="{{ asset('assets/img/tel.svg') }}" alt="{{ __('site.phone_icon') }}" />
              <span dir="ltr" style="direction:ltr; unicode-bidi:isolate;">{{ $footerPhone }}</span>
            </a>
          </li>
          <li>
            <a class="block font-light text-black mb-5 hover:text-price ease-in-out duration-300" href="#">
              <img class="inline-block me-3" src="{{ asset('assets/img/address.svg') }}" alt="{{ __('site.address_icon') }}" />
              {{Config::get('settings.address_'.app()->getLocale())}}
            </a>
          </li>
        </ul>
      </div>
    </div>
  </div>

  <div class="container py-4">
    <div class="float-left rtl:float-right rtl:sm:text-right w-full sm:w-auto text-center sm:text-left mb-3 sm:mb-0">
      <p class="inline-block font-normal text-base text-black">
        {{__('site.all_rights')}} © {{ date('Y') }}
      </p>
      <a class="inline-block font-normal text-base text-price" href="#">
        {{ Config::get('settings.seo_title_'.app()->getLocale()) }}
      </a>
    </div>

    <div class="float-right w-full sm:w-auto text-center sm:text-left rtl:float-left rtl:sm:text-right">
        @php
            $array =[
                'facebook' => Config::get('settings.facebook'),
                'twitter' => Config::get('settings.x'),
                'instagram' => Config::get('settings.instagram'),
                'linkedin' => Config::get('settings.linkedin'),
                'youtube' => Config::get('settings.youtube'),
                'tiktok' => Config::get('settings.tiktok'),
                'snapchat' => Config::get('settings.snapchat'),
            ]
        @endphp
      <ul class="social">
        @foreach($array as $key => $value)
            @if($value)
                <li class="inline-block">
                    <a href="{{$value}}" target="_blank" rel="noopener noreferrer" class="block w-8 h-8 bg-price rounded-lg relative hover:opacity-80 ease-in-out duration-300">
                        <img class="absolute" src="{{ asset('assets/img/'.$key.'.svg') }}" alt="{{$key}}" />
                    </a>
                </li>
            @endif
        @endforeach
      </ul>
    </div>

    <div class="clear-both"></div>
  </div>
</footer>
@stack('pop')
@guest('customer')
    @include('common.login-part')    
@endguest

 