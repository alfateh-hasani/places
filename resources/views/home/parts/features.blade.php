<section class="automated pb-24">
    <div class="container">
        <div class="text-center">
            <h2
                class="font-semibold text-base md:text-3xl text-center mb-8 md:mb-16 relative inline-block ml-auto mr-auto rtl:ml-0 rtl:mr-0"
            >
                @lang('site.automated_marketing')<br />
                @lang('site.with_data_driven_insight') - <span>@lang('site.insight')</span>
            </h2>
        </div>
        <div class="md:grid md:grid-cols-3 md:gap-6 max-w-full">
            <div class="md:pt-24">
                <ul>
                    @foreach ($features_1 as $feature)
                        <li class="mb-6">
                            <div class="w-12 h-12 rounded-full bg-automated-{{ $feature->id }} float-left rtl:float-right relative mt-3 mb-5 mr-5 sm:mr-7 rtl:mr-0 rtl:ml-7 ease-in-out duration-300">
                                @if($feature->hasMedia('icon'))
                                    <img src="{{ $feature->getFirstMediaUrl('icon') }}" class="absolute" />
                                @endif
                            </div>
                            <div>
                                <h4 class="font-normal text-base text-black mb-2">
                                    {{ $feature->name_ar }}
                                </h4>
                                <p class="font-normal text-sm text-gritext">
                                    {{ $feature->description_ar }}
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
            
            <div class="text-center">
                <img src="{{ asset('assets/img/app_427.png') }}" class="inline" />
            </div>
            
            <div class="md:pt-24">
                <ul>
                    @foreach ($features_2 as $feature)
                        <li class="mb-6">
                            <div class="w-12 h-12 rounded-full bg-automated-{{ $feature->id }} float-left rtl:float-right relative mt-3 mb-5 mr-5 sm:mr-7 rtl:mr-0 rtl:ml-7 ease-in-out duration-300">
                                @if($feature->hasMedia('icon'))
                                    <img src="{{ $feature->getFirstMediaUrl('icon') }}" class="absolute" />
                                @endif
                            </div>
                            <div>
                                <h4 class="font-normal text-base text-black mb-2">
                                    {{ $feature->name_ar }}
                                </h4>
                                <p class="font-normal text-sm text-gritext">
                                    {{ $feature->description_ar }}
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div> 
        </div>
    </div>
</section>
