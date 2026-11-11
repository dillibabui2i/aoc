<meta charset="utf-8" />
 <meta http-equiv="X-UA-Compatible" content="IE=edge" />
 <meta name="viewport" content="width=device-width, initial-scale=1.0" />
 <meta name="csrf-token" content="{{ csrf_token() }}">
 <?php if (env('APP_ENV') != 'production') { ?>
    <link rel="shortcut icon" href="{{ asset('resources/images/faviconred.png') }}" />
 <?php }else {?>
    <link rel="shortcut icon" href="{{ asset('resources/images/favicon.png') }}" />
 <?php }?>
 <link rel="preconnect" href="https://fonts.googleapis.com" integrity="sha256-k4yxU2BV0cc04MA+Aj0OlUC1n5t3as/RDH4QfPnH0z0=" crossorigin="anonymous">
 <link rel="preconnect" href="https://fonts.gstatic.com" integrity="sha256-I5JukYXY1DwCgHqDj/s3PMGXdyYJSk5GgHxmranddmA=" crossorigin="anonymous">
 <link href="{{ mix('css/app.css') }}" rel="stylesheet">
 <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" integrity="sha256-eZrrJcwDc/3uDhsdt61sL2oOBY362qM3lon1gyExkL0=" crossorigin="anonymous">

<script src="https://kit.fontawesome.com/fa1ffacba9.js" crossorigin="anonymous"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400..800&family=Barlow:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;1,100&family=Cormorant+Garamond:ital,wght@0,300;0,500;0,600;0,700;1,500&display=swap" rel="stylesheet">
 <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/themes/base/jquery-ui.min.css"
        integrity="sha512-ELV+xyi8IhEApPS/pSj66+Jiw+sOT1Mqkzlh8ExXihe4zfqbWkxPRi8wptXIO9g73FSlhmquFlUOuMSoXz5IRw=="
        crossorigin="anonymous"
        referrerpolicy="no-referrer" />
 <link href="{{ mix('css/nthlanding.css') }}?v={{ rand() }}" rel="stylesheet">
 <link href="{{ mix('css/nthlanding_extra_style.css') }}?v={{ rand() }}" rel="stylesheet">
 <link href="{{ mix('css/bootstrap-multiselect.min.css') }}" rel="stylesheet">

 <title>AOC - @yield('title')</title>

 <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"
 integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ=="
 crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="{{ mix('js/app.js') }}"></script>
<script
 src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"
 integrity="sha512-57oZ/vW8ANMjR/KQ6Be9v/+/h6bq9/l3f0Oc7vn6qMqyhvPd1cvKBRWWpzu0QoneImqr2SkmO4MSqU+RpHom3Q=="
 crossorigin="anonymous"
 referrerpolicy="no-referrer"></script>
<script
 src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/jquery.validate.min.js"
 integrity="sha512-WMEKGZ7L5LWgaPeJtw9MBM4i5w5OSBlSjTjCtSnvFJGSVD26gE5+Td12qN5pvWXhuWaWcVwF++F7aqu9cvqP0A=="
 crossorigin="anonymous"
 referrerpolicy="no-referrer"></script>
<script
 src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/additional-methods.min.js"
 integrity="sha512-TiQST7x/0aMjgVTcep29gi+q5Lk5gVTUPE9XgN0g96rwtjEjLpod4mlBRKWHeBcvGBAEvJBmfDqh2hfMMmg+5A=="
 crossorigin="anonymous"
 referrerpolicy="no-referrer"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js" integrity="sha256-lpQbyCSrPqrv7IZbdk1u4zJ3Ft/DUAIfZElc0Zi25Kw=" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/fixedheader/3.2.4/js/dataTables.fixedHeader.min.js" integrity="sha256-ri7GycJ5c18ATc0nwAl/ExYL/9ycSfakyEoGYpz7pCA=" crossorigin="anonymous"></script>
<script type="text/javascript">
 let datatableMsg = 'Invalid data or session timed out. Please reload the page. ';
 datatableMsg += 'If the problem still exists please contact support.';
 $.fn.dataTable.ext.errMode = () => alert(datatableMsg);

   function toggleInputsByDevice() {
      isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent) || window.innerWidth <= 768;
      if (isMobile) {
        $('.mobile-control').prop('disabled', false);
        $(".mobile-element").show();
        $(".desktop-element").hide();
        $('.desktop-control').prop('disabled', true);
      } else {
        $('.mobile-control').prop('disabled', true);
        $('.desktop-control').prop('disabled', false);
         $(".mobile-element").hide();
        $(".desktop-element").show();
      }
    }
    var isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent) || window.innerWidth <= 768;
    $(document).ready(function () {
      toggleInputsByDevice();
      // Also run on window resize or orientation change
      $(window).on('resize orientationchange', function () {
        toggleInputsByDevice();
      });
    });
</script>
<script src="{{ mix('js/bootstrap-multiselect.min.js') }}"></script>

@if(env('APP_ENV') == 'production')
    @include('partials.ga')
@endif
