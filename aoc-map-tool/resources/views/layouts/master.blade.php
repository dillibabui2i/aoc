<?php
namespace App\Helpers;
$route = \Route::getCurrentRoute()->getActionName();
?>
<!DOCTYPE html>
<html lang="en">
    <head>
		@include('layouts.header')
	
    </head>
    <body class="login-page {{$route}}">
		<div class="container main-container">
            @yield('content')
        </div>
    </body>
</html>