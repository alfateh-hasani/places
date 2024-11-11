@extends('layouts.master')

@section('content')



<section class="container py-10">
            <a>
                <img class="inline-block mr-4" src="assets/img/back.svg" />
                <span class="font-semibold text-2xl text-title translate-y-1 inline-block">Review And Payment</span>
            </a>
        </section>

        <section class="descriptions pb-24">
            <div class="container">
                <div class="xl:flex xl:flex-row">
                    <div class="xl:basis-8/12">
                        <div class="container">
                            <div class="float-left mr-5 mb-6 w-full">
                                <h1 class="float-left font-semibold text-2xl text-title">Studio With Master Bed Studio</h1>
                            </div>
                            <div class="float-left mr-5 mb-3 lg:mb-0">
                                <img class="inline-block mr-2 -translate-y-1" src="assets/img/location.svg" />
                                <p class="inline-block font-normal text-xl text-title">Riyadh, Al Qadisiyah Dist.</p>
                            </div>
                            <div class="float-left mr-5 mb-3 lg:mb-0">
                                <img class="inline-block mr-2 -translate-y-0.5" src="assets/img/star.svg" />
                                <p class="inline-block font-normal text-base text-reviews">10.0 (10) Review</p>
                            </div>
                            <div class="float-left mr-5 mb-3 lg:mb-0">
                                <img class="inline-block mr-2 -translate-y-0.5" src="assets/img/feature-3.svg" />
                                <p class="inline-block font-normal text-base text-reviews">Unit area 300m</p>
                            </div>
                            <div class="clear-both"></div>
                            <form>
                                <h1 class="font-semibold text-2xl text-title border-t border-blackopacity pt-8 mt-8">Payment</h1>
                                <ul class="pb-8 border-b border-blackopacity">
                                    <li>
                                        <input type="radio" name="payment" id="check1" class="hidden" />
                                        <label for="check1" class="cursor-pointer block border border-filterborder py-3 px-4 rounded-lg mb-2 hover:bg-filterborder ease-in-out duration-300">
                                            <img class="inline-block" src="assets/img/payment-1.png" />
                                            <p class="inline-block ml-4 font-normal text-sm text-title">Pay In 4. No Interest, No Fees.</p>
                                            <div class="w-5 h-5 border rounded-full border-filterborder float-right mt-1 relative">
                                                <div class="w-3 h-3 rounded-full bg-price absolute opacity-0"></div>
                                            </div>
                                            <div class="clear-both"></div>
                                        </label>
                                    </li>
                                    <li>
                                        <input type="radio" name="payment" id="check2" class="hidden" />
                                        <label for="check2" class="cursor-pointer block border border-filterborder py-3 px-4 rounded-lg mb-2 hover:bg-filterborder ease-in-out duration-300">
                                            <img class="inline-block" src="assets/img/payment-2.png" />
                                            <p class="inline-block ml-4 font-normal text-sm text-title">Pay In 4. No Interest, No Fees.</p>
                                            <div class="w-5 h-5 border rounded-full border-filterborder float-right mt-1 relative">
                                                <div class="w-3 h-3 rounded-full bg-price absolute opacity-0"></div>
                                            </div>
                                            <div class="clear-both"></div>
                                        </label>
                                    </li>
                                    <li>
                                        <input type="radio" name="payment" id="check3" class="hidden" />
                                        <label for="check3" class="cursor-pointer block border border-filterborder py-3 px-4 rounded-lg mb-2 hover:bg-filterborder ease-in-out duration-300">
                                            <img class="inline-block" src="assets/img/payment-3.png" />
                                            <p class="inline-block ml-4 font-normal text-sm text-title">Pay In 4. No Interest, No Fees.</p>
                                            <div class="w-5 h-5 border rounded-full border-filterborder float-right mt-1 relative">
                                                <div class="w-3 h-3 rounded-full bg-price absolute opacity-0"></div>
                                            </div>
                                            <div class="clear-both"></div>
                                        </label>
                                    </li>
                                </ul>
                            </form>
                        </div>
                        <div class="py-2 xl:py-7 detail-description border-b border-blackopacity mb-8">
                            <h5 class="font-semibold text-xl text-filterhover mb-6">Terms & Policies</h5>
                            <ul>
                                <li class="inline-block w-4/12 font-semibold text-base text-title mb-2"><img class="inline-block mr-2" src="assets/img/feature-ok.svg" /> Free Cancellation For 48 Hours</li>
                                <li class="inline-block w-4/12 font-semibold text-base text-title mb-2"><img class="inline-block mr-2" src="assets/img/feature-ok.svg" /> Free Cancellation For 48 Hours</li>
                                <li class="inline-block w-4/12 font-semibold text-base text-title mb-2"><img class="inline-block mr-2" src="assets/img/feature-ok.svg" /> Dive Right In</li>
                                <li class="inline-block w-4/12 font-semibold text-base text-title mb-2"><img class="inline-block mr-2" src="assets/img/feature-ok.svg" /> Dive Right In</li>
                            </ul>
                            <h6 class="mt-8">Cancellation Policy</h6>
                            <p class="font-light text-base text-gri mt-3 mb-2 ease-in-out duration-900 max-h-[72px] overflow-hidden">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s</p>
                            <button class="showmore font-normal text-sm text-blue underline">Read More</button>
                            <h6 class="mt-8">Cancellation Policy</h6>
                            <p class="font-light text-base text-gri mt-3 mb-2 ease-in-out duration-900 max-h-[72px] overflow-hidden">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only</p>
                        </div>
                    </div>
                    <div class="hidden xl:block xl:basis-4/12 xl:pl-5">
                        <div class="border border border-filterborder rounded-xl px-5 py-6">
                            <p class="font-normal text-base text-reviews"><span class="font-bold text-2xl text-black translate-y-0.5 inline-block">120</span> Saudi Riyal / Night</p>
                            <form class="mb-4">
                                checkin checkout
                                checkin checkout
                                <button class="bg-price rounded-lg h-12 w-full font-semibold text-white">Check Out</button>
                            </form>
                            <p class="font-normal text-sm text-reviews text-center mb-6">You won't be charged yet</p>
                            <ul>
                                <li class="mb-4 font-semibold text-sm text-title">
                                    <span>One Night × 350 Saudi Riyal</span>
                                    <span class="float-right">350 Saudi Riyal</span>
                                    <div class="clear-both"></div>
                                </li>
                                <li class="mb-4 font-semibold text-sm text-title">
                                    <span>Services Fees</span>
                                    <span class="float-right">+38.5 Saudi Riyal</span>
                                    <div class="clear-both"></div>
                                </li>
                            </ul>
                            <p class="py-4 px-3 bg-filterbackground border border-filterborder rounded-lg font-semibold text-sm text-title mb-4">Total Price <span class="float-right">388.50 Saudi Riyal</span></p>
                            <ul>
                                <li>
                                    <a class="block border border-filterborder py-3 px-4 rounded-lg mb-2 hover:bg-filterborder ease-in-out duration-300">
                                        <img class="inline-block" src="assets/img/payment-1.png" />
                                        <p class="inline-block ml-4 font-normal text-sm text-title">Pay In 4. No Interest, No Fees.</p>
                                    </a>
                                </li>
                                <li>
                                    <a class="block border border-filterborder py-3 px-4 rounded-lg mb-2 hover:bg-filterborder ease-in-out duration-300">
                                        <img class="inline-block" src="assets/img/payment-2.png" />
                                        <p class="inline-block ml-4 font-normal text-sm text-title">Pay In 4. No Interest, No Fees.</p>
                                    </a>
                                </li>
                                <li>
                                    <a class="block border border-filterborder py-3 px-4 rounded-lg mb-2 hover:bg-filterborder ease-in-out duration-300">
                                        <img class="inline-block" src="assets/img/payment-3.png" />
                                        <p class="inline-block ml-4 font-normal text-sm text-title">Pay In 4. No Interest, No Fees.</p>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>









@endsection