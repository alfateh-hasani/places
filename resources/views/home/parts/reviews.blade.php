 


<section class="comments py-12">
  <div class="container">
    <div class="text-center">
      <div class="p-1 bg-black inline-block title text-left rounded-3xl">
        <div class="bg-white w-9 h-9 rounded-full float-left relative mr-3">
          <img src="{{ asset('assets/img/star-comment.svg') }}" class="absolute" />
        </div>
        <p class="float-left font-normal text-base sm:text-lg text-white pr-4 sm:pr-6 py-1.5 sm:py-1">
          @lang('site.related_reviews', ['rating' => $averageRating.'/5', 'users' => $totalUsers.' Dyafa'])
        </p>
        <div class="clear-both"></div>
      </div>
    </div>
    <h3 class="text-center font-semibold text-base sm:text-3xl text-black mt-4 mb-6 sm:my-8" >
      @lang('site.words_of_praise')
       
    </h3>
  </div>


  @if($topReviews1->isNotEmpty())
  <div class="relative comment-list top-list mb-4">
    <ul class="comment-slider-1">
 
      @foreach ($topReviews1 as $review)
          <li class="px-2">
              <a class="block bg-commentbg border border-commentborder py-6 px-8 rounded-xl">
                  <div>
                      <!-- Loop to show star rating based on actual rating value -->
                      @foreach (range(1, $review->rating) as $item)
                          <img src="{{ asset('assets/img/comment-star.svg') }}" class="inline-block" />
                      @endforeach
                  </div>
                  <p class="font-normal text-sm text-black mt-5 mb-6">
                      {{ $review->review_text }}
                  </p>
                  <h4 class="font-normal text-lg text-price">{{ $review->customer->first_name   }} {{ $review->customer->last_name   }}</h4>
                  <p class="font-normal text-sm text-gri mt-1">{{ $review?->apartment?->ml('name') ?? 'Anonymous' }}</p>
              </a>
          </li>
      @endforeach

     
    </ul>
  </div>

  @endif

  @if($topReviews2->isNotEmpty())
  <div class="relative comment-list bottom-list hidden sm:block">
      <ul class="comment-slider-2">
          @foreach ($topReviews2 as $review)
            <li class="px-2">
                <a class="block bg-commentbg border border-commentborder py-6 px-8 rounded-xl">
                    <div>
                        <!-- Loop to show star rating based on actual rating value -->
                        @foreach (range(1, $review->rating) as $item)
                            <img src="{{ asset('assets/img/comment-star.svg') }}" class="inline-block" />
                        @endforeach
                    </div>
                    <p class="font-normal text-sm text-black mt-5 mb-6">
                        {{ $review->review_text }}
                    </p>
                    <h4 class="font-normal text-lg text-price">{{ $review->customer->first_name ?? 'Anonymous' }}</h4>
                    <p class="font-normal text-sm text-gri mt-1">Customer</p>
                </a>
            </li>
        @endforeach
      </ul>
  </div>

@endif
</section>
