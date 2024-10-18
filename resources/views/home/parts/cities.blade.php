<section class="py-2 sm:py-20 cities">
      <div class="container">
        <p 
            class="text-center sm:text-left rtl:sm:text-right font-normal text-xs sm:text-base text-price">
            @lang('site.readable_content')
        </p>

        <h3 
            class="text-center sm:text-left rtl:sm:text-right font-semibold text-base sm:text-3xl text-black mt-1 sm:mt-3 mb-4 sm:mb-12">
            @lang('site.popular_cities')
        </h3>


       <div class="sm:grid sm:grid-cols-2 lg:grid-cols-3 sm:gap-6 max-w-full slider">
            @foreach ($cities as $city)
                <a   class="{{ $loop->first ? 'col-span-2' : '' }} relative px-2 sm:px-0">
                    <img   src="{{ $city->image }}"    class="w-full h-80 object-cover rounded-xl"   alt="{{ $city->ml('name') }}" />
                    <h1  class="absolute left-6 bottom-6 font-normal font-semibold text-base text-white z-10 ease-in-out duration-200">
                        {{ $city->ml('name') }}
                    </h1>
                    <p  class="absolute right-6 bottom-6 font-normal font-semibold text-sm text-black z-10 bg-white py-1.5 px-5 rounded-2xl ease-in-out duration-700">
                        {{ $city->apartments_count }} @lang('site.apartments')
                    </p>
                </a>
            @endforeach
        </div>

      </div>
    </section>