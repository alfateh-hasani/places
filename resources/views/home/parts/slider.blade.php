<section class="slider w-full  ">
    @foreach($sliders as $slider)
        <a href="{{ $slider->ml('link') }}">
            @desktop
                <img src="{{ $slider->getFirstMediaUrl('image_'.app()->getLocale()) }}" 
                    alt="{{ $slider->ml('name') }}" />
            @elsedesktop
                <img src="{{ $slider->getFirstMediaUrl('image_mobile_'.app()->getLocale()) }}" 
                    alt="{{ $slider->ml('name') }}" />
            @enddesktop
    
        </a>
    @endforeach
</section>
