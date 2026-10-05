{{-- Mobile-only account navigation. The sidebar below is hidden on mobile
     (hidden lg:block), so on phones this horizontal, scrollable bar is the only
     way to move between the account pages. --}}
<nav class="lg:hidden mb-8 overflow-x-auto" aria-label="{{ __('site.account') }}">
    <ul class="flex gap-3 whitespace-nowrap pb-2">
        <li class="shrink-0">
            <a href="{{route('customer.account')}}" @if(request()->routeIs('customer.account')) aria-current="page" @endif
                class="inline-flex items-center gap-2 px-4 py-2 rounded-full border bg-white text-sm {{request()->routeIs('customer.account') ? 'text-price border-price' : 'text-title border-border'}}">
                {{__('site.profile')}}
            </a>
        </li>
        <li class="shrink-0">
            <a href="{{route('customer.booking')}}" @if(request()->routeIs('customer.booking*')) aria-current="page" @endif
                class="inline-flex items-center gap-2 px-4 py-2 rounded-full border bg-white text-sm {{request()->routeIs('customer.booking*') ? 'text-price border-price' : 'text-title border-border'}}">
                {{__('site.my_reservations')}}
            </a>
        </li>
        <li class="shrink-0">
            <a href="{{route('customer.favorite')}}" @if(request()->routeIs('customer.favorite')) aria-current="page" @endif
                class="inline-flex items-center gap-2 px-4 py-2 rounded-full border bg-white text-sm {{request()->routeIs('customer.favorite') ? 'text-price border-price' : 'text-title border-border'}}">
                {{__('site.my_favorate')}}
            </a>
        </li>
        <li class="shrink-0">
            <a href="{{route('customer.notifications')}}" @if(request()->routeIs('customer.notifications')) aria-current="page" @endif
                class="inline-flex items-center gap-2 px-4 py-2 rounded-full border bg-white text-sm {{request()->routeIs('customer.notifications') ? 'text-price border-price' : 'text-title border-border'}}">
                {{__('site.notifications')}}
                @php $unreadMobileNotifications = $customer->unreadWebNotifications()->count(); @endphp
                @if ($unreadMobileNotifications)
                    <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full bg-price text-white text-xs font-semibold">{{ $unreadMobileNotifications }}</span>
                @endif
            </a>
        </li>
    </ul>
</nav>

<div class="hidden lg:block bg-white p-7 rounded-2xl">
    <p class="font-semibold text-lg">  
        {{$customer->first_name}} {{$customer->last_name}}
    </p>
    <p class="font-normal text-sm text-reviews mb-5">
        {{$customer->email}}
    </p>
    <ul class="border-t border-border">
        <li>
            <a href="{{route('customer.account')}}" class="block py-4 {{request()->routeIs('customer.account') ?'text-price' :''}}">
                <div class="inline-block w-6">
                    <svg class="inline-block" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
                        <path id="user" fill="currentColor" d="M11,2A10,10,0,1,0,21,12,10.01,10.01,0,0,0,11,2ZM4.955,19.006a7.532,7.532,0,0,1,5.852-2.941c.035,0,.069.01.1.01h.034c.033,0,.062-.009.095-.01a7.522,7.522,0,0,1,5.9,3.025,9.227,9.227,0,0,1-11.989-.084Zm5.962-3.688c-.037,0-.072.006-.109.007a3.311,3.311,0,1,1,.235,0C11,15.325,10.96,15.318,10.917,15.318Zm6.575,3.275a8.271,8.271,0,0,0-4.607-3.034,4.065,4.065,0,1,0-3.918,0,8.283,8.283,0,0,0-4.556,2.95,9.266,9.266,0,1,1,13.081.087Z" transform="translate(-1 -2)"/>
                    </svg>
                </div>
                <p class="inline-block ms-4 font-normal text-base">
                    {{__('site.profile')}}
                </p>
            </a>
        </li>
        <li>
            <a href="{{route('customer.booking')}}" class="block py-4 {{request()->routeIs('customer.booking*') ?'text-price' :''}}">
                <div class="inline-block w-6">
                    <svg class="inline-block" xmlns="http://www.w3.org/2000/svg" width="21" height="21" viewBox="0 0 21 21">
                        <g id="event" transform="translate(-1.5 -1.5)">
                            <rect id="Rectangle_19429" data-name="Rectangle 19429" width="20" height="18" rx="4" transform="translate(2 4)" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="1"/>
                            <path id="Path_4484" data-name="Path 4484" d="M2,8A4,4,0,0,1,6,4H18a4,4,0,0,1,4,4V9H2Z" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="1"/>
                            <path id="Path_4485" data-name="Path 4485" d="M6,2V6M18,2V6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="1"/>
                            <path id="Path_4486" data-name="Path 4486" d="M12,12l1.458,1.994,2.347.77-1.446,2-.007,2.47L12,18.48l-2.351.756-.007-2.47-1.446-2,2.347-.77Z" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="1"/>
                        </g>
                    </svg>
                </div>
                <p class="inline-block ms-4 font-normal text-base">
                    {{__('site.my_reservations')}}
                </p>
            </a>
        </li>
        <li>
            <a href="{{route('customer.favorite')}}" class="block py-4 {{request()->routeIs('customer.favorite') ?'text-price' :''}}">
                <div class="inline-block w-6">
                    <svg class="inline-block" xmlns="http://www.w3.org/2000/svg" width="24.13" height="21.2" viewBox="0 0 24.13 21.2">
                        <path id="Icon_feather-heart" data-name="Icon feather-heart" d="M23.485,6.265a6.033,6.033,0,0,0-8.535,0L13.788,7.428,12.625,6.265A6.035,6.035,0,1,0,4.091,14.8l1.163,1.163L13.788,24.5l8.535-8.535L23.485,14.8a6.033,6.033,0,0,0,0-8.535Z" transform="translate(-1.723 -3.897)" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2"/>
                    </svg>
                </div>
                <p class="inline-block ms-4 font-normal text-base">
                    {{__('site.my_favorate')}}
                </p>
            </a>
        </li>
        <li>
            @php $unreadNotifications = $customer->unreadWebNotifications()->count(); @endphp
            <a href="{{route('customer.notifications')}}" class="flex items-center py-4 {{request()->routeIs('customer.notifications') ?'text-price' :''}}">
                <span class="inline-block w-6">
                    <svg class="inline-block" xmlns="http://www.w3.org/2000/svg" width="18" height="20" viewBox="0 0 17.997 19.86">
                        <g transform="translate(-3.9 -2.4)">
                            <path d="M18.5,8.6a5.6,5.6,0,1,0-11.2,0c0,6.532-2.8,8.4-2.8,8.4H21.3s-2.8-1.866-2.8-8.4" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2"/>
                            <path d="M18.634,31.5a1.866,1.866,0,0,1-3.229,0" transform="translate(-4.121 -10.77)" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2"/>
                        </g>
                    </svg>
                </span>
                <p class="ms-4 font-normal text-base">
                    {{__('site.notifications')}}
                </p>
                @if ($unreadNotifications)
                    <span class="ms-auto inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full bg-price text-white text-xs font-semibold">{{ $unreadNotifications }}</span>
                @endif
            </a>
        </li>
        <li>
            <form id="logout-form" action="{{ route('customer.logout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full block py-3 bg-[#fdede9] hover:bg-[#fbdbd1] text-center rounded-lg text-[#ef552c] font-medium ease-in-out duration-300 mt-20">
                    <span class="inline-block w-6">
                        <svg class="inline-block" xmlns="http://www.w3.org/2000/svg" width="17.501" height="17.5" viewBox="0 0 17.501 17.5">
                            <path id="logout" d="M20.53,12.53l-3,3a.75.75,0,0,1-1.06-1.061l1.72-1.72H9a.75.75,0,0,1,0-1.5H18.19l-1.72-1.72a.75.75,0,0,1,1.061-1.061l3,3a.75.75,0,0,1,0,1.061ZM9.75,20A.75.75,0,0,0,9,19.25H6A1.252,1.252,0,0,1,4.75,18V6A1.252,1.252,0,0,1,6,4.75H9a.75.75,0,0,0,0-1.5H6A2.752,2.752,0,0,0,3.25,6V18A2.752,2.752,0,0,0,6,20.75H9A.75.75,0,0,0,9.75,20Z" transform="translate(-3.25 -3.25)" fill="currentColor"/>
                        </svg>
                    </span>
                    <span class="inline-block ms-4 font-normal text-base">
                        {{__('site.logout')}}
                    </span>
                </button>
            </form>
        </li>
    </ul>
</div>