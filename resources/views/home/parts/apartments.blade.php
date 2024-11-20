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
                <button id="load-more" 
                        data-next-page="{{ $apartments->currentPage() + 1 }}" 
                        class="inline-block font-normal font-semibold text-base text-title border border-title px-6 py-2 mt-4 sm:mt-10 rounded-3xl hover:bg-black hover:text-white ease-in-out duration-300">
                    @lang('site.explor_more')
                </button>
            </div>
        @endif
        
    </div>
</section>

@push('scripts')
    <script>
        $(document).ready(function() {
            $('#load-more').click(function() {
                var nextPage = $(this).data('next-page');
                var url = '{{ route('home') }}?page=' + nextPage;
                $.get(url, function(data) {
                    $('#apartments-container').append(data);
                    $('#load-more').data('next-page', nextPage + 1);
                    if (data.trim() == '') {
                        $('#load-more').remove();
                    }
                });
            });
        });
    </script>
@endpush