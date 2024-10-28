<div class="hidden lg:block bg-white p-7 rounded-2xl">
    <p class="font-semibold text-lg">  
        {{$customer->first_name}} {{$customer->last_name}}
    </p>
    <p class="font-normal text-sm text-reviews mb-5">
        {{$customer->email}}
    </p>
    <ul class="border-t border-border">
        <li>
            <a href="{{route('customer.account')}}" class="block py-4 {{request()->fullUrl() == route('customer.account') ?'text-price' :''}}">
                <div class="inline-block w-6">
                    <svg class="inline-block" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
                        <path id="user" fill="currentColor" d="M11,2A10,10,0,1,0,21,12,10.01,10.01,0,0,0,11,2ZM4.955,19.006a7.532,7.532,0,0,1,5.852-2.941c.035,0,.069.01.1.01h.034c.033,0,.062-.009.095-.01a7.522,7.522,0,0,1,5.9,3.025,9.227,9.227,0,0,1-11.989-.084Zm5.962-3.688c-.037,0-.072.006-.109.007a3.311,3.311,0,1,1,.235,0C11,15.325,10.96,15.318,10.917,15.318Zm6.575,3.275a8.271,8.271,0,0,0-4.607-3.034,4.065,4.065,0,1,0-3.918,0,8.283,8.283,0,0,0-4.556,2.95,9.266,9.266,0,1,1,13.081.087Z" transform="translate(-1 -2)"/>
                    </svg>
                </div>
                <p class="inline-block ml-4 font-normal text-base">
                    {{__('site.profile')}}
                </p>
            </a>
        </li>
        <li>
            <a href="{{route('customer.booking')}}" class="block py-4 {{request()->fullUrl() == route('customer.booking') ?'text-price' :''}}">
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
                <p class="inline-block ml-4 font-normal text-base">
                    {{__('site.my_reservations')}}
                </p>
            </a>
        </li>
        <li>
            <a href="{{route('customer.favorite')}}" class="block py-4 {{request()->fullUrl() == route('customer.favorite') ?'text-price' :''}}">
                <div class="inline-block w-6">
                    <svg class="inline-block" xmlns="http://www.w3.org/2000/svg" width="24.13" height="21.2" viewBox="0 0 24.13 21.2">
                        <path id="Icon_feather-heart" data-name="Icon feather-heart" d="M23.485,6.265a6.033,6.033,0,0,0-8.535,0L13.788,7.428,12.625,6.265A6.035,6.035,0,1,0,4.091,14.8l1.163,1.163L13.788,24.5l8.535-8.535L23.485,14.8a6.033,6.033,0,0,0,0-8.535Z" transform="translate(-1.723 -3.897)" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2"/>
                    </svg>
                </div>
                <p class="inline-block ml-4 font-normal text-base">
                    {{__('site.my_favorate')}}
                </p>
            </a>
        </li>
        <li>
            <a class="block py-4">
                <div class="inline-block w-6">
                    <svg class="inline-block" xmlns="http://www.w3.org/2000/svg" width="22.86" height="21.2" viewBox="0 0 22.86 21.2">
                        <g id="wallet" transform="translate(0.6 0.6)">
                            <path id="Path_4487" data-name="Path 4487" d="M20.027,12.1H15.445a3.046,3.046,0,1,1,0-6.092h4.581" transform="translate(1.633 0.792)" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2" fill-rule="evenodd"/>
                            <line id="Line_698" data-name="Line 698" x1="0.353" transform="translate(17.244 9.781)" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2"/>
                            <path id="Path_4488" data-name="Path 4488" d="M5.939,0h9.782A5.939,5.939,0,0,1,21.66,5.939v8.122A5.939,5.939,0,0,1,15.721,20H5.939A5.939,5.939,0,0,1,0,14.061V5.939A5.939,5.939,0,0,1,5.939,0Z" transform="translate(0 0)" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2" fill-rule="evenodd"/>
                            <line id="Line_699" data-name="Line 699" x2="6.11" transform="translate(5.133 5.136)" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2"/>
                        </g>
                    </svg>
                </div>
                <p class="inline-block ml-4 font-normal text-base">
                    {{__('site.wallet_balance')}}
                </p>
            </a>
        </li>
        <li>
            <a class="block py-4 ">
                <div class="inline-block w-6">
                    <svg class="inline-block" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
                        <path id="chat" d="M10.007,0A10,10,0,0,1,10,20a9.924,9.924,0,0,1-2.148-.235A.749.749,0,1,1,8.172,18.3,8.5,8.5,0,1,0,10.006,1.5h0A8.5,8.5,0,0,0,2.46,13.931h0l.192.375a1.963,1.963,0,0,1,.135,1.483,17.118,17.118,0,0,0-.53,1.632C2.818,17.252,3.5,17,4,16.822H4l.2-.073a.748.748,0,0,1,.509,1.408h0l-.2.073a23.755,23.755,0,0,1-2.441.791.773.773,0,0,1-.178.019A1.207,1.207,0,0,1,1,18.7a1.311,1.311,0,0,1-.336-1,.765.765,0,0,1,.019-.147,17.515,17.515,0,0,1,.69-2.246.47.47,0,0,0-.049-.309h0l-.193-.374A10,10,0,0,1,10,0h0ZM10,8.809A1.189,1.189,0,1,1,8.807,10,1.19,1.19,0,0,1,10,8.809Zm4.4,0A1.189,1.189,0,1,1,13.21,10,1.19,1.19,0,0,1,14.4,8.809Zm-8.807,0A1.189,1.189,0,1,1,4.4,10,1.19,1.19,0,0,1,5.592,8.81Z" fill="currentColor"/>
                    </svg>
                </div>
                <p class="inline-block ml-4 font-normal text-base">
                    {{__('site.messages')}}
                </p>
            </a>
        </li>
        <li>
            <a href="{{route('customer.notifications')}}" class="block py-4 {{request()->fullUrl() == route('customer.notifications') ?'text-price' :''}}">
                <div class="inline-block w-6">
                    <svg class="inline-block" xmlns="http://www.w3.org/2000/svg" width="17.997" height="19.86" viewBox="0 0 17.997 19.86">
                        <g id="Icon_feather-bell" data-name="Icon feather-bell" transform="translate(-3.9 -2.4)">
                            <path id="Path_4319" data-name="Path 4319" d="M18.5,8.6a5.6,5.6,0,1,0-11.2,0c0,6.532-2.8,8.4-2.8,8.4H21.3s-2.8-1.866-2.8-8.4" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2"/>
                            <path id="Path_4320" data-name="Path 4320" d="M18.634,31.5a1.866,1.866,0,0,1-3.229,0" transform="translate(-4.121 -10.77)" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2"/>
                        </g>
                    </svg>
                </div>
                <p class="inline-block ml-4 font-normal text-base">
                    {{__('site.notifications')}}
                </p>
            </a>
        </li>
        <li>
            <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"  class="block py-2 bg-[#fdede9] text-center rounded-lg text-price mt-20">
                <div class="inline-block w-6">
                    <svg class="inline-block" xmlns="http://www.w3.org/2000/svg" width="17.501" height="17.5" viewBox="0 0 17.501 17.5">
                        <path id="logout" d="M20.53,12.53l-3,3a.75.75,0,0,1-1.06-1.061l1.72-1.72H9a.75.75,0,0,1,0-1.5H18.19l-1.72-1.72a.75.75,0,0,1,1.061-1.061l3,3a.75.75,0,0,1,0,1.061ZM9.75,20A.75.75,0,0,0,9,19.25H6A1.252,1.252,0,0,1,4.75,18V6A1.252,1.252,0,0,1,6,4.75H9a.75.75,0,0,0,0-1.5H6A2.752,2.752,0,0,0,3.25,6V18A2.752,2.752,0,0,0,6,20.75H9A.75.75,0,0,0,9.75,20Z" transform="translate(-3.25 -3.25)" fill="currentColor"/>
                    </svg>
                </div>
                <p class="inline-block ml-4 font-normal text-base">
                    {{__('site.logout')}}
                </p>
            </a>
            <form id="logout-form" action="{{ route('customer.logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
        </li>
    </ul>
</div>