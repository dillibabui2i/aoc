<?php
namespace App\Helpers;
$loggedInUser = H::getLoggedInUser();
//var_dump(H::getComanpyClient());
$profilePicture = '';
if($loggedInUser['profile_picture']) {
	$profilePicture = env('APP_URL') . '/storage/user_profile/' .  $loggedInUser['profile_picture'];
}
$timezoneList = H::getHelper('ConstantHelper')->getTimeZoneList();
?>
@extends('layouts.myaccount')

@section('title', 'Profile')

@section('content')

<div class="heading-title">
	<h2>Update Profile</h2>
</div>	
    @if ($errors->any())
    <div class="col-sm-12">
        <div class="alert  alert-warning alert-dismissible fade show" role="alert">
            @foreach ($errors->all() as $error)
                <span><p>{{ $error }}</p></span>
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
	
	@if ($message = session('error'))
		<div class="alert alert-danger">
			<button type="button" onclick="this.parentElement.style.display='none'" class="close" data-dismiss="alert">×</button>
			<strong>{{ $message }}</strong>
		</div>
	@endif

<div class="form-container">
<div class="row">
	<div class="col-7">
    <form action="{{ route('users.profilesave') }}" id="usersform" method="POST" class="needs-validation" novalidate enctype="multipart/form-data">
        @csrf
		<div class="form-group">
			<label for="name" class="">Name</label>
			<input type="text" name="name"  placeholder="Name" id="name" class="form-control login" value="<?php echo $loggedInUser['name']?>" @if(!H::canPermissions('manageusers.add')) disabled @endif>
			<div class="invalid-feedback"><?php echo $errors->first('name'); ?></div>
		</div>
		<div class="form-group">
			<label for="title">Title</label>
			<input type="text" name="title"  placeholder="Title" id="title" class="form-control login" value="<?php echo $loggedInUser['title']?>" @if(!H::canPermissions('manageusers.add')) disabled @endif>
			<div class="invalid-feedback"><?php echo $errors->first('title'); ?></div>
		</div>
		<div class="form-group">
			<label for="username" class="">Email</label>
			<input type="text" name="email"  placeholder="Email Address" id="email" readonly="readonly" value="<?php echo $loggedInUser['email']?>" class="form-control login">
			<div class="invalid-feedback"><?php echo $errors->first('email'); ?></div>
		</div>
		<div class="form-group">
			<label for="user_timezone" class="text-left">TimeZone</label>
			<select name="user_timezone" id="user_timezone" class="form-control" @if(!H::canPermissions('manageusers.add')) disabled @endif>
				<option value=""> ---Select--- </option>
				@foreach ($timezoneList as $key => $value)
					<option  value="{{$key}}" @if(isset($loggedInUser)) {{ ($loggedInUser['user_timezone'] == $key)?'selected':'' }} @endif> {{$value}}</option>
				@endforeach
			</select>
			<div class="invalid-feedback"><?php echo $errors->first('user_timezone'); ?></div>
		</div>
		<div class="form-group">
			<label for="password" class="">Password</label>
			<input type="password" name="password"  placeholder="Password" id="password" class="form-control login">
			<div class="invalid-feedback"><?php echo $errors->first('password'); ?></div>
		</div>
		<div class="form-group">
			<label for="confirm-password" class="">Confirm Password</label>
			<input type="password" name="confirm-password"  placeholder="Confirm Password" id="confirm-password" class="form-control login">
			<div class="invalid-feedback"><?php echo $errors->first('password'); ?></div>
		</div>
		@if(LD::checkFeatureFlag('add-bilingual-on-user-page'))
		<div class="form-group">
			<label for="bilingual" class="text-left">Bilingual?</label>
			<select class="form-control" name="bilingual" id="bilingual">
					<option value="0" {{ (isset($loggedInUser['bilingual']) && $loggedInUser['bilingual'] == 0) ? 'selected' : '' }}>No</option>
					<option value="1" {{ (isset($loggedInUser['bilingual']) && $loggedInUser['bilingual'] == 1) ? 'selected' : '' }}>Yes</option>
			</select>
		</div>
		@endif
		<div class="row pb-2">
			<label for="confirm-password" class="col-4 text-left">Profile Picture</label>
			<input type="file" class="col-8" id="profile_picture" name="profile_picture">
			<small id="profile_picture" class="form-text text-muted" style="width: 100%;">(Allowed Up to 2MB; Allowed type: .jpeg, .jpg; Minimum size: 300px X 300px; Maximum size: 800px X 800px)</small>
		</div>
		<div class="form-group">
		<label for="signature" class="">Email Signature</label>
		<textarea name="signature" id="signature" class="tinymce"><?php echo $loggedInUser['signature']?></textarea>
		<div class="invalid-feedback"><?php echo $errors->first('password'); ?></div>
		</div>
		<div class="form-group">
		<input type="submit" name="submit" class="btn btn-info login-btn btn-md" value="Save">
		</div>
    </form>
	</div>
	<div class="col-5">
		<div class="row">
		<div class="col-8"><img src="{{$profilePicture}}" width="300"></div>
		</div>
	</div>
</div>
</div>
<style>
	.form-container {
		width:80%;
	}	
	.form-group, .form-group label {
		display: flex;
	}
	.form-group label {
		display: inline-block;
		vertical-align: middle;
		line-height: 40px;
		margin-right: 10px;
		width: 40%;
		text-align: left;
	}
	
	.right-side {
		display:block;
	}
	
	.heading-title {
    text-align: left;
    margin-bottom: 50px;
}
.tox-tinymce {
	width:100%;
}
</style>

<script>
	const imageUploadAjax = (blobInfo,progress) => new Promise((resolve,reject) => {
        const formData = new FormData();
        formData.append('file', blobInfo.blob(), blobInfo.filename());
		formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
        $.ajax({
            url: "{{route('profile.uploadfiles')}}",
            method: "POST",
            data: formData,
			cache: false,
			contentType: false,
			processData: false,
            success: function(data) {
                if (data.status == 'true') {
					resolve(data.location);
				}
            },
            error: function(data) {
                reject("Error Uploading Image");
            },
        });

    });

	tinymce.init({
		selector: 'textarea.tinymce',
		plugins: 'image code',
		toolbar: 'undo redo bold italic underline alignleft aligncenter alignright alignjustify image code',
		height: 600,
		relative_urls : false,
        remove_script_host : false,
        convert_urls : true,
        images_upload_handler: imageUploadAjax,
	});
</script>
	

@stop
