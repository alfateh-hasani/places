<div class="lg:grid lg:grid-cols-3 lg:gap-6 w-full mx-0">
    <div class="bg-white p-6 rounded-2xl menu mb-2 lg:mb-0">
        <div class="mb-4">
            <div class="float-left rtl:float-right w-10 h-10 rounded-lg bg-commentborder relative">
                <svg class="absolute" xmlns="http://www.w3.org/2000/svg" width="21" height="21" viewBox="0 0 21 21">
                    <g id="event" transform="translate(-1.5 -1.5)">
                        <rect id="Rectangle_19429" data-name="Rectangle 19429" width="20" height="18" rx="4" transform="translate(2 4)" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="1"/>
                        <path id="Path_4484" data-name="Path 4484" d="M2,8A4,4,0,0,1,6,4H18a4,4,0,0,1,4,4V9H2Z" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="1"/>
                        <path id="Path_4485" data-name="Path 4485" d="M6,2V6M18,2V6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="1"/>
                        <path id="Path_4486" data-name="Path 4486" d="M12,12l1.458,1.994,2.347.77-1.446,2-.007,2.47L12,18.48l-2.351.756-.007-2.47-1.446-2,2.347-.77Z" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="1"/>
                    </g>
                </svg>
            </div>
            <p class="float-right rtl:float-left font-normal text-2xl py-1">
                {{$customer->bookings->count()}}
            </p>
            <div class="clear-both"></div>
        </div>
        <p class="font-normal text-lg">
            {{__('customer.my_reservations_count')}}
        </p>
    </div>
    <div class="bg-white p-6 rounded-2xl menu mb-2 lg:mb-0">
        <div class="mb-4">
            <div class="float-left rtl:float-right w-10 h-10 rounded-lg bg-commentborder relative">
                <svg class="absolute" xmlns="http://www.w3.org/2000/svg" width="22.86" height="21.2" viewBox="0 0 22.86 21.2">
                    <g id="wallet" transform="translate(0.6 0.6)">
                        <path id="Path_4487" data-name="Path 4487" d="M20.027,12.1H15.445a3.046,3.046,0,1,1,0-6.092h4.581" transform="translate(1.633 0.792)" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2" fill-rule="evenodd"/>
                        <line id="Line_698" data-name="Line 698" x1="0.353" transform="translate(17.244 9.781)" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2"/>
                        <path id="Path_4488" data-name="Path 4488" d="M5.939,0h9.782A5.939,5.939,0,0,1,21.66,5.939v8.122A5.939,5.939,0,0,1,15.721,20H5.939A5.939,5.939,0,0,1,0,14.061V5.939A5.939,5.939,0,0,1,5.939,0Z" transform="translate(0 0)" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2" fill-rule="evenodd"/>
                        <line id="Line_699" data-name="Line 699" x2="6.11" transform="translate(5.133 5.136)" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2"/>
                    </g>
                </svg>
            </div>
            <p class="float-right rtl:float-left font-normal text-2xl py-1">
                {{$customer->wallet_balance??0}}   SAR
            </p>
            <div class="clear-both"></div>
        </div>
        <p class="font-normal text-lg">
            {{__('customer.my_balance')}}
        </p>
    </div>
    <div class="bg-white p-6 rounded-2xl menu mb-2 lg:mb-0">
        <div class="mb-4">
            <div class="float-left rtl:float-right w-10 h-10 rounded-lg bg-commentborder relative">
                <svg class="absolute" xmlns="http://www.w3.org/2000/svg" width="24.13" height="21.2" viewBox="0 0 24.13 21.2">
                    <path id="Icon_feather-heart" data-name="Icon feather-heart" d="M23.485,6.265a6.033,6.033,0,0,0-8.535,0L13.788,7.428,12.625,6.265A6.035,6.035,0,1,0,4.091,14.8l1.163,1.163L13.788,24.5l8.535-8.535L23.485,14.8a6.033,6.033,0,0,0,0-8.535Z" transform="translate(-1.723 -3.897)" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2"/>
                </svg>
            </div>
            <p class="float-right rtl:float-left font-normal text-2xl py-1">
                {{$customer->favoriteApartments->count()}}
            </p>
            <div class="clear-both"></div>
        </div>
        <p class="font-normal text-lg">
            {{__('customer.my_favorate_count')}}
        </p>
    </div>
</div>