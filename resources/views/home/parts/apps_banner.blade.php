<section class="app   relative">
    <img
        src="{{ asset('assets/images/1-01.webp') }}"
        width="1920"
        height="695"
        class="absolute bottom-0 left-0 right-0 w-full"
        alt=""
        loading="lazy"
    />
    <div class="container">
        <div class="lg:grid lg:grid-cols-2 max-w-full">
            <div>
                 <img src="{{ asset('assets/images/test-img.webp') }}"
                      srcset="{{ asset('assets/images/test-img-600.webp') }} 600w, {{ asset('assets/images/test-img.webp') }} 1185w"
                      sizes="(min-width: 1024px) 35vw, 70vw"
                      width="1185" height="444" alt="" loading="lazy" style="
                 margin-top: 80px;
                margin-bottom: 80px;
                width: 70%;">
                <div class="flex items-center">
                    <a href="https://apps.apple.com/us/app/dyafa-%D8%B6%D9%8A%D8%A7%D9%81%D8%A9/id6711337244">
                        <img
                            src="{{ asset('assets/img/apple.svg') }}"
                            class="mr-3 rtl:ml-3 w-40 lg:w-auto"
                            alt="{{ __('site.download_apple') }}"
                            loading="lazy" width="191" height="56" />
                    </a>
                    <a  href="https://play.google.com/store/apps/details?id=co.Placess.app">
                        <img
                            src="{{ asset('assets/img/android.svg') }}"
                            class="mr-3 rtl:ml-3 w-40 lg:w-auto"
                            alt="{{ __('site.download_google') }}"
                            loading="lazy" width="191" height="56" />
                    </a>
                </div>
            </div>
            <div class="text-right rtl:text-left mt-10 lg:mt-0">
                <img
                    src="{{ asset('assets/images/appsback2.webp') }}"
                    srcset="{{ asset('assets/images/appsback2-600.webp') }} 600w, {{ asset('assets/images/appsback2.webp') }} 1200w"
                    sizes="(min-width: 1024px) 50vw, 100vw"
                    width="1200"
                    height="970"
                    class="inline"
                    alt=""
                    loading="lazy"
                />
            </div>
        </div>
    </div>
</section>
