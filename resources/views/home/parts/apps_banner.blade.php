<section class="app   relative">
    <img
        src="{{ asset('assets/images/1-01.png') }}"
        class="absolute bottom-0 left-0 right-0"
        alt=""
        loading="lazy"
    />
    <div class="container">
        <div class="lg:grid lg:grid-cols-2 max-w-full">
            <div>
                 <img src="{{ asset('assets/images/test-img.png') }}" alt="" loading="lazy" style="
                 margin-top: 80px;
                margin-bottom: 80px;
                width: 70%;">
                <div class="flex flex-wrap items-center gap-3">
                    <a href="https://apps.apple.com/us/app/dyafa-%D8%B6%D9%8A%D8%A7%D9%81%D8%A9/id6711337244">
                        <img
                            src="{{ asset('assets/img/apple.svg') }}"
                            class="w-32 sm:w-40 lg:w-auto"
                            alt="{{ __('site.download_apple') }}"
                            loading="lazy"
                        />
                    </a>
                    <a  href="https://play.google.com/store/apps/details?id=co.Placess.app">
                        <img
                            src="{{ asset('assets/img/android.svg') }}"
                            class="w-32 sm:w-40 lg:w-auto"
                            alt="{{ __('site.download_google') }}"
                            loading="lazy"
                        />
                    </a>
                </div>
            </div>
            <div class="text-right rtl:text-left mt-10 lg:mt-0">
                <img
                    src="{{ asset('assets/images/appsback2.png') }}?v=1"
                    class="inline"
                    alt=""
                    loading="lazy"
                />
            </div>
        </div>
    </div>
</section>
