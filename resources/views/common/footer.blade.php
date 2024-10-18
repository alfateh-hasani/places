<footer class="border-t border-blackopacity pt-12">
  <div class="container border-b border-blackopacity pb-6">
    <div class="lg:grid lg:grid-cols-3 lg:gap-8 max-w-full">
      <div>
        <img src="{{ asset('assets/img/logo.svg') }}" alt="Logo" />
        <p class="font-normal text-sm lg:text-base text-black mt-8 text-justify">
          It is a long established fact that a reader will be distracted by the readable
          content of a page when looking at its layout. The point of using Lorem Ipsum is
          that it has a more-or-less normal distribution of letters, as opposed.
        </p>
      </div>
      <div class="my-6 lg:my-0">
        <h6 class="font-semibold text-xl text-black">Site Map</h6>
        <ul class="mt-4 lg:mt-10 grid grid-cols-2 gap-2 max-w-full mx-0">
          <li><a class="block font-light text-black mb-2 lg:mb-5" href="#">Home</a></li>
          <li><a class="block font-light text-black mb-2 lg:mb-5" href="#">Privacy & Policy</a></li>
          <li><a class="block font-light text-black mb-2 lg:mb-5" href="#">Blogs</a></li>
          <li><a class="block font-light text-black mb-2 lg:mb-5" href="#">Terms Of Use</a></li>
          <li><a class="block font-light text-black mb-2 lg:mb-5" href="#">Contact Us</a></li>
          <li><a class="block font-light text-black mb-2 lg:mb-5" href="#">FAQ</a></li>
        </ul>
      </div>
      <div>
        <h6 class="font-semibold text-xl text-black">Contact Us</h6>
        <ul class="mt-4 lg:mt-10">
          <li>
            <a class="block font-light text-black mb-5" href="mailto:info@testmail.com">
              <img class="inline-block mr-3" src="{{ asset('assets/img/mail.svg') }}" alt="Mail Icon" />
              info@testmail.com
            </a>
          </li>
          <li>
            <a class="block font-light text-black mb-5" href="tel:+966138589000">
              <img class="inline-block mr-3" src="{{ asset('assets/img/tel.svg') }}" alt="Phone Icon" />
              +966-13-858-9000
            </a>
          </li>
          <li>
            <a class="block font-light text-black mb-5" href="#">
              <img class="inline-block mr-3" src="{{ asset('assets/img/address.svg') }}" alt="Address Icon" />
              Address Here
            </a>
          </li>
        </ul>
      </div>
    </div>
  </div>

  <div class="container py-4">
    <div class="float-left w-full sm:w-auto text-center sm:text-left mb-3 sm:mb-0">
      <p class="inline-block font-normal text-base text-black">
        All Rights Reserved © {{ date('Y') }}
      </p>
      <a class="inline-block font-normal text-base text-price" href="#">Website Title</a>
    </div>

    <div class="float-right w-full sm:w-auto text-center sm:text-left">
      <ul class="social">
        <li class="inline-block">
          <a class="block w-8 h-8 bg-blackopacity rounded-lg relative" href="#">
            <img class="absolute" src="{{ asset('assets/img/linkedin.svg') }}" alt="LinkedIn" />
          </a>
        </li>
        <li class="inline-block">
          <a class="block w-8 h-8 bg-blackopacity rounded-lg relative" href="#">
            <img class="absolute" src="{{ asset('assets/img/twitter.svg') }}" alt="Twitter" />
          </a>
        </li>
        <li class="inline-block">
          <a class="block w-8 h-8 bg-blackopacity rounded-lg relative" href="#">
            <img class="absolute" src="{{ asset('assets/img/instagram.svg') }}" alt="Instagram" />
          </a>
        </li>
        <li class="inline-block">
          <a class="block w-8 h-8 bg-blackopacity rounded-lg relative" href="#">
            <img class="absolute" src="{{ asset('assets/img/facebook.svg') }}" alt="Facebook" />
          </a>
        </li>
      </ul>
    </div>

    <div class="clear-both"></div>
  </div>
</footer>
