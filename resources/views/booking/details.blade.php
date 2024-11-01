@extends('layouts.master')

@section('content')


<section class="profile py-5 lg:py-16 bg-[#eff3f6] min-h-screen lg:min-h-min">
    <div class="container">
        <div>
            <div class="inline-block w-8 h-8 rounded-full bg-filteritem relative">
                <svg class="absolute top-2/4 left-2/4 -translate-y-2/4 -translate-x-2/4" xmlns="http://www.w3.org/2000/svg" width="10.939" height="10.748" viewBox="0 0 10.939 10.748">
                  <path id="Path_843" data-name="Path 843" d="M5.843,11.343H16.116m-5.7,4.844L6.178,11.95a.856.856,0,0,1,0-1.211L10.416,6.5" transform="translate(-5.177 -5.97)" fill="none" stroke="#000" stroke-width="1.5"/>
                </svg>
            </div>
            <p class="inline-block font-semibold text-2xl ml-4 -translate-y-2">#00256444</p>
        </div>
        <div class="py-8 px-6 bg-white rounded-2xl mt-6">
            <div class="border-b border-border pb-8 mb-8">
                <p class="font-semibold text-lg float-left py-2.5">Studio With Master Bed</p>
                <div class="float-right">
                    <a class="py-3 px-4 inline-block rounded-md bg-gri text-white ml-2">
                        <svg class="inline-block" id="fi_2891455" enable-background="new 0 0 24 24" height="20" viewBox="0 0 24 24" width="20" xmlns="http://www.w3.org/2000/svg" fill="currentColor">
                            <path d="m21.5 18h-3c-.276 0-.5-.224-.5-.5s.224-.5.5-.5h3c.827 0 1.5-.673 1.5-1.5v-7c0-.827-.673-1.5-1.5-1.5h-19c-.827 0-1.5.673-1.5 1.5v7c0 .827.673 1.5 1.5 1.5h3c.276 0 .5.224.5.5s-.224.5-.5.5h-3c-1.379 0-2.5-1.122-2.5-2.5v-7c0-1.378 1.121-2.5 2.5-2.5h19c1.379 0 2.5 1.122 2.5 2.5v7c0 1.378-1.121 2.5-2.5 2.5z"></path>
                            <path d="m14.5 21h-6c-.276 0-.5-.224-.5-.5s.224-.5.5-.5h6c.276 0 .5.224.5.5s-.224.5-.5.5z"></path>
                            <path d="m14.5 19h-6c-.276 0-.5-.224-.5-.5s.224-.5.5-.5h6c.276 0 .5.224.5.5s-.224.5-.5.5z"></path>
                            <path d="m10.5 17h-2c-.276 0-.5-.224-.5-.5s.224-.5.5-.5h2c.276 0 .5.224.5.5s-.224.5-.5.5z"></path>
                            <path d="m18.5 7c-.276 0-.5-.224-.5-.5v-4c0-.827-.673-1.5-1.5-1.5h-9c-.827 0-1.5.673-1.5 1.5v4c0 .276-.224.5-.5.5s-.5-.224-.5-.5v-4c0-1.378 1.121-2.5 2.5-2.5h9c1.379 0 2.5 1.122 2.5 2.5v4c0 .276-.224.5-.5.5z"></path>
                            <path d="m16.5 24h-9c-1.379 0-2.5-1.122-2.5-2.5v-8c0-.276.224-.5.5-.5h13c.276 0 .5.224.5.5v8c0 1.378-1.121 2.5-2.5 2.5zm-10.5-10v7.5c0 .827.673 1.5 1.5 1.5h9c.827 0 1.5-.673 1.5-1.5v-7.5z"></path>
                        </svg>
                        <span class="inline-block ml-2 text-sm">Print Reservations</span>
                     </a>
                     <a class="py-3 px-4 inline-block rounded-md bg-[#fdeee9] text-price ml-2">
                         <svg class="inline-block" fill="currentColor" height="20" viewBox="0 0 329.26933 329" width="20" xmlns="http://www.w3.org/2000/svg" id="fi_1828778"><path d="m194.800781 164.769531 128.210938-128.214843c8.34375-8.339844 8.34375-21.824219 0-30.164063-8.339844-8.339844-21.824219-8.339844-30.164063 0l-128.214844 128.214844-128.210937-128.214844c-8.34375-8.339844-21.824219-8.339844-30.164063 0-8.34375 8.339844-8.34375 21.824219 0 30.164063l128.210938 128.214843-128.210938 128.214844c-8.34375 8.339844-8.34375 21.824219 0 30.164063 4.15625 4.160156 9.621094 6.25 15.082032 6.25 5.460937 0 10.921875-2.089844 15.082031-6.25l128.210937-128.214844 128.214844 128.214844c4.160156 4.160156 9.621094 6.25 15.082032 6.25 5.460937 0 10.921874-2.089844 15.082031-6.25 8.34375-8.339844 8.34375-21.824219 0-30.164063zm0 0"></path></svg>
                         <span class="inline-block ml-2 text-sm">Cancel Reservations</span>
                     </a>
                     <a class="py-2.5 px-4 inline-block rounded-md ml-2 border border-price text-center text-price">
                         <svg class="inline-block" fill="currentColor" xmlns="http://www.w3.org/2000/svg" id="fi_5728913" data-name="Layer 1" viewBox="0 0 512 512" width="20" height="20"><path d="M489.417,279v-1.182c0-62.1-24.349-120.646-68.56-164.857S318.1,44.4,256,44.4,135.354,68.749,91.143,112.96s-68.56,102.758-68.56,164.856V279A27.578,27.578,0,0,0,0,306.081V397.1a27.571,27.571,0,0,0,27.538,27.539H44.556v3.934A39.075,39.075,0,0,0,83.586,467.6H98.705a23.94,23.94,0,0,0,23.912-23.913v-184.2a23.94,23.94,0,0,0-23.912-23.913H83.586a39.074,39.074,0,0,0-39.03,39.03v3.935H38.583v-.727C38.583,157.933,136.116,60.4,256,60.4s217.417,97.533,217.417,217.416v.727h-5.973v-3.935a39.074,39.074,0,0,0-39.03-39.03H413.3a23.94,23.94,0,0,0-23.912,23.913v184.2A23.94,23.94,0,0,0,413.3,467.6h15.119a39.075,39.075,0,0,0,39.03-39.031v-3.934h17.018A27.571,27.571,0,0,0,512,397.1V306.081A27.578,27.578,0,0,0,489.417,279Zm-428.861-4.39a23.056,23.056,0,0,1,23.03-23.03H98.705a7.921,7.921,0,0,1,7.912,7.913v184.2a7.921,7.921,0,0,1-7.912,7.913H83.586a23.056,23.056,0,0,1-23.03-23.031Zm-16,134.027H27.538A11.552,11.552,0,0,1,16,397.1V306.081a11.551,11.551,0,0,1,11.538-11.538H44.556Zm406.888,19.934a23.056,23.056,0,0,1-23.03,23.031H413.3a7.921,7.921,0,0,1-7.912-7.913v-184.2a7.921,7.921,0,0,1,7.912-7.913h15.119a23.056,23.056,0,0,1,23.03,23.03ZM496,397.1a11.552,11.552,0,0,1-11.538,11.539H467.444V294.543h17.018A11.551,11.551,0,0,1,496,306.081Z"></path></svg>
                     </a>
                </div>
                <div class="clear-both"></div>
            </div>
            <div class="grid lg:grid-cols-5 gap-6 max-w-full">
                <div class="col-span-3">
                    <img src="assets/img/slider.png" class="w-full h-40 object-cover rounded-lg mb-4" />
                    <ul>
                        <li class="bg-feature border border-feature-border mb-4 rounded-lg p-4"><p class="text-gri float-left">Reservations Number :</p><p class="float-right">#00256444</p><div class="clear-both"></div></li>
                        <li class="bg-feature border border-feature-border mb-4 rounded-lg p-4"><p class="text-gri float-left">Reservations Staus :</p><p class="float-right text-[#10C13F]">Active</p><div class="clear-both"></div></li>
                        <li class="bg-feature border border-feature-border mb-4 rounded-lg p-4"><p class="text-gri float-left">Reservations Date :</p><p class="float-right">3 Jan 2024 - 16:00</p><div class="clear-both"></div></li>
                        <li class="bg-feature border border-feature-border mb-4 rounded-lg p-4"><p class="text-gri float-left">Reservations End Date :</p><p class="float-right">14 Jan 2024 - 16:00</p><div class="clear-both"></div></li>
                    </ul>
                </div>
                <div class="bg-footer border border-feature-border rounded-lg py-5 col-span-2">
                    <div class="border-b border-feature-border pb-5 mb-5 px-5">
                        <p class="float-left font-semibold">Reservations Info</p>
                        <svg class="float-right text-price" xmlns="http://www.w3.org/2000/svg" width="21" height="21" viewBox="0 0 21 21">
                            <g id="event" transform="translate(-1.5 -1.5)">
                                <rect id="Rectangle_19429" data-name="Rectangle 19429" width="20" height="18" rx="4" transform="translate(2 4)" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="1"/>
                                <path id="Path_4484" data-name="Path 4484" d="M2,8A4,4,0,0,1,6,4H18a4,4,0,0,1,4,4V9H2Z" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="1"/>
                                <path id="Path_4485" data-name="Path 4485" d="M6,2V6M18,2V6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="1"/>
                                <path id="Path_4486" data-name="Path 4486" d="M12,12l1.458,1.994,2.347.77-1.446,2-.007,2.47L12,18.48l-2.351.756-.007-2.47-1.446-2,2.347-.77Z" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="1"/>
                            </g>
                        </svg>
                        <div class="clear-both"></div>
                    </div>
                    <div class="bg-feature border border-feature-border rounded-lg mx-5 px-4 py-3">
                        <p class="float-left text-xs">Chick In Time <span class="block font-semibold text-sm">04:00 PM</span></p>
                        <p class="float-right text-xs w-2/4 border-l border-feature-border text-right">Chick In Time <span class="block font-semibold text-sm">04:00 PM</span></p>
                        <div class="clear-both"></div>
                    </div>
                    <div class="bg-feature border border-feature-border rounded-lg mx-5 my-3 p-4">
                        <p class="float-left text-sm">Reservations Link :</p>
                        <a class="float-right text-sm underline decoration-solid">Go To Link</a>
                        <div class="clear-both"></div>
                    </div>
                    <div class="border border-price bg-[#fdeee9] rounded-lg mx-5 px-3 py-5 relative pin text-price">
                        <svg class="float-left" fill="currentColor" id="fi_16916738" height="32" viewBox="0 0 24 24" width="32" xmlns="http://www.w3.org/2000/svg">
                            <path d="m10.5 5.5c0 .27612-.22388.5-.5.5s-.5-.22388-.5-.5c0-.27618.22388-.5.5-.5s.5.22382.5.5zm2.5-.5c-.27612 0-.5.22382-.5.5 0 .27612.22388.5.5.5s.5-.22388.5-.5c0-.27618-.22388-.5-.5-.5zm-6 0c-.27612 0-.5.22382-.5.5 0 .27612.22388.5.5.5s.5-.22388.5-.5c0-.27618-.22388-.5-.5-.5zm3 3c-.27612 0-.5.22382-.5.5 0 .27612.22388.5.5.5s.5-.22388.5-.5c0-.27618-.22388-.5-.5-.5zm3 0c-.27612 0-.5.22382-.5.5 0 .27612.22388.5.5.5s.5-.22388.5-.5c0-.27618-.22388-.5-.5-.5zm-6 0c-.27612 0-.5.22382-.5.5 0 .27612.22388.5.5.5s.5-.22388.5-.5c0-.27618-.22388-.5-.5-.5zm3 3c-.27612 0-.5.22382-.5.5 0 .27612.22388.5.5.5s.5-.22388.5-.5c0-.27618-.22388-.5-.5-.5zm3 0c-.27612 0-.5.22382-.5.5 0 .27612.22388.5.5.5s.5-.22388.5-.5c0-.27618-.22388-.5-.5-.5zm-6 0c-.27612 0-.5.22382-.5.5 0 .27612.22388.5.5.5s.5-.22388.5-.5c0-.27618-.22388-.5-.5-.5zm13.75 5v2c0 .96484-.78516 1.75-1.75 1.75h-8.89307c-1.39453 0-2.60693-.98633-2.81982-2.29395-.13086-.80566.09424-1.62207.61768-2.2373.52393-.61523 1.2876-.96875 2.09521-.96875h9c.96484 0 1.75.78516 1.75 1.75zm-1.5 0c0-.1377-.1123-.25-.25-.25h-9c-.36719 0-.71436.16113-.95264.44043-.2417.28418-.34082.64844-.27979 1.02539.09619.58984.67188 1.03418 1.33936 1.03418h8.89307c.1377 0 .25-.1123.25-.25zm-9.25.5c-.27612 0-.5.22382-.5.5 0 .27612.22388.5.5.5s.5-.22388.5-.5c0-.27618-.22388-.5-.5-.5zm4 4.75h-8c-.68945 0-1.25-.56055-1.25-1.25v-16c0-.68945.56055-1.25 1.25-1.25h8c.68945 0 1.25.56055 1.25 1.25v9h1.5v-9c0-1.5166-1.2334-2.75-2.75-2.75h-8c-1.5166 0-2.75 1.2334-2.75 2.75v16c0 1.5166 1.2334 2.75 2.75 2.75h8c1.16302 0 2.15375-.72784 2.55518-1.75h-1.84424c-.20447.14587-.4411.25-.71094.25z"></path>
                        </svg>
                        <p class="absolute font-semibold text-black">Passcode</p>
                        <p class="float-right tracking-wider py-1">224658</p>
                        <div class="clear-both"></div>
                    </div>
                    <p class="font-semibold py-4 mx-5">Summary</p>
                    <p class="text-title mx-5">Night Price <span class="float-right font-semibold">600 SAR</span></p>
                    <div class="bg-feature border border-feature-border rounded-lg mx-5 mt-4 p-3">
                        <p>Total Price (6 Nights)</p>
                        <p class="font-semibold text-lg">2650 SAR</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


@endsection