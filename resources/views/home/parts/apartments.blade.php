<section class="list pt-2 sm:pt-10 pb-2 sm:pb-20">
    <div class="container">
        <h2 class="text-center sm:text-left rtl:sm:text-right font-semibold text-base sm:text-2xl text-black mb-4 sm:mb-10">
                 @lang('site.explor_title')
        </h2>

        <div id="apartments-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 max-w-full">
            @foreach($apartments as $apartment)
                @include('apartment.card', ['apartment' => $apartment])
            @endforeach
        </div>
        
        @if($apartments->hasMorePages())
            <div class="text-center">
                <button id="show-more" 
                        data-next-page="{{ $apartments->currentPage() + 1 }}" 
                        class="inline-block font-normal font-semibold text-base text-title border border-title px-6 py-2 mt-4 sm:mt-10 rounded-3xl hover:bg-black hover:text-white ease-in-out duration-300">
                    @lang('site.explor_more')
                </button>
            </div>
        @endif
        
    </div>

    <div style="display:none;" id="list-links">
        {!! $apartments->links() !!}
        </div>
</section>

@push('js')

<script src="{{ asset('assets/js/infinite-scroll.pkgd.min.js')}}"></script>
<script>
    // $(document).ready(function () {
    //     // Initialize Infinite Scroll
    //     $('#apartments-container').infiniteScroll({
    //         path: '#list-links a[aria-label="pagination.next"]',
    //         append: '.apartment-card',
    //         history: false,
    //     }).on('append.infiniteScroll', function (event, response, path, items) {
    //         // Reinitialize sliders if present in the new content
    //         $(items).find('.slider').each(function () {
    //             // Destroy existing Slick instance if initialized
    //             if ($(this).hasClass('slick-initialized')) {
    //                 $(this).slick('unslick');
    //             }
    //             // Initialize Slick
    //             $(this).slick({
    //                 dots: true,
    //                 @if(config('app.locale') == 'ar')
    //                 rtl: true,
    //                 @endif
    //             });
    //         });
    //     });
    // });
</script>
<script>
    $(document).ready(function () {
        var infScroll = $('#apartments-container').infiniteScroll({
            path: '#list-links a[aria-label="pagination.next"]',
            append: '.apartment-card',
            history: false,
            scrollThreshold: false, // Disable automatic loading
        }).on('append.infiniteScroll', function (event, response, path, items) {
            // Reinitialize sliders if present in the new content
            $(items).find('.slider').each(function () {
                // Destroy existing Slick instance if initialized
                if ($(this).hasClass('slick-initialized')) {
                    $(this).slick('unslick');
                }
                // Initialize Slick
                $(this).slick({
                    dots: true,
                    @if(config('app.locale') == 'ar')
                    rtl: true,
                    @endif
                });
            });
        });

        // Manual trigger on button click
        $('#show-more').on('click', function () {
            infScroll.infiniteScroll('loadNextPage');
        });
    });

    $('#apartments-container').on('last.infiniteScroll', function () {
        $('#show-more').hide();
    });
</script>
 
@endpush