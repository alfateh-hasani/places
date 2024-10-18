<section class="app pt-16 lg:pt-24 relative">
    <img
        src="{{ asset('assets/img/app-bg.png') }}"
        class="absolute bottom-0 left-0 right-0"
        alt="App Background"
    />
    <div class="container">
        <div class="lg:grid lg:grid-cols-2 max-w-full">
            <div>
                <h3 class="font-semibold text-4xl lg:text-6xl text-white lg:pt-14">
                    @lang('site.download_app_title')<br />
                    @lang('site.real_estate_app')
                </h3>
                <p class="font-normal text-base text-white my-6">
                    @lang('site.app_description')
                </p>
                <div>
                    <a>
                        <img
                            src="{{ asset('assets/img/apple.svg') }}"
                            class="float-left rtl:float-right mr-3 rtl:ml-3 w-40 lg:w-auto"
                            alt="Download on Apple Store"
                        />
                    </a>
                    <a>
                        <img
                            src="{{ asset('assets/img/android.svg') }}"
                            class="float-left rtl:float-right mr-3 rtl:ml-3 w-40 lg:w-auto"
                            alt="Download on Google Play"
                        />
                    </a>
                    <div class="clear-both"></div>
                </div>
            </div>
            <div class="text-right rtl:text-left mt-10 lg:mt-0">
                <img
                    src="{{ asset('assets/img/app.png') }}"
                    class="inline"
                    alt="App Image"
                />
            </div>
        </div>
    </div>
</section>
