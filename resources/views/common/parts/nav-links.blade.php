@php($liClass = $liClass ?? '')
<li class="{{ $liClass }}"><a href="{{ route('apartments.search') }}" class="font-normal text-base text-black">@lang('site.apartments_list')</a></li>
<li class="{{ $liClass }}"><a href="{{ route('page', 'blog') }}" class="font-normal text-base text-black">@lang('site.blog')</a></li>
<li class="{{ $liClass }}"><a href="{{ route('page', 'contact') }}" class="font-normal text-base text-black">@lang('site.contact_us')</a></li>
