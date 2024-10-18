<section class="slider w-[140vw] -ml-[20vw] sm:ml-0 sm:w-full">
    @foreach($sliders as $slider)
        <a href="{{ $slider->ml('link') }}">
            <img src="{{ $slider->getFirstMediaUrl('image_ar', 'thumb') }}" 
                 alt="{{ $slider->ml('name') }}" />
        </a>
    @endforeach
</section>
