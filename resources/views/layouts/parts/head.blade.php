    <meta charset="utf-8" />
    <meta name="author" content="MADAR SOLUTIONS" />
      {!! SEO::generate() !!}
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta   name="viewport"  content="width=device-width, initial-scale=1, shrink-to-fit=no"   />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    @stack('TopCss')
    
    <link href="{{ asset('assets/css/style.css?44')}}" rel="stylesheet" />
    <link    href="https://cdn.jsdelivr.net/npm/flowbite@2.5.1/dist/flowbite.min.css"  rel="stylesheet"  />
    <link    href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"   rel="stylesheet" />
    <link rel="icon" href="{{ asset('assets/img/favicon.svg')}}" />
    <script  type="text/javascript"    src="https://code.jquery.com/jquery-3.7.1.js"  ></script>
    <script src="https://code.jquery.com/ui/1.14.0/jquery-ui.js"></script>

    
    <script type="text/javascript" src="https://cdn.tailwindcss.com"></script>
    <script type="text/javascript" src="{{ asset('assets/js/maplace.js?3')}}"></script>

    <script>
      tailwind.config = {
          theme: {
              container: {
                  center: true,
              },
              colors: {
                  'gri': '#303610',
                  'white': '#fff',
                  'black': '#000',
                  'border': '#E8E8E8',
                  'reviews': '#999999',
                  'title': '#2C2C2C',
                  'feature': '#F6F6F6',
                  'feature-border': '#E8E8E8',
                  'price': '#EF552C',
                  'automated-1': 'rgba(239, 85, 44, .2)',
                  'automated-2': 'rgba(255, 90, 95, .2)',
                  'automated-3': 'rgba(233, 187, 113, .2)',
                  'gritext': '#444',
                  'commentbg': '#F4F6F8',
                  'commentborder': '#F1F1F1',
                  'blackopacity': 'rgba(0, 0, 0, .1)',
                  'properits': '#F8F7FD',
                  'filterbackground': '#fbfbfb',
                  'filterborder': '#ececec',
                  'filteritem': '#ebebe8',
                  'filterhover': '#303610',
                  'sort': '#f6f6f6',
                  'sortactive': '#303610',
                  'blue': '#0068CF',
                  'footer': '#fcfcfc',
                  'titletext': '#848484'
              }
          }
      }
  </script> 
    <style>
      .datepicker-cell {
        color: #787e8b !important;
      }
      .focused {
        color: black !important;
      }
      .select2-selection {
        border: none !important;
      }
      .select2-container--default
        .select2-selection--single
        .select2-selection__rendered {
        color: inherit !important;
      }
      .view-switch,
      .prev-btn > svg,
      .next-btn > svg {
        color: #000 !important;
      }
      .range-start {
        color: white !important;
      }
    </style>


    @if(app()->getLocale() == 'ar')
      <link    href="{{ asset('assets/css/rtl.css?'.time())}}"   rel="stylesheet" />
    @endif

      <link    href="{{ asset('assets/css/custom.css?'.time())}}"   rel="stylesheet" />

    @stack('css')