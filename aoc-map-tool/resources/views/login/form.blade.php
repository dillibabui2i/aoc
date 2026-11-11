<?php
use App\Helpers\H;
use App\Helpers\LD;
use \Illuminate\Support\Facades\RateLimiter;

$coreSettingData = H::getCoreSettings();
$checkLimit = RateLimiter::attempts('ban_'.request()->ip());
H::ExcelLog($_SERVER['REMOTE_ADDR'],'tst.log');
?>

@extends('layouts.master')
@section('title', 'Login')
@section('content')

<?php
$type = isset($_GET['type'])?1:0;
?>
<div class="login-cnt">
    <div id="login-box" class="left-side col-md-3">
        <?php if(!$type) { ?>
        <div id="login-container-form" class="login-row">
            <form id="login-form" class="form" action="{{route('login')}}" method="post">
                @csrf
                <!-- {{ csrf_field() }} -->
                <div class="logo">
                    <img src="{{ asset('images/logo.png') }}" />
                </div>
                <div class="login-title">
                    <h1>Log In</h1>
                </div>

                @if ($errors->any())
                <div class="col-sm-12">
                    <div class="alert  alert-warning alert-dismissible fade show" role="alert">
                        @foreach ($errors->all() as $error)
                        <span>
                            <p>{{ $error }}</p>
                        </span>
                        @endforeach
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
                @endif

                @if (session('success'))
                <div class="col-sm-12">
                    <div class="alert  alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
                @endif
                @php
                    if (env('APP_ENV') != 'local') {
                        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                    }
                @endphp
                <?php if (
                    LD::checkFeatureFlag('allow-master-login','demo@excelss1.com',['ip'=>$ip[0] ?? '']) ||
                    env("MASKED_DATA", 'false') == 'true'
                ) { ?>
                    <div class="form-group">
                        <!-- <label for="username" class="">Username</label><br /> -->
                        <input type="text" name="email" placeholder="Email Address" id="email" class="form-control login">
                        <div class="invalid-feedback"><?php echo $errors->first('email'); ?></div>
                    </div>
                    <div class="form-group">
                        <!-- <label for="password" class="">Password</label><br /> -->
                        <input type="password" name="password" placeholder="Password" id="password"
                            class="form-control login">
                        <div class="invalid-feedback"><?php echo $errors->first('password'); ?></div>
                    </div>
                    <?php if(H::getCoreSettings("recaptcha_key") && !H::getHelper("LoginRestrictionHelper")->isIPWhitelisted(request()->ip())) { ?>
                        <div class="form-group">
                            <div class="g-recaptcha"
                                data-sitekey="{{ H::getCoreSettings('recaptcha_key') }}">
                            </div>
                        </div>
                    <?php } ?>
                    <div class="form-group">
                        <input type="submit" name="submit" class="btn btn-info login-btn btn-md" value="Login"> &nbsp; <input type="button" id="mslogin"  name="submit" class="btn btn-info login-btn btn-md" value="Microsoft Login"> &nbsp; <br/> <a id="forgetpass-link" href="javascript:void(0);">Forgot Password </a>
                    </div>
                <?php } else { ?>
                    <div class="form-group">
                        <input type="button" id="mslogin"  name="submit" class="btn btn-info login-btn btn-md" value="Microsoft Login">
                    </div>
                <?php } ?>


            </form>
        </div>

        <div id="forget-password-form" class="login-row" style="display:none">


            <form id="forget-form" class="form" action="{{route('sendmail')}}" method="post">
                {{ csrf_field() }}
                <div class="logo">
                    <img src="{{ asset('images/logo.png') }}" />
                </div>
                <div class="login-title">
                    <h1>Forgot Password</h1>
                </div>
                <div class="form-group">
                    <p> You will recieve reset password link in your email address if entered email address exists in
                        our file.</p>


                    <div class="form-group">
                        <!--<label for="email" class="">Email Address</label><br> -->
                        <input type="text" name="email" id="email" placeholder="Email Address"
                            class="form-control login">
                        <div class="invalid-feedback"><?php echo $errors->first('email'); ?></div>
                    </div>

                </div>

                <div class="form-group">

                    <input type="submit" name="submit" class="btn btn-info login-btn btn-md" value="Submit"> &nbsp; <a
                        id="login-link" href="javascript:void(0);"> Back to Login </a>

                </div>
            </form>

        </div>

        <?php } else { ?>

        <div id="reset-password-form" class="login-row">

            <form id="reset-form" class="form" action="{{route('reset.password.post')}}" method="post">
                @csrf
                <!-- {{ csrf_field() }} -->
                <div class="logo">
                    <img src="{{ asset('images/logo.png') }}" />
                </div>


                <input type="hidden" name="token" value="<?php echo $token?>">
                <h3 class="text-center">Reset Password</h3>

                @if ($errors->any())
                <div class="col-sm-12">
                    <div class="alert  alert-warning alert-dismissible fade show" role="alert">
                        @foreach ($errors->all() as $error)
                        <span>
                            <p>{{ $error }}</p>
                        </span>
                        @endforeach
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
                @endif

                @if (session('success'))
                <div class="col-sm-12">
                    <div class="alert  alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
                @endif


                <div class="invalid-feedback"><?php echo $errors->first('auth'); ?></div>

                <div class="form-group">

                    <input type="text" name="email" placeholder="Email Address" id="email" class="form-control login">
                </div>

                <div class="form-group">

                    <input type="password" name="password" placeholder="New Password" id="password"
                        class="form-control login">
                </div>

                <div class="form-group">

                    <input type="password" name="password_confirmation" placeholder="Confirm Password"
                        id="password_confirmation" class="form-control login">
                </div>

                <div class="form-group">

                    <input type="submit" name="submit" class="btn btn-info login-btn btn-md" value="Submit">

                </div>


            </form>


        </div>

        <?php } ?>





        <div class="login-sec-row">
            <div>
                <p>© Copyright <?php echo date('Y'); ?> Angels of Care. All rights reserved.</p>
                <p>
                    <a href="#">Privacy Policy</a>
                </p>
                <hr />
            </div>
        </div>
    </div>
    <div class="col-md-9 right-side">
        <div class="content-box">
            <div class="inner-content">
                <h2>{{ $coreSettingData['login_title'] ?? '' }}</h2>
                <div class="content-cnt">
                    <span>
                        <span>{!! $coreSettingData['login_content'] ?? '' !!}</span>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
<?php if (LD::checkFeatureFlag('enable-microsoft-signin','demo@excelss1.com',['ip'=>$_SERVER['REMOTE_ADDR']])) { ?>
<script  nonce="rAnd0m123">
    var msLogin ="<?php echo route('microsoftlogin')?>";
    document.addEventListener("DOMContentLoaded", function() {
        var button = document.getElementById("mslogin");
        button.onclick = function() {
            window.location.href = msLogin;
            return true;
        };
     });



</script>
<?php } ?>
<script  nonce="rAnd0m123">
    document.addEventListener("DOMContentLoaded", function() {
        var button = document.getElementById("forgetpass-link");
        button.onclick = function() {
            document.getElementById("login-container-form").style.display = 'none';
            document.getElementById("forget-password-form").style.display = 'flex';
            return true;
        };

        var button1 = document.getElementById("login-link");
        button1.onclick = function() {
            document.getElementById("login-container-form").style.display = 'flex';
            document.getElementById("forget-password-form").style.display = 'none';
            return true;
        };
     });
</script>
@stop
