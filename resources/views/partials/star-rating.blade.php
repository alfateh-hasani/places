{{--
    Reusable star-rating display.

    Usage:  @include('partials.star-rating', ['rating' => $apartment->total_ratings, 'count' => $apartment->reviews->count()])

    Draws exactly five stars in a single SVG. Each star is coloured independently
    in PHP — full gold, full gray, or (only the single fractional star) a per-star
    gradient. There is no cross-star gradient or overlapping layer, so it always
    renders 5 crisp stars and can never read as extra/duplicated stars. When there
    are no reviews it shows a muted "no reviews yet" state.

    Self-contained (inline SVG + inline styles), lives in the main repo.
--}}
@php
    $ratingValue = is_numeric($rating ?? null) ? (float) $rating : 0.0;
    $reviewsCount = (int) ($count ?? 0);
    $hasReviews = $reviewsCount > 0 && $ratingValue > 0;
    $uid = uniqid('star-');
    $gold = '#f7bb8e';
    $gray = '#9a9a9a';
    $starPath = 'M10 1.2 12.6 6.9 18.9 7.6 14.2 11.8 15.5 18 10 14.8 4.5 18 5.8 11.8 1.1 7.6 7.4 6.9Z';
@endphp

<div class="flex items-center gap-1.5">
    <svg width="80" height="15" viewBox="0 0 100 20" style="display:block; flex:none;"
         role="img"
         aria-label="{{ $hasReviews ? number_format($ratingValue, 1) . ' ' . __('apartment.out_of_5') : __('apartment.no_reviews_yet') }}">
        @for ($i = 0; $i < 5; $i++)
            @php $fraction = max(0.0, min(1.0, round($ratingValue - $i, 2))); @endphp

            @if ($fraction >= 1)
                <path transform="translate({{ $i * 20 }},0)" d="{{ $starPath }}" fill="{{ $gold }}"></path>
            @elseif ($fraction <= 0)
                <path transform="translate({{ $i * 20 }},0)" d="{{ $starPath }}" fill="{{ $gray }}"></path>
            @else
                <defs>
                    <linearGradient id="{{ $uid }}-{{ $i }}" x1="0" y1="0" x2="1" y2="0">
                        <stop offset="{{ $fraction * 100 }}%" stop-color="{{ $gold }}"></stop>
                        <stop offset="{{ $fraction * 100 }}%" stop-color="{{ $gray }}"></stop>
                    </linearGradient>
                </defs>
                <path transform="translate({{ $i * 20 }},0)" d="{{ $starPath }}" fill="url(#{{ $uid }}-{{ $i }})"></path>
            @endif
        @endfor
    </svg>

    @if ($hasReviews)
        <span class="font-semibold text-xs text-title">{{ number_format($ratingValue, 1) }}</span>
        <span class="font-normal text-xs text-reviews">({{ $reviewsCount }} @lang('apartment.reviews'))</span>
    @else
        <span class="font-normal text-xs text-reviews">@lang('apartment.no_reviews_yet')</span>
    @endif
</div>
