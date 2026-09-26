<section class="slider w-full  ">
    @foreach($sliders as $slider)
        <a href="{{ $slider->ml('link') }}">
            <picture>
                <source media="(max-width: 1023px)" srcset="{{ $slider->getFirstMediaUrl('image_mobile_'.app()->getLocale()) }}">
                <img src="{{ $slider->getFirstMediaUrl('image_'.app()->getLocale()) }}"
                    alt="{{ $slider->ml('name') }}" />
            </picture>
        </a>
    @endforeach
</section>
