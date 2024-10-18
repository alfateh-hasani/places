<section class="list pt-2 sm:pt-10 pb-2 sm:pb-20">
    <div class="container">
        <h2 class="text-center sm:text-left rtl:sm:text-right font-semibold text-base sm:text-2xl text-black mb-4 sm:mb-10">
                 @lang('site.explor_title')
        </h2>


        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 max-w-full">
            @foreach($apartments as $apartment)
                @include('apartment.card', ['apartment' => $apartment])
            @endforeach
        </div>

        <div class="text-center">
            <a href="{{ route('apartments.index') }}"
               class="inline-block font-normal font-semibold text-base text-title border border-title px-6 py-2 mt-4 sm:mt-10 rounded-3xl hover:bg-black hover:text-white ease-in-out duration-300">
               @lang('site.explor_more')
            </a>
        </div>
    </div>
</section>
