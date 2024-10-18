<section class="comments py-12">
    <div class="container-fluid">
        <div class="text-center">
            <div class="p-1 bg-black inline-block title text-left rtl:text-right rounded-3xl">
                <div class="bg-white w-9 h-9 rounded-full float-left rtl:float-right relative mr-3 rtl:mr-0 rtl:ml-3">
                    <img src="{{ asset('assets/img/star-comment.svg') }}" class="absolute" alt="Star" />
                </div>
                <p class="float-left rtl:float-right font-normal text-base sm:text-lg text-white pr-4 sm:pr-6 rtl:pl-4 sm:rtl:pl-6 py-1.5 sm:py-1">
                    @lang('site.related_reviews', ['rating' => '4/5', 'users' => '1 Dyafa'])
                </p>
                <div class="clear-both"></div>
            </div>
        </div>

        <h3 class="text-center font-semibold text-base sm:text-3xl text-black mt-4 mb-6 sm:my-8">
            @lang('site.words_of_praise')
            <br />@lang('site.about_our_presence')
        </h3>

        <div class="relative comment-list top-list mb-4">
            <ul class="w-[5000vw]">
                @for ($i = 0; $i < 8; $i++)
                    <li class="float-left w-[400px] mx-2 rtl:float-right">
                        <a class="block bg-commentbg border border-commentborder py-6 px-8 rounded-xl">
                            <div>
                                @for ($j = 0; $j < 5; $j++)
                                    <img src="{{ asset('assets/img/comment-star.svg') }}" class="inline-block" alt="Star" />
                                @endfor
                            </div>
                            <p class="font-normal text-sm text-black mt-5 mb-6">
                                @lang('site.comment_content')
                            </p>
                            <h4 class="font-normal text-lg text-price">@lang('site.comment_author', ['name' => 'Hazem Anwar'])</h4>
                            <p class="font-normal text-sm text-gri mt-1">@lang('site.comment_position')</p>
                        </a>
                    </li>
                @endfor
                <li class="clear-both"></li>
            </ul>
        </div>

         
    </div>
</section>
