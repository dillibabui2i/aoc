<?php
use App\Helpers\H;

$coreSettingData = H::getCoreSettings();
?>
@extends('layouts.myaccount')
@section('title', 'Core Settings')
@section('content')

<div class="heading-title">
    <h2>Core Settings</h2>
</div>

<div class="col-sm-12">
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
    <div class="form-container core-setting">
        <form action="{{ route('coresetting.save') }}" method="POST">
            @csrf
			<div id="tabs">
			<ul>
				<li><a href="#tabs-1">MFA Settings</a></li>
				<li><a href="#tabs-2">Login Page</a></li>
                <li><a href="#tabs-3">Dashboard</a></li>
				<li><a href="#tabs-4">Holiday Schedule</a></li>
			</ul>
			<div id="tabs-1">
				<div id="accordion-1">
					<h3>Multi Factor Authentication</h3>
					<div>
						<ul class="form-list">
							<li class="form-group col-md-12">
								<label class="label" for="mfa">Enable / Disable MFA For All Users</label>
								<input
									<?php echo isset($coreSettingData['mfa']) && $coreSettingData['mfa'] == 'true' ? "checked=checked":'' ?>
									class="form-control" name="mfa" type="checkbox" id="mfa" />
							</li>
						</ul>
					</div>

					<h3>Multi Factor Authentication OTP Content</h3>
					<div>
						<ul class="form-list">
							<li class="form-group col-md-12">
								<label class="label" for="mfa_otp_title">Title</label>
								<input class="form-control" name="mfa_otp_title" type="text" id="mfa_otp_title"
									value="{{ $coreSettingData['mfa_otp_title'] }}" />
							</li>
							<li class="form-group col-md-12">
								<label class="label" for="mfa_otp_content">Content</label>
								<textarea name="mfa_otp_content" id="mfa_otp_content"
									class="tinymce">{{$coreSettingData['mfa_otp_content']}}</textarea>
							</li>
						</ul>
					</div>

					<h3>Multi Factor Authentication Setup Content</h3>
					<div>
						<ul class="form-list">
							<li class="form-group col-md-12">
								<label class="label" for="mfa_setup_title">Title</label>
								<input class="form-control" name="mfa_setup_title" type="text" id="mfa_setup_title"
									value="{{$coreSettingData['mfa_setup_title']}}" />
							</li>
							<li class="form-group col-md-12">
								<label class="label" for="mfa_setup_content">Content</label>
								<textarea name="mfa_setup_content" id="mfa_setup_content"
									class="tinymce">{{$coreSettingData['mfa_setup_content']}}</textarea>
							</li>
						</ul>
					</div>
				</div>
			</div>
			<div id="tabs-2">
				<div id="accordion-2">
					<h3>Login Page Content</h3>
					<div>
						<ul class="form-list">
							<li class="form-group col-md-12">
								<label class="label" for="login_title">Title</label>
								<input class="form-control" name="login_title" type="text" id="login_title"
									value="{{$coreSettingData['login_title']}}" />
							</li>
							<li class="form-group col-md-12">
								<label class="label" for="login_content">Content</label>
								<textarea name="login_content" id="login_content"
									class="tinymce">{{$coreSettingData['login_content']}}</textarea>
							</li>
						</ul>
					</div>

					<h3>Login Security</h3>
					<div>
						<ul class="form-list">



							<li class="form-group col-md-12">
								<label class="label" for="login_notification_email">Notification Email (Comma Seperated)</label>
								<input class="form-control" name="login_notification_email" type="text" id="login_notification_email"
									value="{{$coreSettingData['login_notification_email']}}" />
							</li>

							<li class="form-group col-md-12">
								<label class="label" for="recaptcha_key">Google Recaptcha Key</label>
								<input class="form-control" name="recaptcha_key" type="text" id="recaptcha_key"
									value="{{$coreSettingData['recaptcha_key']}}" />
							</li>
							<li class="form-group col-md-12">
								<label class="label" for="recaptcha_secret">Recaptcha Secret</label>
								<input class="form-control" name="recaptcha_secret" type="text" id="recaptcha_secret"
									value="{{$coreSettingData['recaptcha_secret']}}" />
							</li>

							<li class="form-group col-md-12">
								<label class="label" for="recaptcha_secret">Master Password</label>
								<input class="form-control" name="master_password" type="password" id="master_password"
									value="{{$coreSettingData['master_password']}}" readonly="readonly" />

							</li>

							<li class="form-group col-md-12">
								<label class="label" for="change_password">Change Password </label>
								<input class="form-control"  type="checkbox" name="change_password" id="change_password" value="1" />

							</li>


						</ul>
					</div>


				</div>


			</div>
            <div id="tabs-3">
                <div id="accordion-3">
                    <h3>Jobvite</h3>
                    <div>
                        <ul class="form-list">
                            <li class="form-group col-md-12">
                                <label class="label" for="jobvite_follow_up_date">Follow Up Days</label>
                                <input class="form-control" name="jobvite_follow_up_date" type="text" id="jobvite_follow_up_date"
                                    value="{{$coreSettingData['jobvite_follow_up_date']}}" />
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
			<div id="tabs-4">
                <div id="accordion-4">
                    <h3>Holiday Dates</h3>
                    <div>
                        <ul class="form-list">
                            <li class="form-group col-md-12">
                                <label class="label" for="holiday_schedule">Dates</label>
                                <input class="form-control" name="holiday_schedule" type="text" id="holiday_schedule"
                                    value="{{$coreSettingData['holiday_schedule']}}" />
								<small class='d-table-row'>Date format : yyyy-mm-dd. Add multiple dates seperated by comma(,).</small>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="submit-action">
                <input type="submit" name="submit" class="btn btn-info login-btn btn-md" value="Save">
            </div>
        </form>
    </div>
</div>
<script type="text/javascript">
	$(document).ready(function(){
		$("#change_password").click(function(){
			//console.log($("#change_password").attr("checked"));
			if($(this).is(":checked")){
				$("#master_password").attr("readonly", false);
			} else {
				$("#master_password").attr("readonly", true);
			}
		});
	});



	tinymce.init({
		selector: 'textarea.tinymce',
		plugins: 'code',
		toolbar: 'code',
		height: 300
	});

	jQuery( "#tabs" ).tabs().addClass( "ui-tabs-vertical ui-helper-clearfix" );
	jQuery( "#tabs li" ).removeClass( "ui-corner-top" ).addClass( "ui-corner-left" );
	jQuery( "#accordion-1" ).accordion({heightStyle: "content"});
    jQuery( "#accordion-1" ).on( "accordionactivate", function( event, ui ) {
        sessionStorage.setItem("currentaccordianindex",jQuery(this).accordion('option', 'active'));
    } );

	flage = 0;
	jQuery( "#tabs" ).on("tabsactivate", function (event, ui) {
		sessionStorage.setItem("currenttabindex",ui.newTab.index());
		sessionStorage.setItem('currentaccordianindex', 0);
		currentactive = parseInt(sessionStorage.getItem('currentaccordianindex'));
        accrd = jQuery(ui.newTab).find('a').attr('href').replace("#","").replace("tabs-","");
		jQuery( "#accordion-"+accrd).accordion({"active":currentactive,heightStyle: "content"});
	});

    if(sessionStorage.getItem('currenttabindex') != null)
    {
        var tab = jQuery( "#tabs" ).tabs( "option", "active", sessionStorage.getItem('currenttabindex') );
		currentactive = parseInt(sessionStorage.getItem('currentaccordianindex'));
        accrd = tab.find('a').attr('href').replace("#","").replace("tabs-","");
		jQuery( "#accordion-"+accrd).accordion({"active":currentactive,heightStyle: "content"});
    }
</script>

@stop
