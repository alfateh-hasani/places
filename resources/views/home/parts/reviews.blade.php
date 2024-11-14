 

<section class="comments py-12">
    <div class="container">
      <div class="text-center">
        <div class="p-1 bg-black inline-block title text-left rounded-3xl">
          <div class="bg-white w-9 h-9 rounded-full float-left relative mr-3">
            <img src="{{ asset('assets/img/star-comment.svg') }}" class="absolute" />
          </div>
          <p class="float-left font-normal text-base sm:text-lg text-white pr-4 sm:pr-6 py-1.5 sm:py-1">
            @lang('site.related_reviews', ['rating' => '4/5', 'users' => '1 Dyafa'])
          </p>
          <div class="clear-both"></div>
        </div>
      </div>
      <h3 class="text-center font-semibold text-base sm:text-3xl text-black mt-4 mb-6 sm:my-8" >
        @lang('site.words_of_praise')
        <br />@lang('site.about_our_presence')
      </h3>
    </div>
    <div class="relative comment-list top-list mb-4">
      <ul class="comment-slider-1">
        <li class="px-2">
          <a class="block bg-commentbg border border-commentborder py-6 px-8 rounded-xl">
            <div>
                @foreach (range(1, 5) as $item)
                    <img src="{{asset('assets/img/comment-star.svg')}}" class="inline-block" />
                @endforeach
    
            </div>
            <p class="font-normal text-sm text-black mt-5 mb-6">
              Established Fact That A Reader Will Be Distracted By The
              Readable Content Of A Page When Looking At Its Layout. The Point
            </p>
            <h4 class="font-normal text-lg text-price">Hazem Anwar</h4>
            <p class="font-normal text-sm text-gri mt-1">Founder & CEO</p>
          </a>
        </li>
        <li class="px-2">
          <a class="block bg-commentbg border border-commentborder py-6 px-8 rounded-xl">
            <div>
                @foreach (range(1, 5) as $item)
                <img src="{{asset('assets/img/comment-star.svg')}}" class="inline-block" />
            @endforeach

            </div>
            <p class="font-normal text-sm text-black mt-5 mb-6">
              Established Fact That A Reader Will Be Distracted By The
              Readable Content Of A Page When Looking At Its Layout. The Point
            </p>
            <h4 class="font-normal text-lg text-price">Hazem Anwar</h4>
            <p class="font-normal text-sm text-gri mt-1">Founder & CEO</p>
          </a>
        </li>
        <li class="px-2">
          <a class="block bg-commentbg border border-commentborder py-6 px-8 rounded-xl">
            <div>
                @foreach (range(1, 5) as $item)
                <img src="{{asset('assets/img/comment-star.svg')}}" class="inline-block" />
            @endforeach

            </div>
            <p class="font-normal text-sm text-black mt-5 mb-6">
              Established Fact That A Reader Will Be Distracted By The
              Readable Content Of A Page When Looking At Its Layout. The Point
            </p>
            <h4 class="font-normal text-lg text-price">Hazem Anwar</h4>
            <p class="font-normal text-sm text-gri mt-1">Founder & CEO</p>
          </a>
        </li>
        <li class="px-2">
          <a class="block bg-commentbg border border-commentborder py-6 px-8 rounded-xl">
            <div>
                @foreach (range(1, 5) as $item)
                <img src="{{asset('assets/img/comment-star.svg')}}" class="inline-block" />
            @endforeach

            </div>
            <p class="font-normal text-sm text-black mt-5 mb-6">
              Established Fact That A Reader Will Be Distracted By The
              Readable Content Of A Page When Looking At Its Layout. The Point
            </p>
            <h4 class="font-normal text-lg text-price">Hazem Anwar</h4>
            <p class="font-normal text-sm text-gri mt-1">Founder & CEO</p>
          </a>
        </li>
        <li class="px-2">
          <a class="block bg-commentbg border border-commentborder py-6 px-8 rounded-xl">
            <div>
                @foreach (range(1, 5) as $item)
                <img src="{{asset('assets/img/comment-star.svg')}}" class="inline-block" />
            @endforeach

            </div>
            <p class="font-normal text-sm text-black mt-5 mb-6">
              Established Fact That A Reader Will Be Distracted By The
              Readable Content Of A Page When Looking At Its Layout. The Point
            </p>
            <h4 class="font-normal text-lg text-price">Hazem Anwar</h4>
            <p class="font-normal text-sm text-gri mt-1">Founder & CEO</p>
          </a>
        </li>
     
        <li class="px-2">
          <a class="block bg-commentbg border border-commentborder py-6 px-8 rounded-xl">
            <div>
                @foreach (range(1, 5) as $item)
                <img src="{{asset('assets/img/comment-star.svg')}}" class="inline-block" />
            @endforeach

            </div>
            <p class="font-normal text-sm text-black mt-5 mb-6">
              Established Fact That A Reader Will Be Distracted By The
              Readable Content Of A Page When Looking At Its Layout. The Point
            </p>
            <h4 class="font-normal text-lg text-price">Hazem Anwar</h4>
            <p class="font-normal text-sm text-gri mt-1">Founder & CEO</p>
          </a>
        </li>
        <li class="px-2">
          <a class="block bg-commentbg border border-commentborder py-6 px-8 rounded-xl">
            <div>
                @foreach (range(1, 5) as $item)
                <img src="{{asset('assets/img/comment-star.svg')}}" class="inline-block" />
            @endforeach

            </div>
            <p class="font-normal text-sm text-black mt-5 mb-6">
              Established Fact That A Reader Will Be Distracted By The
              Readable Content Of A Page When Looking At Its Layout. The Point
            </p>
            <h4 class="font-normal text-lg text-price">Hazem Anwar</h4>
            <p class="font-normal text-sm text-gri mt-1">Founder & CEO</p>
          </a>
        </li>
      </ul>
    </div>
    <div class="relative comment-list bottom-list hidden sm:block">
        <ul class="comment-slider-2">
            <li class="px-2">
                <a class="block bg-commentbg border border-commentborder py-6 px-8 rounded-xl">
                  <div>
                      @foreach (range(1, 5) as $item)
                          <img src="{{asset('assets/img/comment-star.svg')}}" class="inline-block" />
                      @endforeach
          
                  </div>
                  <p class="font-normal text-sm text-black mt-5 mb-6">
                    Established Fact That A Reader Will Be Distracted By The
                    Readable Content Of A Page When Looking At Its Layout. The Point
                  </p>
                  <h4 class="font-normal text-lg text-price">Hazem Anwar</h4>
                  <p class="font-normal text-sm text-gri mt-1">Founder & CEO</p>
                </a>
              </li>
              <li class="px-2">
                <a class="block bg-commentbg border border-commentborder py-6 px-8 rounded-xl">
                  <div>
                      @foreach (range(1, 5) as $item)
                      <img src="{{asset('assets/img/comment-star.svg')}}" class="inline-block" />
                  @endforeach
      
                  </div>
                  <p class="font-normal text-sm text-black mt-5 mb-6">
                    Established Fact That A Reader Will Be Distracted By The
                    Readable Content Of A Page When Looking At Its Layout. The Point
                  </p>
                  <h4 class="font-normal text-lg text-price">Hazem Anwar</h4>
                  <p class="font-normal text-sm text-gri mt-1">Founder & CEO</p>
                </a>
              </li>
              <li class="px-2">
                <a class="block bg-commentbg border border-commentborder py-6 px-8 rounded-xl">
                  <div>
                      @foreach (range(1, 5) as $item)
                      <img src="{{asset('assets/img/comment-star.svg')}}" class="inline-block" />
                  @endforeach
      
                  </div>
                  <p class="font-normal text-sm text-black mt-5 mb-6">
                    Established Fact That A Reader Will Be Distracted By The
                    Readable Content Of A Page When Looking At Its Layout. The Point
                  </p>
                  <h4 class="font-normal text-lg text-price">Hazem Anwar</h4>
                  <p class="font-normal text-sm text-gri mt-1">Founder & CEO</p>
                </a>
              </li>
              <li class="px-2">
                <a class="block bg-commentbg border border-commentborder py-6 px-8 rounded-xl">
                  <div>
                      @foreach (range(1, 5) as $item)
                      <img src="{{asset('assets/img/comment-star.svg')}}" class="inline-block" />
                  @endforeach
      
                  </div>
                  <p class="font-normal text-sm text-black mt-5 mb-6">
                    Established Fact That A Reader Will Be Distracted By The
                    Readable Content Of A Page When Looking At Its Layout. The Point
                  </p>
                  <h4 class="font-normal text-lg text-price">Hazem Anwar</h4>
                  <p class="font-normal text-sm text-gri mt-1">Founder & CEO</p>
                </a>
              </li>
              <li class="px-2">
                <a class="block bg-commentbg border border-commentborder py-6 px-8 rounded-xl">
                  <div>
                      @foreach (range(1, 5) as $item)
                      <img src="{{asset('assets/img/comment-star.svg')}}" class="inline-block" />
                  @endforeach
      
                  </div>
                  <p class="font-normal text-sm text-black mt-5 mb-6">
                    Established Fact That A Reader Will Be Distracted By The
                    Readable Content Of A Page When Looking At Its Layout. The Point
                  </p>
                  <h4 class="font-normal text-lg text-price">Hazem Anwar</h4>
                  <p class="font-normal text-sm text-gri mt-1">Founder & CEO</p>
                </a>
              </li>
           
              <li class="px-2">
                <a class="block bg-commentbg border border-commentborder py-6 px-8 rounded-xl">
                  <div>
                      @foreach (range(1, 5) as $item)
                      <img src="{{asset('assets/img/comment-star.svg')}}" class="inline-block" />
                  @endforeach
      
                  </div>
                  <p class="font-normal text-sm text-black mt-5 mb-6">
                    Established Fact That A Reader Will Be Distracted By The
                    Readable Content Of A Page When Looking At Its Layout. The Point
                  </p>
                  <h4 class="font-normal text-lg text-price">Hazem Anwar</h4>
                  <p class="font-normal text-sm text-gri mt-1">Founder & CEO</p>
                </a>
              </li>
              <li class="px-2">
                <a class="block bg-commentbg border border-commentborder py-6 px-8 rounded-xl">
                  <div>
                      @foreach (range(1, 5) as $item)
                      <img src="{{asset('assets/img/comment-star.svg')}}" class="inline-block" />
                  @endforeach
      
                  </div>
                  <p class="font-normal text-sm text-black mt-5 mb-6">
                    Established Fact That A Reader Will Be Distracted By The
                    Readable Content Of A Page When Looking At Its Layout. The Point
                  </p>
                  <h4 class="font-normal text-lg text-price">Hazem Anwar</h4>
                  <p class="font-normal text-sm text-gri mt-1">Founder & CEO</p>
                </a>
              </li>
        </ul>
    </div>
  </section>
