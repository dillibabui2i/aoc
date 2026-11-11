<?php
namespace App\Helpers;
$route = \Route::getCurrentRoute()->getActionName();
$routeName = \Route::currentRouteName();
?>
<!DOCTYPE html>
<html lang="en">
    <head>
      @include('layouts.nthLandingHeader')
    </head>
    <body class="{{$routeName}}">
        <header>
        <div class="logo">
            <div class="container">
            <img src="{{ asset('resources/images/logo.png') }}" />
                </div>
        </div>
			<div class="header-cloud">
                    <img decoding="async" loading="lazy" class="" src="{{ asset('resources/images/h-clouds2-1.png') }}" alt="h-clouds2" itemprop="image" height="40" width="1440" data-no-lazy="1">
                        </div>
<!--
        <div class="hero">
            <div class="container">
            <img src="{{ asset('resources/images/hero.png') }}" />	
                </div>
        </div>
-->
			<section class="reward-section">
    <div class="reward-cnt fl-row-content col-md-12 p-0">
        <div class="reward-cnt-inner col-md-12 p-0">
                <div class="col-md-7 centerbanner">
                    <h2>
                        REWARDING <br>CAREERS IN <br>HOME HEALTH
                    </h2>
                </div>
                <div class="col-md-5 pull-right text-right pr-0">
                    <div class="fl-photo-content-main-banner">
                        <img class="fl-photo-main-banner" src="{{ asset('resources/images/hero-woman2.png') }}" alt="">
                    </div>
                </div>
        </div>

        <div class="fl-photo-content-bottom-banner col-md-12 p-0">
            <img decoding="async" class="fl-photo-img-bottom" loading="lazy" src="{{ asset('resources/images/top-s.svg') }}" alt="top-s" itemprop="image" title="top-s">
        </div>

    </div>

    

    
</section>
        </header>
        <div class="container main-container">
                @yield('content')
        </div>
        
        <div class="footer-container">
            @include('layouts.nthLandingFooter')
        </div>
        @if($routeName != 'nthlanding.nthAvailableCases')
        <script>
            $(document).ready(function() {
             //   $("#mainFooter").hide();
            });
        </script>
        @endif
        @if($routeName == 'nthlanding.results')
        <script>(function(t,e,s,o){var n,c,l;t.SMCX=t.SMCX||[],e.getElementById(o)||(n=e.getElementsByTagName(s),c=n[n.length-1],l=e.createElement(s),l.type="text/javascript",l.async=!0,l.id=o,l.src="https://widget.surveymonkey.com/collect/website/js/tRaiETqnLgj758hTBazgdyyd68aIrSLAmJo_2FgKLxMEcjvyjTuxS_2F7xJcvdlikLUT.js",c.parentNode.insertBefore(l,c))})(window,document,"script","smcx-sdk");</script>
        @endif
    </body>
</html>