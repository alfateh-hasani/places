<a href="{{$blog->link}}" class="border border-border rounded-lg p-3 block mb-2 lg:mb-0">
    <img class="h-48 rounded-lg w-full object-cover mb-3" src="{{getImage($blog,'image','card')}}" />
    <h4 class="font-bold text-sm text-black">
        {{$blog->{'name_'.app()->getLocale()} }}
    </h4>
    <p class="font-light font-sm text-titletext my-3">
        {{$blog->{'info_'.app()->getLocale()} }}
    </p>
    <p class="font-normal text-xs text-price">
            {{__('site.explor_more')}}
        <img class="h-3 inline-block ml-2 rtl:rotate-180" src="{{asset('assets/img/blog-arrow.svg')}}" /></p>
</a>
