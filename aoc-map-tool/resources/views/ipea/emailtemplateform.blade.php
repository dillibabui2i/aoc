<?php
use App\Helpers\H;
use App\Helpers\LD;

$readonly = '';

$permissions = '';
if(isset($data->id)) { 
    $permissions='edit_branch';
} else {
    $permissions='add_branch';
}
?>
@extends('layouts.myaccount')
@section('title', 'Manage Branch')
@section('content')
<!-- Begin Page Content -->

<div class="container-fluid">

    <!-- Page Heading -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Manage Branch</h1>
            <a href="{{ route('emailtemplate.grid') }}" title="Back"><i class="fa fa-chevron-circle-left"></i></a>
        </div>
    </div>
    <!-- DataTales Example -->
    @if ($errors->any())
    <div class="alert alert-danger">
        <button type="button" class="close" data-dismiss="alert">×</button>
        {!! implode('', $errors->all('<div>:message</div>')) !!}
    </div>
    @endif
    @if ($message = Session::get('error'))
    <div class="alert alert-danger">
        <button type="button" class="close" data-dismiss="alert">×</button>
        {{ $message }}
    </div>
    @endif
    @if ($message = Session::get('success'))
    <div class="alert alert-success">
        <button type="button" class="close" data-dismiss="alert">×</button>
        {{ $message }}
    </div>
    @endif
    <div>
        <form method="POST" id="add-form-validate" enctype='multipart/form-data' action="{{ route('emailtemplate.save') }}">
            <input type="hidden" name="id" id="id" value="{{ isset($data->id)?$data->id:''}}">
                @csrf
                <div class="form-row">
                
                    @canPermission($permissions) 
                
                    <div class="form-group col-md-12">
                        <label for="branch" class="text-left col-md-3">Branch</label>
                        <input type="text" name="branch"  placeholder="Branch" id="branch"  value="<?php echo isset($data['branch'])?$data['branch']:''?>" class="form-control login" />
                    </div>

                    <div class="form-group col-md-12">
                        <label for="address" class="text-left col-md-3">Address</label>
                        <input type="text" name="address"  placeholder="address" id="address"  value="<?php echo isset($data['address'])?$data['address']:''?>" class="form-control login" />
                    </div>

                    <div class="form-group col-md-12">
                        <label for="city" class="text-left col-md-3">City</label>
                        <input type="text" name="city"  placeholder="city" id="city"  value="<?php echo isset($data['city'])?$data['city']:''?>" class="form-control login" />
                    </div>

                    <div class="form-group col-md-12">
                        <label for="state" class="text-left col-md-3">State</label>
                        <input type="text" name="state"  placeholder="state" id="state"  value="<?php echo isset($data['state'])?$data['state']:''?>" class="form-control login" />
                    </div>

                    <div class="form-group col-md-12">
                        <label for="zipcode" class="text-left col-md-3">Zipcode</label>
                        <input type="text" name="zipcode"  placeholder="zipcode" id="zipcode"  value="<?php echo isset($data['zipcode'])?$data['zipcode']:''?>" class="form-control login" />
                    </div>
                    <div class="form-group col-md-12" style="position: relative;">
                        <label for="phone_number" class="text-left col-md-3">Phone Number <span class="text-danger" aria-hidden="true">*</span></label>
                        <input type="text" name="phone_number" id="phone_number" data-mask="phone" aria-required="true"
                               value="{{ formatPhone($data['phone_number'] ?? null) }}" class="form-control login" />
                        <div class="text-danger small" id="phone_number_error" role="alert" hidden></div>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="IPEA_instructions" class="text-left col-md-3">IPEA Instructions</label>
                        <textarea name="IPEA_instructions"  placeholder="IPEA_instructions" id="IPEA_instructions" class="form-control " style="height:100px"><?php echo isset($data['IPEA_instructions'])?$data['IPEA_instructions']:''?></textarea>
                    </div>

                    <div class="form-group col-md-12">
                        <label for="branch_email" class="text-left col-md-3">Branch Email</label>
                        <input type="text" name="branch_email"  placeholder="Branch Email" id="branch_email"  value="<?php echo isset($data['branch_email'])?$data['branch_email']:''?>" class="form-control login" />
                    </div>

                    <div class="form-group col-md-12">
                        <label for="email_subject" class="col-md-3">Email Subject</label>
                        <input type="text" name="email_subject"  placeholder="Email Subject" id="email_subject"  value="<?php echo isset($data['email_subject'])?$data['email_subject']:''?>" class="form-control login">
                        <div class="invalid-feedback"><?php echo $errors->first('email'); ?></div>
                    </div>

                    <div class="form-group col-md-12">
                        <label for="email_body" class="col-md-3">Email Body</label>
                        <textarea name="email_body" id="email_body" class="tinymce"><?php echo isset($data)?$data['email_body']:''?></textarea>
                    </div>
                    @endcanPermission
                </div>
                <button type="submit" class="btn btn-primary pull-left mt-3">Save</button>
        </form>
    </div>
    
</div>
<style>
    .tox-tinymce {
	width:100%;
}
    </style>
<script src="{{ asset('js/phone-mask.js') }}"></script>
<script>
    $(function () {
        const $phone = $('#phone_number');
        const $error = $('#phone_number_error');

        function validatePhone() {
            if (!$phone.length) return true;
            const value = $phone.val();
            const digits = value.replace(/\D/g, '').length;
            const message = value === '' ? 'Enter a phone number.' : (digits !== 10 ? 'Enter a 10-digit phone number.' : '');
            // The form-group is a flex row, so place the error under the input explicitly
            $error.text(message).prop('hidden', message === '').css({
                position: 'absolute',
                top: $phone.position().top + $phone.outerHeight() + 2,
                left: $phone.position().left,
                'line-height': 'normal'
            });
            $phone.toggleClass('is-invalid', message !== '');
            return message === '';
        }

        $phone.on('blur', validatePhone);
        $('#add-form-validate').on('submit', function (e) {
            if (!validatePhone()) {
                e.preventDefault();
                $phone.focus();
            }
        });
    });

    const imageUploadAjax = (blobInfo,progress) => new Promise((resolve,reject) => {
        const formData = new FormData();
        formData.append('file', blobInfo.blob(), blobInfo.filename());
        $.ajax({
            url: "{{route('emailtemplate.uploadfiles')}}",
            method: "POST",
            data: formData, cache:false,contentType:false,processData:false,
            success: function(data) {
                const json = JSON.parse(data);
                resolve(json.location);
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
