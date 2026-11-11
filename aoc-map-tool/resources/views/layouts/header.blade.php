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
 <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=block" integrity="sha256-j7vOETZG08NNRPKfyQND7PvDQR83cQ/1b1yaCVI0BSA=" crossorigin="anonymous">
 <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;500;600;700;800&display=block" integrity="sha256-btzlqBMUKqG4hCG6ugjU4oHmNMUkKv/HXHImld4Is2M=" crossorigin="anonymous">
 <link href="{{ mix('css/app.css') }}" rel="stylesheet">
 <link href="{{ mix('css/custom.css') }}" rel="stylesheet">
 <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" integrity="sha256-eZrrJcwDc/3uDhsdt61sL2oOBY362qM3lon1gyExkL0=" crossorigin="anonymous">



 <title>AOC - @yield('title')</title>
