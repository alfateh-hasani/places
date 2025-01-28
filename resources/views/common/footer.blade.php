<footer class="border-t border-blackopacity pt-12">
  <div class="container border-b border-blackopacity pb-6">
    <div class="lg:grid lg:grid-cols-3 lg:gap-8 max-w-full">
      <div>
        <img src="{{ asset('assets/img/logo.svg') }}" alt="Logo" />
        <p class="font-normal text-sm lg:text-base text-black mt-8 text-justify">
            {{Config::get('settings.footer_'.app()->getLocale())}}
        </p>
      </div>
      <div class="my-6 lg:my-0">
        <h6 class="font-semibold text-xl text-black">
            {{__('site.quick_links')}}
        </h6> 
        <ul class="mt-4 lg:mt-10 grid grid-cols-2 gap-2 max-w-full mx-0">
          <li><a class="block font-light text-black mb-2 lg:mb-5 hover:text-price ease-in-out duration-300" href="{{route('home')}}">{{__('site.home')}}</a li>
          <li><a class="block font-light text-black mb-2 lg:mb-5 hover:text-price ease-in-out duration-300" href="{{route('page','privacy-policy')}}"> {{__('site.privacy-policy')}}</a></li>
          <li><a class="block font-light text-black mb-2 lg:mb-5 hover:text-price ease-in-out duration-300" href="{{route('page','terms-and-conditions')}}"> {{__('site.terms')}}  </a></li>
          <li><a class="block font-light text-black mb-2 lg:mb-5 hover:text-price ease-in-out duration-300" href="{{route('page','contact')}}">{{__('site.blogs')}}</a></li>
          <li><a class="block font-light text-black mb-2 lg:mb-5 hover:text-price ease-in-out duration-300" href="{{route('page','contact')}}">
            {{__('site.contact')}}  
          </a></li>
          <li><a class="block font-light text-black mb-2 lg:mb-5 hover:text-price ease-in-out duration-300" href="{{route('page','contact')}}">
            {{__('site.contact_us_menu')}}  
          </a></li>
          <li><a class="block font-light text-black mb-2 lg:mb-5 hover:text-price ease-in-out duration-300" href="{{route('page','faq')}}">    {{__('site.faqs')}}  </a></li>
        </ul>
      </div>
      <div>
        <h6 class="font-semibold text-xl text-black">
            {{__('site.contact_us_menu')}}
        </h6>
        <ul class="mt-4 lg:mt-10">
          <li>
            <a class="block font-light text-black mb-5 hover:text-price ease-in-out duration-300" href="mailto:{{Config::get('settings.email')}}">
              <img class="inline-block me-3" src="{{ asset('assets/img/mail.svg') }}" alt="Mail Icon" />
              {{Config::get('settings.email')}}
            </a>
          </li>
          <li>
            <a class="block font-light text-black mb-5 hover:text-price ease-in-out duration-300" href="tel:{{Config::get('settings.phone')}}">
              <img class="inline-block me-3" src="{{ asset('assets/img/tel.svg') }}" alt="Phone Icon" />
              {{Config::get('settings.phone')}}
            </a>
          </li>
          <li>
            <a class="block font-light text-black mb-5 hover:text-price ease-in-out duration-300" href="#">
              <img class="inline-block me-3" src="{{ asset('assets/img/address.svg') }}" alt="Address Icon" />
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
            ]
        @endphp
      <ul class="social">
        @foreach($array as $key => $value)
            @if($value)
                <li class="inline-block">
                    <a href="{{$value}}" target="_blank" class="block w-8 h-8 bg-blackopacity rounded-lg relative hover:bg-price ease-in-out duration-300">
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

<div>
 <!-- Start of LiveChat (www.livechat.com) code -->
<script>
  window.__lc = window.__lc || {};
  window.__lc.license = 19004757;
  window.__lc.integration_name = "manual_onboarding";
  window.__lc.product_name = "livechat";
  ;(function(n,t,c){function i(n){return e._h?e._h.apply(null,n):e._q.push(n)}var e={_q:[],_h:null,_v:"2.0",on:function(){i(["on",c.call(arguments)])},once:function(){i(["once",c.call(arguments)])},off:function(){i(["off",c.call(arguments)])},get:function(){if(!e._h)throw new Error("[LiveChatWidget] You can't use getters before load.");return i(["get",c.call(arguments)])},call:function(){i(["call",c.call(arguments)])},init:function(){var n=t.createElement("script");n.async=!0,n.type="text/javascript",n.src="https://cdn.livechatinc.com/tracking.js",t.head.appendChild(n)}};!n.__lc.asyncInit&&e.init(),n.LiveChatWidget=n.LiveChatWidget||e}(window,document,[].slice))
</script>
 
</div>
<!-- End of LiveChat code -->
