<section class="breadcrumb py-16 sm:py-32 relative">
    <img class="object-cover" src="{{getImage($page,'image')}}" />
    <div class="container z-10 relative text-center text-white">
        <h1 class="font-semibold text-4xl mb-3 sm:mb-6">
            {{$page->{'name_'.app()->getLocale()} }}
        </h1>
        <ul>
            <li class="inline-block"><a href="{{route('home')}}" class="px-5">{{__('site.home')}}</a></li>
            <li class="inline-block"><a class="px-5"> 
                {{$title ?? $page->{'name_'.app()->getLocale()} }}    
            </a></li>
        </ul>
    </div>
</section>