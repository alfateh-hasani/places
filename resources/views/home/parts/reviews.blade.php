 


@php
    // Slick autoplay only scrolls when there are more slides than slidesToShow
    // (up to 6 on wide screens, per main.js). With only a few reviews the strip
    // would sit static/centered, so repeat the reviews to guarantee scrolling.
    $repeatReviews = function ($reviews, $min = 9) {
        if ($reviews->isEmpty()) {
            return $reviews;
        }
        $out = collect();
        while ($out->count() < $min) {
            $out = $out->merge($reviews);
        }
        return $out->values();
    };
    $slides1 = $repeatReviews($topReviews1);
    $slides2 = $repeatReviews($topReviews2);
@endphp

<section class="comments py-12">
  <div class="container">
    <div class="text-center">
      <div class="px-4 py-3 sm:p-1 sm:ps-6 bg-black inline-flex flex-col sm:flex-row-reverse items-center gap-2 sm:gap-3 title rounded-3xl sm:rounded-3xl max-w-full text-center sm:text-start">
        <div class="bg-white w-9 h-9 rounded-full relative flex-shrink-0">
          <img src="{{ asset('assets/img/star-comment.svg') }}" class="absolute" alt="" loading="lazy" />
        </div>
        <p class="font-normal text-sm sm:text-lg text-white py-1.5 sm:py-1">
          @lang('site.related_reviews', ['rating' => $averageRating.'/5', 'users' => $totalUsers.' Dyafa'])
        </p>
      </div>
    </div>
    <h2 class="text-center font-semibold text-base sm:text-3xl text-black mt-4 mb-6 sm:my-8" >
      @lang('site.words_of_praise')

    </h2>
  </div>


  @if($topReviews1->isNotEmpty())
  <div class="relative comment-list top-list mb-4">
    <div class="comment-slider comment-slider-1">
 
      @foreach ($slides1 as $review)
          <div class="px-2">
              <div class="comment-card block bg-commentbg border border-commentborder py-6 px-8 rounded-xl">
                  <div>
                      <!-- Loop to show star rating based on actual rating value -->
                      @foreach (range(1, $review->rating) as $item)
                          <img src="{{ asset('assets/img/comment-star.svg') }}" class="inline-block" width="20" height="19" alt="" loading="lazy" />
                      @endforeach
                  </div>
                  <p class="font-normal text-sm text-black mt-5 mb-6">
                      {{ $review->review_text }}
                  </p>
                  <h3 class="font-normal text-lg text-price">{{ $review->customer->first_name   }} {{ $review->customer->last_name   }}</h3>
                  <p class="font-normal text-sm text-gri mt-1">{{ $review?->apartment?->ml('name') ?? __('site.anonymous') }}</p>
              </div>
          </div>
      @endforeach

     
    </div>
  </div>

  @endif

  @if($topReviews2->isNotEmpty())
  <div class="relative comment-list bottom-list hidden sm:block">
      <div class="comment-slider comment-slider-2">
          @foreach ($slides2 as $review)
            <div class="px-2">
                <div class="comment-card block bg-commentbg border border-commentborder py-6 px-8 rounded-xl">
                    <div>
                        <!-- Loop to show star rating based on actual rating value -->
                        @foreach (range(1, $review->rating) as $item)
                            <img src="{{ asset('assets/img/comment-star.svg') }}" class="inline-block" width="20" height="19" alt="" loading="lazy" />
                        @endforeach
                    </div>
                    <p class="font-normal text-sm text-black mt-5 mb-6">
                        {{ $review->review_text }}
                    </p>
                    <h3 class="font-normal text-lg text-price">{{ $review->customer->first_name ?? __('site.anonymous') }}</h3>
                    <p class="font-normal text-sm text-gri mt-1">{{ __('site.customer') }}</p>
                </div>
            </div>
        @endforeach
      </div>
  </div>

@endif
</section>
