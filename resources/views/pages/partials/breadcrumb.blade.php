<section class="breadcrumb py-16 sm:py-32 relative">
    <img class="object-cover" src="assets/img/breadcrumb.jpg" />
    <div class="container z-10 relative text-center text-white">
        <h1 class="font-semibold text-4xl mb-3 sm:mb-6">
            {{$page->title}}
        </h1>
        <ul>
            <li class="inline-block"><a href="{{route('home')}}" class="px-5">{{__('site.home')}}</a></li>
            <li class="inline-block"><a class="px-5"> {{$page->title}}</a></li>
        </ul>
    </div>
</section>