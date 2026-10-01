  @stack('TopJs')

  @include('common.recaptcha')

  <script type="text/javascript"  src="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"  ></script>
  <script  type="text/javascript"  src="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.js" ></script>
  <script  type="text/javascript" src="{{ asset('assets/vendor/flowbite-2.5.1/flowbite.min.js') }}"  ></script>
  <script type="text/javascript" src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script type="text/javascript" src="{{ asset('assets/js/jquery-searchbox.js')}}"></script>
  <script type="text/javascript" src="{{ asset('assets/js/tel.js') }}?v={{ @filemtime(public_path('front/assets/js/tel.js')) }}"></script>
  <script type="text/javascript" src="{{ asset('assets/js/main.js') }}?v={{ @filemtime(public_path('front/assets/js/main.js')) }}"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

  <script>
    /* Saudi Riyal symbol helpers for prices built in JavaScript.
       Use with .html() (NOT .text()) so the symbol span renders:
           $('#total').html(window.formatSAR(response.total));   // "1,234.00 ⃁"
       window.SAR_SYMBOL is the bare symbol markup for manual concatenation. */
    window.SAR_SYMBOL = '<span class="sar-symbol" role="img" aria-label="{{ __('apartment.currency') }}">⃁</span>';
    window.formatSAR = function (amount, decimals) {
      if (typeof decimals === 'undefined') { decimals = 2; }
      var value = (amount === '' || amount === null || isNaN(amount))
        ? amount
        : Number(amount).toLocaleString(undefined, {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
          });
      return '<span class="sar-amount">' + value + '</span> ' + window.SAR_SYMBOL;
    };
  </script>

  <script>
    $(document).ready(function () {
      $(".select2").select2();
    });
  </script>

  @include('partials.wishlist')

  @stack('js')

  @include('partials.web-push')