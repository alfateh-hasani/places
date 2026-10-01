<section class="slider w-full  ">
    @foreach($sliders as $slider)
        <a href="{{ $slider->ml('link') }}">
            <picture>
                <source media="(max-width: 1023px)" srcset="{{ $slider->displayImageUrl('image_mobile_'.app()->getLocale(), 'hero_mobile') }}" width="900" height="1208">
                <img src="{{ $slider->displayImageUrl('image_'.app()->getLocale(), 'hero') }}"
                    width="1600" height="537"
                    @if($loop->first) fetchpriority="high" loading="eager" @else loading="lazy" @endif
                    alt="{{ $slider->ml('name') }}" />
            </picture>
        </a>
    @endforeach
</section>
