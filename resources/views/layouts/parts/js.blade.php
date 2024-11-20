  @stack('TopJs')
  
  <script type="text/javascript"  src="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"  ></script>
  <script  type="text/javascript"  src="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.js" ></script>
  <script  type="text/javascript" src="https://cdn.jsdelivr.net/npm/flowbite@2.5.1/dist/flowbite.min.js"  ></script>
  <script type="text/javascript" src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script type="text/javascript" src="{{ asset('assets/js/jquery-searchbox.js')}}"></script>
  <script type="text/javascript" src="{{ asset('assets/js/tel.js?43') }}"></script>
  <script type="text/javascript" src="{{ asset('assets/js/main.js?44') }}"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

  <script>
    $(document).ready(function () {
      $(".select2").select2();
    });
  </script>

  @stack('js')